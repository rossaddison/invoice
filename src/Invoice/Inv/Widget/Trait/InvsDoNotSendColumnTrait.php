<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Widget\Trait;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Enum\DoNotSendReason;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/**
 * HomeCare do-not-send read-only badge, extracted from
 * InvsColumnBuilder::buildColumns() to stay within S1448 (≤ 20 methods) —
 * same technique already used for InvsWorkerColumnTrait/
 * InvsCategorySecondaryRunColumnTrait.
 *
 * Read-only by design: only the field worker sets this, from inv/guest
 * (see Trait\Guest::setDoNotSend()) — a manager never edits it from here.
 *
 * @property-read \Yiisoft\Translator\TranslatorInterface $translator
 */
trait InvsDoNotSendColumnTrait
{
    private function buildDoNotSendColumn(bool $homeCareEnabled): DataColumn
    {
        $t = $this->translator;
        return new DataColumn(
            header: $t->translate('do.not.send'),
            content: static function (Inv $model) use ($t): string {
                if (!$model->getDoNotSend()) {
                    return '';
                }
                $reason = DoNotSendReason::tryFrom($model->getDoNotSendReason());
                $label = $reason !== null
                    ? $t->translate('do.not.send.reason.' . $reason->value)
                    : '';
                return '<span class="badge bg-danger" data-bs-toggle="tooltip" title="'
                    . Html::encode($label) . '">🚫 ' . Html::encode($label) . '</span>';
            },
            encodeContent: false,
            visible: $homeCareEnabled,
            withSorting: false,
        );
    }
}
