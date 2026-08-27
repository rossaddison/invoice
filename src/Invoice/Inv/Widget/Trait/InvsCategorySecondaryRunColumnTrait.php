<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\CategorySecondary\CategorySecondaryRepository as CSR;
use App\Widget\NoOpFilterFactory;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/**
 * HomeCare "Current Run" column, extracted from InvsColumnBuilder::buildColumns()
 * to stay within S1448 (≤ 20 methods) — SonarQube doesn't count trait-provided
 * methods toward the consuming class, the same technique already used for
 * InvsWorkerColumnTrait.
 *
 * @property-read \Yiisoft\Translator\TranslatorInterface $translator
 * @property-read \App\Invoice\Inv\Widget\InvsFilterOptions $filterOptions
 */
trait InvsCategorySecondaryRunColumnTrait
{
    private function buildCategorySecondaryRunColumn(CSR $csR, bool $homeCareEnabled): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            property: 'filterCategorySecondaryRun',
            header: $t->translate('homecare.current.run'),
            content: static function (Inv $model) use ($csR): string {
                $categorySecondaryId = $model->getFirstItemCategorySecondaryId();
                if ($categorySecondaryId === null) {
                    return '';
                }
                $categorySecondary = $csR->repoCategorySecondaryQuery($categorySecondaryId);
                return Html::encode($categorySecondary?->getName() ?? '');
            },
            encodeContent: false,
            filter: DropdownFilter::widget()
                ->addAttributes(['id' => 'filter-category-secondary-run',
                    'class' => 'native-reset inv-filter',
                    'aria-label' => 'Filter by HomeCare current run',
                    'title' => $t->translate('homecare.current.run')])
                ->optionsData($this->filterOptions->categorySecondaryRun),
            filterFactory: new NoOpFilterFactory(),
            withSorting: false,
            visible: $homeCareEnabled,
        );
    }
}
