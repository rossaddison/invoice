<?php

declare(strict_types=1);

namespace App\Invoice\InvRecurring;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvRecurring\InvRecurring;
use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Helpers\DateHelper;
use App\Invoice\Helpers\Telegram\TelegramHelper;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\Inv\InvService as IS;
use App\Invoice\InvItem\IiAddProductDeps;
use App\Invoice\InvItem\InvItemService;
use App\Invoice\ProductClient\ProductClientRepository as PCR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Log\Logger;

/**
 * Generates recurring invoices and sends Telegram balance reminders.
 * Extracted from InvRecurringController::cron() so both the legacy HTTP
 * cron_key endpoint and the `invrecurring/process` console command can
 * share the exact same logic.
 */
final class InvRecurringCronService
{
    public function __construct(
        private readonly InvRecurringRepository $irR,
        private readonly IR $iR,
        private readonly GR $gR,
        private readonly PCR $pcR,
        private readonly IS $iS,
        private readonly InvItemService $invItemService,
        private readonly SR $sR,
        private readonly Logger $_logger,
    ) {
    }

    public function resolveAdminUser(InvCronUserDeps $d): ?User
    {
        /** @var UserInv $ui */
        foreach ($d->uiR->findAllPreloaded() as $ui) {
            if ($ui->getType() === 0) {
                return $d->userRepository->findById($ui->reqUserId());
            }
        }
        return null;
    }

    /**
     * @return array{created: int, reminded: int}
     */
    public function process(User $user, InvItemDeps $itemDeps, InvCronUserDeps $cronDeps): array
    {
        $created = 0;
        $reminded = 0;
        $token = $this->sR->getSetting('telegram_token');
        $telegramActive = $this->sR->getSetting('enable_telegram') === '1' && strlen($token) > 1;

        /** @var InvRecurring $invRecurring */
        foreach ($this->irR->active() as $invRecurring) {
            $prevInvId = $invRecurring->reqInvId();
            $baseInv = $this->iR->repoInvUnloadedquery($prevInvId);
            if (null === $baseInv) {
                continue;
            }
            $clientId = $baseInv->reqClientId();

            $userInv = $this->resolveRecurringUser($clientId, $cronDeps);
            if (null === $userInv) {
                continue;
            }

            $created += $this->createIfConsentedWithProducts($userInv, $clientId, $user, $itemDeps);

            $this->advanceRecurringDate($invRecurring);

            if ($telegramActive) {
                $reminded += $this->sendTelegramReminderIfNeeded($prevInvId, $userInv, $cronDeps, $token);
            }
        }

        return ['created' => $created, 'reminded' => $reminded];
    }

    private function resolveRecurringUser(int $clientId, InvCronUserDeps $cronDeps): ?UserInv
    {
        $userClient = $cronDeps->uclR->repoUserquery($clientId);
        if (null === $userClient) {
            return null;
        }
        return $cronDeps->uiR->repoUserInvUserIdquery($userClient->reqUserId());
    }

    private function createIfConsentedWithProducts(
        UserInv $userInv,
        int $clientId,
        User $user,
        InvItemDeps $itemDeps,
    ): int {
        if (!$userInv->getConsentPeriodicInvoice()) {
            return 0;
        }
        /** @var array<int,\App\Infrastructure\Persistence\ProductClient\ProductClient> $productClients */
        $productClients = $this->pcR->findByClientId($clientId);
        if (count($productClients) === 0) {
            return 0;
        }
        $savedInv = $this->iS->saveInv($user, new Inv(), [
            'client_id'     => $clientId,
            'group_id'      => 1,
            'status_id'     => 1,
            'date_created'  => (new \DateTimeImmutable())->format('Y-m-d'),
            'date_supplied' => (new \DateTimeImmutable())->format('Y-m-d'),
        ], $this->sR, $this->gR);
        $this->addProductItemsToInv($productClients, (string) $savedInv->reqId(), $itemDeps);
        return 1;
    }

    /**
     * Shared with InvRecurringController::createFromProductClient(), which
     * builds the first invoice of a new recurring schedule the same way the
     * cron builds each subsequent one.
     *
     * @param array<int,\App\Infrastructure\Persistence\ProductClient\ProductClient> $productClients
     */
    public function addProductItemsToInv(array $productClients, string $invId, InvItemDeps $d): void
    {
        foreach ($productClients as $productClient) {
            $productId = $productClient->getProductId();
            if (null === $productId) {
                continue;
            }
            $product = $d->pR->repoProductquery($productId);
            if (null !== $product) {
                $this->invItemService->addInvItemProduct(
                    new InvItem(),
                    [
                        'product_id'      => $product->reqId(),
                        'tax_rate_id'     => $product->reqTaxRateId(),
                        'quantity'        => 1.00,
                        'price'           => $product->getProductPrice() ?? 0.00,
                        'discount_amount' => 0.00,
                        'product_unit_id' => $product->reqUnitId(),
                    ],
                    $invId,
                    new IiAddProductDeps($d->pR, $d->trR, $d->iias, $d->iiar, $this->sR, $d->unR),
                );
            }
        }
    }

    private function advanceRecurringDate(InvRecurring $invRecurring): void
    {
        $dateHelper = new DateHelper($this->sR);
        $nextRaw = $invRecurring->getNext();
        $nextString = match (true) {
            $nextRaw instanceof \DateTimeImmutable => $nextRaw->format('Y-m-d'),
            is_string($nextRaw) && $nextRaw !== '' => $nextRaw,
            default                                => date('Y-m-d'),
        };
        $invRecurring->setNext($dateHelper->incrementDateStringToDateTime($nextString, $invRecurring->getFrequency()));
        $this->irR->save($invRecurring);
    }

    private function sendTelegramReminderIfNeeded(
        int $prevInvId,
        UserInv $userInv,
        InvCronUserDeps $d,
        string $token,
    ): int {
        $invAmount = $d->iaR->repoInvquery($prevInvId);
        $balance = $invAmount?->getBalance() ?? 0.0;
        if ($balance > 0.0 && $userInv->getConsentTelegramOutstanding()) {
            $chatId = $userInv->getTelegramChatId();
            if (null !== $chatId && $chatId !== '') {
                $telegramHelper = new TelegramHelper($token, $this->_logger);
                $telegramHelper->getBotApi()->sendMessage(
                    $chatId,
                    'Invoice #' . $prevInvId . ' has an outstanding balance of '
                        . number_format($balance, 2) . '. Please log in to make a payment.',
                );
                return 1;
            }
        }
        return 0;
    }
}
