<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use App\Invoice\Helpers\DateHelper;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}

?>
<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['class' => 'row']); ?>
 <div class="mb3 form-group">
<label for="amount" class="form-label" style="background:lightblue"><?= $translator->translate('i.amount'); ?></label>
   <?= Html::encode($body['amount'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="vat" class="form-label" style="background:lightblue"><?= $translator->translate('i.vat'); ?></label>
   <?= Html::encode($body['vat'] ?? ''); ?>
 </div>
</div>
