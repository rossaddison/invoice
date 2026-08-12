<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Dwelling\DwellingRepository as DwR;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/**
 * HomeCare house-number column — Inv → Client → Dwelling, not the InvItem/Product/Family chain
 * {@see InvsCategorySecondaryRunColumnTrait} walks. Extracted into its own trait for the same reason
 * (SonarQube S1448, ≤ 20 methods per class) and following its exact shape: a chain-walking accessor
 * already living on Inv ({@see Inv::getClientDwellingId()}), then a repository lookup here to turn the
 * id into a display value.
 *
 * @property-read \Yiisoft\Translator\TranslatorInterface $translator
 */
trait InvsDwellingHouseNumberColumnTrait
{
    private function buildDwellingHouseNumberColumn(DwR $dwR, bool $homeCareEnabled): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            property: 'filterDwellingHouseNumber',
            header: $t->translate('dwelling.house.number'),
            content: static function (Inv $model) use ($dwR): string {
                $dwellingId = $model->getClientDwellingId();
                if ($dwellingId === null) {
                    return '';
                }
                $dwelling = $dwR->repoDwellingQuery($dwellingId);
                return Html::encode($dwelling?->getHouseNumberDisplay() ?? '');
            },
            encodeContent: false,
            withSorting: false,
            visible: $homeCareEnabled,
        );
    }
}
