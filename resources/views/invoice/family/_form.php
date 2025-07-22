<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\Family\FamilyForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $categoryPrimaries
 * @psalm-var array<array-key, array<array-key, string>|string> $categorySecondaries
 *
 */
?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('FamilyForm')
    ->open(); ?>

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
  <?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
    <?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
      <?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
        <?php echo Html::openTag('div', ['class' => 'card-header']); ?>
          <?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
            <?php echo Html::encode($title); ?>
          <?php echo Html::closeTag('h1'); ?>
          <?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
            <?php echo $button::backSave(); ?>
            <?php echo Html::openTag('div', ['id' => 'content']); ?>
              <?php echo Html::openTag('div', ['class' => 'row']); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?php echo Field::errorSummary($form)
                ->errors($errors)
                ->header($translator->translate('error.summary'))
                ->onlyProperties(...['family_name', 'category_primary_id', 'category_secondary_id'])
                ->onlyCommonErrors();
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?php echo Field::text($form, 'family_name')
    ->label($translator->translate('family.name'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('family.name'),
        'value'       => Html::encode($form->getFamily_name() ?? ''),
        'class'       => 'form-control',
        'id'          => 'family_name',
    ])
    ->hint($translator->translate('hint.this.field.is.required'));
?>
                <?php echo Html::closeTag('div'); ?>  
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?php echo Field::select($form, 'category_primary_id')
                    ->label($translator->translate('category.primary'))
                    ->addInputAttributes([
                        'class' => 'form-control  alert alert-warning',
                    ])
                    ->value($form->getCategory_primary_id())
                    ->prompt($translator->translate('none'))
                    ->optionsData($categoryPrimaries);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                  <?php echo Field::select($form, 'category_secondary_id')
    ->label($translator->translate('category.secondary'))
    ->addInputAttributes([
        'class' => 'form-control  alert alert-warning',
    ])
    ->value($form->getCategory_secondary_id())
    ->prompt($translator->translate('none'))
    ->optionsData($categorySecondaries);
?>
                <?php echo Html::closeTag('div'); ?>
              <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
          <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
      <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
  <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>

<?php echo Form::tag()->close(); ?>