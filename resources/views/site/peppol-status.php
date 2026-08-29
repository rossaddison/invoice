<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Public Peppol Access Point status page — same idea as
 * site/gateway-status.php (which payment gateways have passed a real
 * sandbox check) for the two providers PeppolSendServiceRouter can
 * resolve to. Just two rows, so a plain table rather than the
 * GridView/GatewayStatusListWidget machinery gateway-status.php uses —
 * see SiteController::peppolStatus()'s own docblock for why.
 *
 * @var list<array{
 *     name: string,
 *     sdk_version: string|null,
 *     sandbox_status: string,
 *     sandbox_tested_at: string|null,
 *     notes: string,
 * }> $rows
 * @var list<array{name: string, regions: string, notes: string}> $referenceProviders
 * @var array{tested: bool, tested_at: string|null, peer_party_id: string|null} $as4Bilateral
 */

$statusBadge = static function (string $status): string {
    return match ($status) {
        'pass' => Html::span('Sandbox tested ✓', ['class' => 'badge text-bg-success'])->render(),
        default => Html::span('Not yet sandbox tested', ['class' => 'badge text-bg-secondary'])->render(),
    };
};
?>
<?= Html::openTag('section', ['class' => 'py-5']); ?>
    <?= Html::openTag('div', ['class' => 'container']); ?>
        <?= Html::tag('h1', 'Peppol Access Point Status', ['class' => 'display-5 fw-bold mb-3'])->render(); ?>
        <?= Html::tag(
            'p',
            'Which Access Point provider actually sends your Peppol documents, and whether it has a real'
                . ' confirmed send behind it — not a synthetic ping, since neither provider exposes a'
                . ' side-effect-free health check the way a payment gateway does. Only one provider is'
                . ' active at a time (Settings > Peppol Access Point).',
            ['class' => 'lead mb-4'],
        )->render(); ?>

        <?= Html::openTag('div', ['class' => 'table-responsive']); ?>
            <?= Html::openTag('table', ['class' => 'table table-hover align-middle']); ?>
                <?= Html::openTag('thead', ['class' => 'table-dark']); ?>
                    <?= Html::openTag('tr'); ?>
                        <?php foreach (['Provider', 'Version', 'Sandbox Tested', 'Notes'] as $col): ?>
                            <?= Html::tag('th', $col)->render(); ?>
                        <?php endforeach; ?>
                    <?= Html::closeTag('tr'); ?>
                <?= Html::closeTag('thead'); ?>
                <?= Html::openTag('tbody'); ?>
                    <?php foreach ($rows as $row): ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::tag('td', $row['name'], ['data-label' => 'Provider'])->render(); ?>
                            <?= Html::tag('td', $row['sdk_version'] ?? '—',
                                ['data-label' => 'Version'])->render(); ?>
                            <?= Html::tag('td',
                                $statusBadge($row['sandbox_status'])
                                . ($row['sandbox_tested_at'] !== null
                                    ? Html::tag('div', $row['sandbox_tested_at'],
                                        ['class' => 'small text-muted'])->render()
                                    : ''),
                                ['data-label' => 'Sandbox Tested'])
                                ->encode(false)->render(); ?>
                            <?= Html::tag('td', $row['notes'],
                                ['class' => 'small text-muted', 'data-label' => 'Notes'])->render(); ?>
                        <?= Html::closeTag('tr'); ?>
                    <?php endforeach; ?>
                <?= Html::closeTag('tbody'); ?>
            <?= Html::closeTag('table'); ?>
        <?= Html::closeTag('div'); ?>

        <?= Html::tag('h2', 'AS4 Bilateral (self-hosted)',
            ['class' => 'h4 fw-bold mt-5 mb-2'])->render(); ?>
        <?= Html::tag(
            'p',
            'A separate, self-hosted AS4 stack for point-to-point delivery without Peppol PKI or SMP'
                . ' lookup — used for BIS Advanced Ordering and bilateral connectivity testing. Not'
                . ' selected via the Access Point Provider setting above; independent of it.',
            ['class' => 'text-muted small mb-3'],
        )->render(); ?>
        <?= Html::openTag('div', ['class' => 'table-responsive mb-4']); ?>
            <?= Html::openTag('table', ['class' => 'table table-sm']); ?>
                <?= Html::openTag('tbody'); ?>
                    <?= Html::openTag('tr'); ?>
                        <?= Html::tag('td', 'Bilateral send tested', ['class' => 'fw-semibold'])->render(); ?>
                        <?= Html::tag('td',
                            $statusBadge($as4Bilateral['tested'] ? 'pass' : 'untested')
                            . ($as4Bilateral['tested_at'] !== null
                                ? Html::tag('div', $as4Bilateral['tested_at'],
                                    ['class' => 'small text-muted'])->render()
                                : ''))
                            ->encode(false)->render(); ?>
                    <?= Html::closeTag('tr'); ?>
                    <?php if ($as4Bilateral['peer_party_id'] !== null): ?>
                        <?= Html::openTag('tr'); ?>
                            <?= Html::tag('td', 'Last confirmed peer', ['class' => 'fw-semibold'])->render(); ?>
                            <?= Html::tag('td', $as4Bilateral['peer_party_id'])->render(); ?>
                        <?= Html::closeTag('tr'); ?>
                    <?php endif; ?>
                <?= Html::closeTag('tbody'); ?>
            <?= Html::closeTag('table'); ?>
        <?= Html::closeTag('div'); ?>

        <?php if ($referenceProviders !== []): ?>
            <?= Html::tag('h2', 'Other providers surveyed for future regional coverage',
                ['class' => 'h4 fw-bold mt-5 mb-2'])->render(); ?>
            <?= Html::tag(
                'p',
                'Not integrated into this app — no send capability exists for these yet, so'
                    . ' there\'s nothing here for this app itself to have tested. Listed because a real,'
                    . ' no-sales-call sandbox was confirmed directly against the provider\'s own site.',
                ['class' => 'text-muted small mb-3'],
            )->render(); ?>
            <?= Html::openTag('div', ['class' => 'table-responsive']); ?>
                <?= Html::openTag('table', ['class' => 'table table-sm']); ?>
                    <?= Html::openTag('thead'); ?>
                        <?= Html::openTag('tr'); ?>
                            <?php foreach (['Provider', 'Regions', 'Notes'] as $col): ?>
                                <?= Html::tag('th', $col)->render(); ?>
                            <?php endforeach; ?>
                        <?= Html::closeTag('tr'); ?>
                    <?= Html::closeTag('thead'); ?>
                    <?= Html::openTag('tbody'); ?>
                        <?php foreach ($referenceProviders as $provider): ?>
                            <?= Html::openTag('tr'); ?>
                                <?= Html::tag('td', $provider['name'],
                                    ['data-label' => 'Provider'])->render(); ?>
                                <?= Html::tag('td', $provider['regions'],
                                    ['data-label' => 'Regions'])->render(); ?>
                                <?= Html::tag('td', $provider['notes'],
                                    ['class' => 'small text-muted', 'data-label' => 'Notes'])->render(); ?>
                            <?= Html::closeTag('tr'); ?>
                        <?php endforeach; ?>
                    <?= Html::closeTag('tbody'); ?>
                <?= Html::closeTag('table'); ?>
            <?= Html::closeTag('div'); ?>
        <?php endif; ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('section'); ?>
