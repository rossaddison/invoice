<?php
declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */
?>

<h1><?= Html::encode($title); ?></h1>
  <?= Html::openTag('div', ['class' => 'row']); ?>
    <div class="mb-3 form-group">
        <label for="family_name" name="family_name" id="family_name" class="form-label" style="background:lightblue" value="<?= Html::encode($body['family_name'] ?? '') ?>">Family Name</label>
        <?= Html::encode($form->getFamily_name() ?? '') ?>
    </div>
  <?= Html::closeTag('div'); ?>
