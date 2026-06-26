<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;

/**
 * @var string $alert
 * @var array<int, array{form: string, property: string, validators: list<string>}> $issues
 * @var array<int, string> $formattedIssues
 * @var int $issueCount
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 */
?>

<?= $alert ?>

<?= Html::openTag('div', ['class' => 'card mb-4']); ?>
    <?= Html::openTag('div', ['class' => 'card-header bg-dark text-white d-flex justify-content-between align-items-center']); ?>
        <?= Html::openTag('h5', ['class' => 'mb-0']); ?>⚙ Common Errors — Debug Mode<?= Html::closeTag('h5'); ?>
        <?= (new A())
            ->href($urlGenerator->generate('setting/debugIndex'))
            ->addClass('btn btn-sm btn-secondary')
            ->content('← Debug Index')
            ->render(); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'card-body']); ?>

        <?= Html::openTag('div', ['class' => 'card mb-4']); ?>
            <?= Html::openTag('div', ['class' => 'card-header bg-warning text-dark']); ?>
                <?= Html::openTag('h6', ['class' => 'mb-0']); ?>
                    🔍 Form/View Consistency — Validated properties with no matching Field:: in any view
                <?= Html::closeTag('h6'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'card-body']); ?>
                <?php if ($issueCount === 0) : ?>
                    <?= Html::openTag('p', ['class' => 'text-success mb-0']); ?>✅ No issues found.<?= Html::closeTag('p'); ?>
                <?php else : ?>
                    <?= Html::openTag('p', ['class' => 'text-danger']); ?>
                        <?= Html::encode($issueCount . ' potential issue(s) — fields with #[Required] or similar validators that are never rendered in any view.'); ?>
                    <?= Html::closeTag('p'); ?>
                    <?= Html::openTag('table', ['class' => 'table table-sm table-bordered table-striped']); ?>
                        <?= Html::openTag('thead', ['class' => 'table-dark']); ?>
                            <?= Html::openTag('tr'); ?>
                                <?= Html::openTag('th'); ?>Form File<?= Html::closeTag('th'); ?>
                                <?= Html::openTag('th'); ?>Property<?= Html::closeTag('th'); ?>
                                <?= Html::openTag('th'); ?>Validators<?= Html::closeTag('th'); ?>
                            <?= Html::closeTag('tr'); ?>
                        <?= Html::closeTag('thead'); ?>
                        <?= Html::openTag('tbody'); ?>
                            <?php foreach ($issues as $issue) : ?>
                                <?= Html::openTag('tr'); ?>
                                    <?= Html::openTag('td', ['class' => 'text-danger fw-bold']); ?><?= Html::encode(basename($issue['form'])); ?><?= Html::closeTag('td'); ?>
                                    <?= Html::openTag('td', ['class' => 'font-monospace']); ?><?= Html::encode('$' . $issue['property']); ?><?= Html::closeTag('td'); ?>
                                    <?= Html::openTag('td', ['class' => 'font-monospace text-warning']); ?><?= Html::encode(implode(', ', $issue['validators'])); ?><?= Html::closeTag('td'); ?>
                                <?= Html::closeTag('tr'); ?>
                            <?php endforeach; ?>
                        <?= Html::closeTag('tbody'); ?>
                    <?= Html::closeTag('table'); ?>
                <?php endif; ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>

    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
