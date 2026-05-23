<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Profile\ProfileForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $companies
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<?=  new Form()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('ProfileForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                    <?= Field::checkbox($form, 'current')
                        ->label($translator->translate('profile.property.label.current'))
                        ->inputLabelAttributes(['class' => 'form-check-label'])
                        ->disabled(true)
                        ->inputClass('form-check-input')
                        ->ariaDescribedBy($translator->translate('active'))
                ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                     <?php
                        $optionsDataCompany = [];
                        /**
                         * @var App\Infrastructure\Persistence\Company\Company $company
                         */
                        foreach ($companies as $company) {
                            $companyId = (string) $company->reqId();
                            $companyName = $company->getName();
                            if (strlen($companyId) > 0 && null !== $companyName) {
                                $optionsDataCompany[$companyId] = $companyName;
                            }
                        }
                    ?>
                    <?= Field::select($form, 'company_id')
                        ->label($translator->translate('profile.property.label.company'))    
                        ->optionsData($optionsDataCompany)
                        ->disabled(true);
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                    <?= Field::telephone($form, 'mobile')
                        ->label($translator->translate('profile.property.label.mobile'))                               ->disabled(true);
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                    <?= Field::email($form, 'email')
                        ->label($translator->translate('profile.property.label.email'))                                ->disabled(true);
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                    <?= Field::text($form, 'description')
                        ->label($translator->translate('profile.property.label.description'))                    
                        ->disabled(true);
                    ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?=  new Form()->close() ?>

