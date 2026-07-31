<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\UserInv;

use App\Infrastructure\Persistence\User\User;
use App\Infrastructure\Persistence\UserInv\UserInv;
use App\Invoice\UserInv\UserInvRepository;
use App\Invoice\UserInv\UserInvService;
use App\User\UserRepository as UR;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers UserInvService: saveUserInv's user relation persistence and full
 * profile/address/contact/financial/consent field assignment, and
 * deleteUserInv.
 */
#[Test]
final class UserInvServiceTest
{
    private function makeService(
        ?UserInvRepository $repository = null,
        ?UR $userR = null,
    ): UserInvService {
        $repository = $repository ?? m::mock(UserInvRepository::class);
        $userR = $userR ?? m::mock(UR::class);
        return new UserInvService($repository, $userR);
    }

    public function saveUserInvSetsUserRelationAndAllFieldGroupsAndSaves(): void
    {
        $model = new UserInv();

        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('reqId');
        $e->once()->andReturn(9);

        /** @var UR&m\MockInterface $userR */
        $userR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $userR->expects('findById');
        $e2->once()->with(9)->andReturn($user);

        /** @var UserInvRepository&m\MockInterface $repository */
        $repository = m::mock(UserInvRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->expects('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $userR);

        $array = [
            'user_id' => 9,
            'type' => 1,
            'active' => '1',
            'language' => 'en',
            'all_clients' => '1',
            'name' => 'Jane Doe',
            'company' => 'Acme',
            'listLimit' => 25,
            'address_1' => '1 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62701',
            'country' => 'US',
            'phone' => '555-1234',
            'fax' => '555-5678',
            'mobile' => '555-9999',
            'web' => 'https://example.com',
            'vat_id' => 'VAT123',
            'tax_code' => 'TX1',
            'subscribernumber' => 'SUB1',
            'iban' => 'GB33BUKB20201555555555',
            'gln' => 123456,
            'rcc' => 'RCC1',
            'consent_periodic_invoice' => '1',
            'consent_telegram_outstanding' => '1',
            'telegram_chat_id' => '42',
        ];

        $service->saveUserInv($model, $array);

        Assert::same($user, $model->getUser());
        Assert::same(9, $model->reqUserId());
        Assert::same(1, $model->getType());
        Assert::true($model->getActive());
        Assert::same('en', $model->getLanguage());
        Assert::true($model->getAllClients());
        Assert::same('Jane Doe', $model->getName());
        Assert::same('Acme', $model->getCompany());
        Assert::same(25, $model->getListLimit());
        Assert::same('1 Main St', $model->getAddress1());
        Assert::same('Springfield', $model->getCity());
        Assert::same('IL', $model->getState());
        Assert::same('62701', $model->getZip());
        Assert::same('US', $model->getCountry());
        Assert::same('555-1234', $model->getPhone());
        Assert::same('555-5678', $model->getFax());
        Assert::same('555-9999', $model->getMobile());
        Assert::same('https://example.com', $model->getWeb());
        Assert::same('VAT123', $model->getVatId());
        Assert::same('TX1', $model->getTaxCode());
        Assert::same('SUB1', $model->getSubscribernumber());
        Assert::same('GB33BUKB20201555555555', $model->getIban());
        Assert::same(123456, $model->getGln());
        Assert::same('RCC1', $model->getRcc());
        Assert::true($model->getConsentPeriodicInvoice());
        Assert::true($model->getConsentTelegramOutstanding());
        Assert::same('42', $model->getTelegramChatId());
    }

    public function saveUserInvWithoutTelegramChatIdSetsItNull(): void
    {
        $model = new UserInv();

        /** @var User&m\MockInterface $user */
        $user = m::mock(User::class);
        /** @var \Mockery\Expectation $e */
        $e = $user->shouldReceive('reqId');
        $e->once()->andReturn(9);

        /** @var UR&m\MockInterface $userR */
        $userR = m::mock(UR::class);
        /** @var \Mockery\Expectation $e2 */
        $e2 = $userR->expects('findById');
        $e2->once()->with(9)->andReturn($user);

        /** @var UserInvRepository&m\MockInterface $repository */
        $repository = m::mock(UserInvRepository::class);
        /** @var \Mockery\Expectation $e3 */
        $e3 = $repository->expects('save');
        $e3->once()->with($model);

        $service = $this->makeService($repository, $userR);

        $array = ['user_id' => 9, 'active' => '0', 'all_clients' => '0'];

        $service->saveUserInv($model, $array);

        Assert::false($model->getActive());
        Assert::false($model->getAllClients());
        Assert::false($model->getConsentPeriodicInvoice());
        Assert::false($model->getConsentTelegramOutstanding());
        Assert::null($model->getTelegramChatId());
    }

    public function deleteUserInvCallsRepositoryDelete(): void
    {
        $model = new UserInv();

        /** @var UserInvRepository&m\MockInterface $repository */
        $repository = m::mock(UserInvRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteUserInv($model);
    }
}
