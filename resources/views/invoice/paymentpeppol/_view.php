<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 * @psalm-var array<string,list<string>> $errors
 */

if (!empty($errors)) {
    /**
     * @var string $field
     * @var string $error
     */
    foreach ($errors as $field => $error) {
        echo Alert::widget()
             ->type(AlertType::DANGER)
             ->addClass('shadow')
             ->dismissable(true)   
             ->body($field . ':' . $error, true);
    }
}

?>
<h1><?= Html::encode($title) ?></h1>
<div class='row'>
    <div class="row mb3 form-group">
        <label for="auto_reference" class="text-bg col-sm-2 col-form-label " style="background:lightblue">
            <?= $translator->translate('i.auto_reference'); ?>
        </label>
        <label class="text-bg col-sm-10 col-form-label">
            <?= Html::encode($body['auto_reference'] ?? ''); ?>
        </label>
    </div>
    <div class="row mb3 form-group">
        <label for="provider" class="text-bg col-sm-2 col-form-label " style="background:lightblue">
            <?= $translator->translate('i.provider'); ?>
        </label>
        <label class="text-bg col-sm-10 col-form-label">
            <?= Html::encode($body['provider'] ?? ''); ?>
        </label>
    </div>
</div>
