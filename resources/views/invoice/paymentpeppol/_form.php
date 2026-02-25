<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Bootstrap5\Alert;
use Yiisoft\Bootstrap5\AlertVariant;

/**
 * @var Yiisoft\DataResponse\DataStream\DataStream $dataStream
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */

if (!empty($errors)) {
    /**
     * @var string $field
     * @var string $error
     */
    foreach ($errors as $field => $error) {
        echo Alert::widget()
                ->variant(AlertVariant::DANGER)
                ->body($field . ':' . $error, true)
                ->dismissable(true)
                ->render();
    }
}

?>
<?= Html::openTag('h1'); ?><?= Html::encode($title) ?><?= Html::closeTag('h1'); ?>
<form id="PaymentPeppolForm" method="POST" action="<?= $urlGenerator->generate($actionName, $actionArguments); ?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('paymentpeppols.form'); ?></h1>
    <?= (string) $dataStream->getData(); ?>
    <div id="content">
        <div class = 'row'>
           <div class="mb3 form-group">
             <input type="hidden" name="id" id="id" class="form-control"
           value="<?= Html::encode($body['id'] ??  ''); ?>">
           </div>
           <div class="mb3 form-group">
             <label for="auto_reference"><?= $translator->translate('auto.reference'); ?></label>
             <input type="text" name="auto_reference" id="auto_reference" class="form-control"
           value="<?= Html::encode($body['auto_reference'] ??  ''); ?>">
           </div>
           <div class="mb3 form-group">
             <label for="provider"><?= $translator->translate('provider'); ?></label>
             <input type="text" name="provider" id="provider" class="form-control"
           value="<?= Html::encode($body['provider'] ??  ''); ?>">
           </div>
       </div>
    </div>
</div>
</form>
