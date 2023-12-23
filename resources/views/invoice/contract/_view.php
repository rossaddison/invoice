<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>
<?= Field::text($form, 'client_id')->readonly(true);?>    
<?= Field::text($form, 'reference')->readonly(true);?>
<?= Field::text($form, 'name')->readonly(true);?>
<?= Field::text($form, 'period_start')
    ->value(
        Html::encode(Html::encode( $datehelper->get_or_set_with_style($form->getPeriod_start() ?? new DateTimeImmutable('now'))))    
    )->readonly(true);?>
<?= Field::text($form, 'period_end')
    ->value(
        Html::encode(Html::encode( $datehelper->get_or_set_with_style($form->getPeriod_end() ?? new DateTimeImmutable('now'))))    
    )->readonly(true);?>

