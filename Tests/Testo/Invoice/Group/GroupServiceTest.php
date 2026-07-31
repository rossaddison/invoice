<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Group;

use App\Infrastructure\Persistence\Group\Group;
use App\Invoice\Group\GroupException;
use App\Invoice\Group\GroupRepository;
use App\Invoice\Group\GroupService;
use Mockery as m;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

/**
 * Covers GroupService: saveGroup's field assignment (the missing-key ternary
 * branches are dead code — their string/int literals are never applied to
 * the model, so a missing key just keeps whichever default the entity's
 * constructor already set), and deleteGroup's system-group protection.
 */
#[Test]
final class GroupServiceTest
{
    private const CUSTOM_GROUP_NAME = 'Custom Group';

    private function makeService(?GroupRepository $repository = null): GroupService
    {
        $repository = $repository ?? m::mock(GroupRepository::class);
        return new GroupService($repository);
    }

    public function saveGroupSetsAllFieldsAndSaves(): void
    {
        $model = new Group();
        $array = [
            'name' => self::CUSTOM_GROUP_NAME,
            'identifier_format' => 'CG{{{id}}}',
            'next_id' => 5,
            'left_pad' => 4,
        ];

        /** @var GroupRepository&m\MockInterface $repository */
        $repository = m::mock(GroupRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveGroup($model, $array);

        Assert::same(self::CUSTOM_GROUP_NAME, $model->getName());
        Assert::same('CG{{{id}}}', $model->getIdentifierFormat());
        Assert::same(5, $model->getNextId());
        Assert::same(4, $model->getLeftPad());
    }

    public function saveGroupKeepsConstructorDefaultsWhenFieldsMissing(): void
    {
        $model = new Group();

        /** @var GroupRepository&m\MockInterface $repository */
        $repository = m::mock(GroupRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveGroup($model, []);

        Assert::same('', $model->getName());
        Assert::same('', $model->getIdentifierFormat());
        Assert::null($model->getNextId());
        Assert::null($model->getLeftPad());
    }

    public function deleteGroupCallsRepositoryDeleteForNonSystemGroup(): void
    {
        $model = new Group();
        $model->setName(self::CUSTOM_GROUP_NAME);

        /** @var GroupRepository&m\MockInterface $repository */
        $repository = m::mock(GroupRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteGroup($model);
    }

    #[ExpectException(GroupException::class)]
    public function deleteGroupThrowsForQuoteGroup(): void
    {
        $model = new Group();
        $model->setName('Quote Group');
        $this->makeService()->deleteGroup($model);
    }

    #[ExpectException(GroupException::class)]
    public function deleteGroupThrowsForInvoiceGroup(): void
    {
        $model = new Group();
        $model->setName('Invoice Group');
        $this->makeService()->deleteGroup($model);
    }

    #[ExpectException(GroupException::class)]
    public function deleteGroupThrowsForSalesOrderGroup(): void
    {
        $model = new Group();
        $model->setName('Sales Order Group');
        $this->makeService()->deleteGroup($model);
    }

    #[ExpectException(GroupException::class)]
    public function deleteGroupThrowsForCreditNoteGroup(): void
    {
        $model = new Group();
        $model->setName('Credit Note Group');
        $this->makeService()->deleteGroup($model);
    }

    public function deleteGroupThrowsWithDescriptiveMessage(): void
    {
        $model = new Group();
        $model->setName('Invoice Group');

        try {
            $this->makeService()->deleteGroup($model);
        } catch (GroupException $e) {
            Assert::same('System group "Invoice Group" cannot be deleted.', $e->getMessage());
            return;
        }
        Assert::true(false);
    }
}
