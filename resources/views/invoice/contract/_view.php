<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\Contract\ContractForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var int $client_id
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
    ->id('ContractForm')
    ->open();
?>

<?php echo Field::text($form, 'client_id')->readonly(true); ?>    
<?php echo Field::text($form, 'reference')->readonly(true); ?>
<?php echo Field::text($form, 'name')->readonly(true); ?>
<?php echo Field::text($form, 'period_start')
    ->value(
        Html::encode(Html::encode($form->getPeriod_start()->format('Y-m-d'))),
    )->readonly(true); ?>
<?php echo Field::text($form, 'period_end')
    ->value(
        Html::encode(Html::encode($form->getPeriod_end()->format('Y-m-d'))),
    )->readonly(true); ?>

