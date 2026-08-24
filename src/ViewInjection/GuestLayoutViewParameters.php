<?php

declare(strict_types=1);

namespace App\ViewInjection;

use App\Invoice\Quote\QuoteRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\User\UserService;
use Yiisoft\Yii\View\Renderer\LayoutParametersInjectionInterface;

/**
 * `resources/views/layout/guest.php`-only data — scoped the same way
 * `App\Webshop\StorefrontViewParameters` is scoped to the storefront
 * layout (see `config/common/di/guest-layout.php`'s own docblock for why
 * this can't just be added to the shared `LayoutViewInjection`, which
 * also serves the staff `invoice.php`/`soletrader/main.php` layouts and
 * has no use for this).
 *
 * Whether to show the Quote/SalesOrder nav dropdowns at all: not gated
 * on account "type" (webshop customer vs. a traditional B2B client with
 * real quote/sales-order history) — there's no such distinction anywhere
 * in this app; `App\Api\OrderService::createOrder()`'s own docblock notes
 * it reuses the exact same observer-role account shape the pre-existing
 * self-service signup flow already used. Gated on whether the observer's
 * assigned client(s) actually have any Quotes/SalesOrders at all instead
 * — a webshop customer never will, so this hides them correctly without
 * needing a new marker, and a genuine B2B client with real quote/SO
 * history still sees them. Confirmed live: a webshop-checkout customer's
 * `inv/view` was showing empty, confusing "Quote"/"Sales Order" nav
 * buttons that led nowhere useful.
 */
final readonly class GuestLayoutViewParameters implements LayoutParametersInjectionInterface
{
    public function __construct(
        private UserService $userService,
        private UserInvRepository $userInvRepository,
        private UserClientRepository $userClientRepository,
        private QuoteRepository $quoteRepository,
        private SalesOrderRepository $salesOrderRepository,
    ) {
    }

    /** @return array{hasQuotesOrSalesOrders: bool} */
    #[\Override]
    public function getLayoutParameters(): array
    {
        return [
            'hasQuotesOrSalesOrders' => $this->resolveHasQuotesOrSalesOrders(),
        ];
    }

    private function resolveHasQuotesOrSalesOrders(): bool
    {
        $userId = $this->resolveActiveObserverUserId();
        if ($userId === null) {
            return false;
        }

        /** @var list<int> $clientIds */
        $clientIds = $this->userClientRepository->getAssignedToUser($userId);
        foreach ($clientIds as $clientId) {
            // Same countAllWithUserClient($user_id, $client_id) both
            // repositories already expose for App\Invoice\UserClient\
            // UserClientController's own "can this link be safely
            // deleted" check — reused here for "does it have anything
            // to show", not deletion safety, but the same real query.
            if ($this->quoteRepository->countAllWithUserClient($userId, $clientId) > 0
                || $this->salesOrderRepository->countAllWithUserClient($userId, $clientId) > 0
            ) {
                return true;
            }
        }
        return false;
    }

    private function resolveActiveObserverUserId(): ?int
    {
        $user = $this->userService->getUser();
        if ($user === null) {
            return null;
        }

        $userId = $user->reqId();
        $userInv = $this->userInvRepository->repoUserInvUserIdcount($userId) > 0
            ? $this->userInvRepository->repoUserInvUserIdquery($userId)
            : null;
        return ($userInv !== null && $userInv->getActive()) ? $userId : null;
    }
}
