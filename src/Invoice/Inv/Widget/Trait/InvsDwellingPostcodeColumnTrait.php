<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Dwelling\DwellingPostcodeLink;
use App\Invoice\Dwelling\DwellingRepository as DwR;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/**
 * HomeCare postcode column, Inv → Client → Dwelling (see
 * {@see InvsDwellingHouseNumberColumnTrait}, whose shape this follows). The
 * postcode links to a Google Maps search in a new tab. Clients with no
 * Dwelling show an hourglass (see {@see DwellingPostcodeLink}).
 *
 * @property-read \Yiisoft\Translator\TranslatorInterface $translator
 */
trait InvsDwellingPostcodeColumnTrait
{
    private function buildDwellingPostcodeColumn(DwR $dwR, bool $homeCareEnabled): DataColumn
    {
        $pendingTitle = $this->translator->translate('dwelling.postcode.pending');
        return new DataColumn(
            property: 'filterDwellingPostcode',
            header: $this->translator->translate('dwelling.postcode'),
            content: static fn (Inv $model): string => DwellingPostcodeLink::html(
                $model->getClientDwellingId(),
                $dwR,
                $pendingTitle
            ),
            encodeContent: false,
            withSorting: false,
            visible: $homeCareEnabled,
        );
    }
}
