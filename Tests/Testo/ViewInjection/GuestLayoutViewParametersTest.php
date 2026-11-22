<?php

declare(strict_types=1);

namespace Tests\Testo\ViewInjection;

use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\Enum\BusinessType;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\SalesOrder\SalesOrderRepository;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Invoice\UserInv\UserInvRepository;
use App\User\UserService;
use App\ViewInjection\GuestLayoutViewParameters;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;

/**
 * Whether the guest layout shows its Quote and Sales Order menus. They show only for a signed-in, active observer
 * whose assigned clients have a quote or a sales order, and never on a window-cleaning (home services) install, which
 * has neither: the staff layout hides them for the same business_type.
 *
 * @see GuestLayoutViewParameters
 */
#[Test]
final class GuestLayoutViewParametersTest
{
    private const int USER_ID = 7;
    private const int CLIENT_ID = 21;

    /**
     * @param string $businessType the stored business_type setting ('' is a standard install)
     * @param int $quotes how many quotes the observer's client has
     * @param int $salesOrders how many sales orders the observer's client has
     */
    private function parameters(
        string $businessType,
        int $quotes,
        int $salesOrders,
        bool $signedIn = true,
        bool $active = true,
        bool $expectQuoteLookups = true,
    ): GuestLayoutViewParameters {
        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn(self::USER_ID);
        $userService = TypedMock::of(UserService::class);
        $userService->shouldReceive('getUser')->andReturn($signedIn ? $user : null);

        $userInv = TypedMock::of(UserInv::class);
        $userInv->shouldReceive('getActive')->andReturn($active);
        $userInvRepository = TypedMock::of(UserInvRepository::class);
        $userInvRepository->shouldReceive('repoUserInvUserIdcount')->andReturn(1);
        $userInvRepository->shouldReceive('repoUserInvUserIdquery')->andReturn($userInv);

        $userClientRepository = TypedMock::of(UserClientRepository::class);
        $userClientRepository->shouldReceive('getAssignedToUser')->andReturn([self::CLIENT_ID]);

        $quoteRepository = TypedMock::of(QuoteRepository::class);
        $salesOrderRepository = TypedMock::of(SalesOrderRepository::class);
        if ($expectQuoteLookups) {
            $quoteRepository->shouldReceive('countAllWithUserClient')->andReturn($quotes);
            $salesOrderRepository->shouldReceive('countAllWithUserClient')->andReturn($salesOrders);
        } else {
            $quoteRepository->shouldNotReceive('countAllWithUserClient');
            $salesOrderRepository->shouldNotReceive('countAllWithUserClient');
        }

        $settings = TypedMock::of(SettingRepository::class);
        $settings->shouldReceive('getSetting')->with(BusinessType::SETTING_KEY)->andReturn($businessType);

        return new GuestLayoutViewParameters(
            $userService,
            $userInvRepository,
            $userClientRepository,
            $quoteRepository,
            $salesOrderRepository,
            $settings,
        );
    }

    public function aStandardInstallShowsTheMenusWhenTheClientHasQuotes(): void
    {
        $parameters = $this->parameters('', quotes: 3, salesOrders: 0);

        Assert::same($parameters->getLayoutParameters(), ['hasQuotesOrSalesOrders' => true]);
    }

    public function aStandardInstallShowsTheMenusWhenTheClientHasOnlySalesOrders(): void
    {
        $parameters = $this->parameters('', quotes: 0, salesOrders: 2);

        Assert::same($parameters->getLayoutParameters(), ['hasQuotesOrSalesOrders' => true]);
    }

    public function aStandardInstallHidesTheMenusWhenTheClientHasNeither(): void
    {
        $parameters = $this->parameters('', quotes: 0, salesOrders: 0);

        Assert::same($parameters->getLayoutParameters(), ['hasQuotesOrSalesOrders' => false]);
    }

    public function aWindowCleaningInstallHidesTheMenusEvenWhenTheClientHasQuotesAndSalesOrders(): void
    {
        $parameters = $this->parameters(
            BusinessType::WindowCleaning->value,
            quotes: 5,
            salesOrders: 5,
            expectQuoteLookups: false,
        );

        Assert::same($parameters->getLayoutParameters(), ['hasQuotesOrSalesOrders' => false]);
    }

    public function backToStandardBringsTheMenusBack(): void
    {
        // the General tab's Standard choice stores the word 'standard'
        $parameters = $this->parameters(BusinessType::Standard->value, quotes: 1, salesOrders: 1);

        Assert::same($parameters->getLayoutParameters(), ['hasQuotesOrSalesOrders' => true]);
    }

    public function noMenusForSomeoneWhoIsNotSignedInOrNotActive(): void
    {
        $guest = $this->parameters('', quotes: 4, salesOrders: 4, signedIn: false);
        $inactive = $this->parameters('', quotes: 4, salesOrders: 4, active: false);

        Assert::same($guest->getLayoutParameters(), ['hasQuotesOrSalesOrders' => false]);
        Assert::same($inactive->getLayoutParameters(), ['hasQuotesOrSalesOrders' => false]);
    }
}
