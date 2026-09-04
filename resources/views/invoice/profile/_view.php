<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

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

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-body']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                <?php
                    $companyName = '';
/**
 * @var App\Infrastructure\Persistence\Company\Company $company
 */
foreach ($companies as $company) {
    if ($company->reqId() === $form->getCompanyId()) {
        $companyName = $company->getName() ?? '';
        break;
    }
}
?>
                <?php
    ReadOnlyField::render(
        $translator->translate('profile.property.label.current'),
        $translator->translate($form->getCurrent() === 1 ? 'yes' : 'no'),
    );
ReadOnlyField::render(
    $translator->translate('profile.property.label.company'),
    $companyName,
);
ReadOnlyField::render(
    $translator->translate('profile.property.label.mobile'),
    $form->getMobile(),
);
ReadOnlyField::render(
    $translator->translate('profile.property.label.email'),
    $form->getEmail(),
);
ReadOnlyField::render(
    $translator->translate('profile.property.label.description'),
    $form->getDescription(),
);
?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
