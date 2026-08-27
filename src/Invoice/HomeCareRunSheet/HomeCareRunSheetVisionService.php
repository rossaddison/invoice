<?php

declare(strict_types=1);

namespace App\Invoice\HomeCareRunSheet;

use Anthropic\Client;
use Anthropic\Messages\Base64ImageSource\MediaType;
use Anthropic\Messages\Message;
use Anthropic\Messages\TextBlock;
use App\Infrastructure\Persistence\HomeCareRunSheetItem\HomeCareRunSheetItem;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\Enum\DoNotSendReason;
use App\Invoice\HomeCareRunSheetItem\HomeCareRunSheetItemRepository as RSIR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\Worker\WorkerRepository as WR;
use RuntimeException;

/**
 * Step 3b of the run-sheet reconciliation (see project_homecare_run_signoff_design
 * memory): reads one scanned run-sheet image and writes the vision-detected
 * per-invoice adjustments onto the batch's HomeCareRunSheetItem rows.
 *
 * Model choice: Claude Haiku 4.5 (confirmed with the user) — this is a
 * bounded, closed-set extraction task (match handwriting against a known
 * worker roster and the fixed DoNotSendReason codes), not open-ended
 * reasoning, and the "temporary index of adjustments" staging screen that
 * follows this step means an occasional misread is a one-click correction,
 * not a data-integrity incident. Structured outputs (a JSON schema
 * enumerating exactly the invoice ids on this sheet, the active worker ids,
 * and the reason codes) rules out the model inventing an id that doesn't
 * exist, rather than trusting free text.
 */
final readonly class HomeCareRunSheetVisionService
{
    private const string MODEL = 'claude-haiku-4-5';

    public function __construct(
        private RSIR $rsiR,
        private WR $wR,
        private SR $sR,
    ) {}

    /**
     * @param array<int, HomeCareRunSheetItem> $items the sheet's own snapshot
     *                                                 rows — both the source
     *                                                 of the printed
     *                                                 reference table in the
     *                                                 prompt and the rows
     *                                                 detections get written
     *                                                 back onto.
     */
    public function readScan(array $items, string $imageBytes, MediaType $mediaType): void
    {
        if ($items === []) {
            return;
        }
        $apiKey = $this->sR->getSetting('homecare_vision_api_key');
        if ($apiKey === '') {
            throw new RuntimeException(
                'homecare_vision_api_key is not configured — see Settings.',
            );
        }

        $workers = $this->wR->findAllActive();
        $client = new Client(apiKey: $apiKey);
        $message = $client->messages->create(
            model: self::MODEL,
            maxTokens: 4096,
            system: $this->buildSystemPrompt($workers),
            messages: [[
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'image',
                        'source' => [
                            'type' => 'base64',
                            'mediaType' => $mediaType->value,
                            'data' => base64_encode($imageBytes),
                        ],
                    ],
                    ['type' => 'text', 'text' => $this->buildReferenceTable($items)],
                ],
            ]],
            outputConfig: ['format' => [
                'type' => 'json_schema',
                'schema' => $this->buildSchema($items, $workers),
            ]],
        );

        $this->applyDetections($items, $this->extractRows($message));
    }

    /**
     * @param array<int, Worker> $workers
     */
    private function buildSystemPrompt(array $workers): string
    {
        $names = array_map(
            static fn (Worker $w): string => $w->reqId() . ' = ' . $w->getFirstname() . ' ' . $w->getLastname(),
            $workers,
        );
        $reasons = implode(', ', array_map(static fn (DoNotSendReason $r): string => $r->value, DoNotSendReason::cases()));

        return <<<PROMPT
            You are reading a hand-annotated HomeCare cleaning run sheet. The
            image is a scanned/photographed printed sheet listing invoices in
            route order; a field worker or supervisor has marked each row by
            hand during or after the run: circling/writing which worker
            actually did the job, marking it complete or not, and — only for
            an incomplete row — writing one reason code.

            Known workers (worker_id = name), match handwritten names/initials
            against these, do not invent a worker_id outside this list:
            {$this->joinLines($names)}

            Valid reason codes (use exactly one of these, only when a row is
            marked incomplete): {$reasons}

            A separate text block lists every invoice_id on this sheet with
            its printed client name and the worker it was originally assigned
            to, in the same order they appear on the sheet — use it to align
            what you read against the correct row. For every invoice_id in
            that list, report: which worker_id actually did it (null if
            illegible or not marked — do not guess), whether it was completed,
            and the reason code if not completed (null if completed).
            PROMPT;
    }

    /**
     * @param array<int, HomeCareRunSheetItem> $items
     */
    private function buildReferenceTable(array $items): string
    {
        $lines = array_map(
            static fn (HomeCareRunSheetItem $item): string =>
                'invoice_id=' . $item->reqInvId() . ' expected_worker_id=' . ($item->getExpectedWorkerId() ?? 'none'),
            $items,
        );
        return "Printed rows on this sheet:\n" . $this->joinLines($lines);
    }

    /**
     * @param array<int, HomeCareRunSheetItem> $items
     * @param array<int, Worker> $workers
     * @return array<string, mixed>
     */
    private function buildSchema(array $items, array $workers): array
    {
        $invoiceIds = array_map(static fn (HomeCareRunSheetItem $i): int => $i->reqInvId(), $items);
        $workerIds = array_map(static fn (Worker $w): int => $w->reqId(), $workers);
        $reasonCodes = array_map(static fn (DoNotSendReason $r): string => $r->value, DoNotSendReason::cases());

        return [
            'type' => 'object',
            'properties' => [
                'rows' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'invoice_id' => ['type' => 'integer', 'enum' => $invoiceIds],
                            'worker_id' => ['type' => ['integer', 'null'], 'enum' => [...$workerIds, null]],
                            'completed' => ['type' => 'boolean'],
                            'reason_code' => ['type' => ['string', 'null'], 'enum' => [...$reasonCodes, null]],
                        ],
                        'required' => ['invoice_id', 'worker_id', 'completed', 'reason_code'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['rows'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @return list<array{invoice_id: int, worker_id: int|null, completed: bool, reason_code: string|null}>
     */
    private function extractRows(Message $message): array
    {
        foreach ($message->content as $block) {
            if ($block instanceof TextBlock) {
                /** @var array{rows?: list<array{invoice_id: int, worker_id: int|null, completed: bool, reason_code: string|null}>} $decoded */
                $decoded = json_decode($block->text, true) ?? [];
                return $decoded['rows'] ?? [];
            }
        }
        return [];
    }

    /**
     * @param array<int, HomeCareRunSheetItem> $items
     * @param list<array{invoice_id: int, worker_id: int|null, completed: bool, reason_code: string|null}> $rows
     */
    private function applyDetections(array $items, array $rows): void
    {
        $byInvId = [];
        foreach ($items as $item) {
            $byInvId[$item->reqInvId()] = $item;
        }
        foreach ($rows as $row) {
            $item = $byInvId[$row['invoice_id']] ?? null;
            if ($item === null) {
                continue;
            }
            $item->setDetection($row['worker_id'], $row['completed'], $row['reason_code']);
            $this->rsiR->save($item);
        }
    }

    /**
     * @param array<int, string> $lines
     */
    private function joinLines(array $lines): string
    {
        return implode("\n", $lines);
    }
}
