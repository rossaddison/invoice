<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\Entity\PostalAddress $postalAddress
 * @var App\Invoice\PostalAddress\PostalAddressForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */

?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('PostalAddressForm')
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
    <?php echo $button::back(); ?>
    <?php echo Html::openTag('div', ['id' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::hidden($form, 'id')
        ->hideLabel(); ?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::hidden($form, 'client_id')
        ->hideLabel(); ?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'street_name')
        ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'additional_street_name')
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'building_number')
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'city_name')
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'postalzone')
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'countrysubentity')
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Field::text($form, 'country')
    ->disabled(true);
?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>
