<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\CustomField;

use App\Infrastructure\Persistence\CustomField\CustomField;
use App\Invoice\CustomField\CustomFieldRepository;
use App\Invoice\CustomField\CustomFieldService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers CustomFieldService: saveCustomField's field assignment (including
 * the always-set email_multiple/required boolean flags) and
 * deleteCustomField.
 */
#[Test]
final class CustomFieldServiceTest
{
    private function makeService(?CustomFieldRepository $repository = null): CustomFieldService
    {
        /** @var CustomFieldRepository&m\MockInterface $repository */
        $repository = $repository ?? m::mock(CustomFieldRepository::class);
        return new CustomFieldService($repository);
    }

    public function saveCustomFieldSetsAllFieldsAndSaves(): void
    {
        $model = new CustomField();
        $array = [
            'table' => 'inv',
            'label' => 'Purchase Order',
            'type' => 'TEXT',
            'location' => 1,
            'order' => 5,
            'email_min_length' => 3,
            'email_max_length' => 50,
            'email_multiple' => '1',
            'text_min_length' => 1,
            'text_max_length' => 100,
            'text_area_min_length' => 1,
            'text_area_max_length' => 500,
            'text_area_cols' => 40,
            'text_area_rows' => 5,
            'text_area_wrap' => 'soft',
            'number_min' => 0,
            'number_max' => 999,
            'url_min_length' => 5,
            'url_max_length' => 200,
            'required' => '1',
        ];

        /** @var CustomFieldRepository&m\MockInterface $repository */
        $repository = m::mock(CustomFieldRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveCustomField($model, $array);

        Assert::same('inv', $model->getTable());
        Assert::same('Purchase Order', $model->getLabel());
        Assert::same('TEXT', $model->getType());
        Assert::same(1, $model->getLocation());
        Assert::same(5, $model->getOrder());
        Assert::same(3, $model->getEmailMinLength());
        Assert::same(50, $model->getEmailMaxLength());
        Assert::true($model->getEmailMultiple());
        Assert::same(1, $model->getTextMinLength());
        Assert::same(100, $model->getTextMaxLength());
        Assert::same(1, $model->getTextAreaMinLength());
        Assert::same(500, $model->getTextAreaMaxLength());
        Assert::same(40, $model->getTextAreaCols());
        Assert::same(5, $model->getTextAreaRows());
        Assert::same('soft', $model->getTextAreaWrap());
        Assert::same(0, $model->getNumberMin());
        Assert::same(999, $model->getNumberMax());
        Assert::same(5, $model->getUrlMinLength());
        Assert::same(200, $model->getUrlMaxLength());
        Assert::true($model->getRequired());
    }

    public function saveCustomFieldSkipsOptionalFieldsWhenMissing(): void
    {
        $model = new CustomField();
        $array = [
            'email_multiple' => '0',
            'required' => '0',
        ];

        /** @var CustomFieldRepository&m\MockInterface $repository */
        $repository = m::mock(CustomFieldRepository::class);
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveCustomField($model, $array);

        Assert::same('', $model->getTable());
        Assert::false($model->getEmailMultiple());
        Assert::false($model->getRequired());
    }

    public function deleteCustomFieldCallsRepositoryDelete(): void
    {
        $model = new CustomField();

        /** @var CustomFieldRepository&m\MockInterface $repository */
        $repository = m::mock(CustomFieldRepository::class);
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteCustomField($model);
    }
}
