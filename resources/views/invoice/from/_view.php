<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
* @var \Yiisoft\View\View $this
* @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
* @var \Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var string $csrf
* @var string $action
* @var string $title
*/

?>
<h1><?= Html::encode($title) ?></h1>
<div class="row">
 <div class="row mb3 form-group">
<label for="email" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $s->trans('email'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['email'] ?? ''); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="include" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $translator->translate('invoice.email.include'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['include'] == '1' ? $s->trans('yes') : $s->trans('no')); ?></label>
 </div>
 <div class="row mb3 form-group">
<label for="default_email" class="text-bg col-sm-2 col-form-label " style="background:lightblue"><?= $translator->translate('invoice.email.default'); ?></label>
<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body['default_email'] == '1' ? $s->trans('yes') : $s->trans('no')); ?></label>
 </div>
</div>
