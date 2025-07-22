<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;

/**
 * @var App\Invoice\Inv\InvAttachmentsForm     $form
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var bool                                   $invEdit
 * @var bool                                   $invView
 * @var string                                 $actionName
 * @var string                                 $csrf
 * @var string                                 $partial_inv_attachments_list
 * @var string                                 $title
 *
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
if ($invEdit && $invView) {
    $title = $translator->translate('add.files');
}
?>

<div class="panel panel-default no-margin">
    <div class="panel-heading">
        <b>
            <?php echo I::tag()
                ->addClass('bi bi-info-circle')
                ->addAttributes([
                    'tooltip' => 'data-bs-toggle',
                    'title'   => $s->isDebugMode(5),
                ])
                ->content(' '.$translator->translate('attachments'));
?>
        </b>    
    </div>
    <div class="panel-body clearfix">
        <div class="container">
            <?php if ($invView && $invEdit) { ?> 
            <?php echo Html::openTag('div', ['class' => 'row']); ?>
                <div>
                    <div>
                        <h5><?php echo Html::encode($title); ?></h5>
                    </div>
                    <div>
                        <?php echo Form::tag()
                            ->post($urlGenerator->generate($actionName, $actionArguments))
                            ->enctypeMultipartFormData()
                            ->csrf($csrf)
                            ->id('InvAttachmentsForm')
                            ->open();
                ?>
                        <?php echo Field::file($form, 'attachFile')
                    ->containerClass('mb-3')
                    ->hideLabel();
                ?>
                    </div>
                    <div>
                        <?php echo Field::buttonGroup()
                            ->addContainerClass('btn-group')
                            ->buttonsData([
                                [
                                    $translator->translate('layout.reset'),
                                    'type'  => 'reset',
                                    'class' => 'btn btn-sm btn-danger',
                                ],
                                [
                                    $translator->translate('layout.submit'),
                                    'type'    => 'submit',
                                    'title'   => 'actions: inv/view_partial_inv_attachments and inv/attachment',
                                    'tooltip' => 'data-bs-toggle',
                                    'class'   => 'btn btn-sm btn-primary',
                                    'name'    => 'contact-button',
                                ],
                            ]); ?>
                        <?php echo Form::tag()->close(); ?>
                    </div>
                </div>
            </div>
            <?php } ?>
            <?php if ($invView) { ?>
            <?php echo Html::openTag('div', ['class' => 'row']); ?>
                <?php echo $partial_inv_attachments_list; ?>
            </div>
            <?php } ?>
        </div>

    </div>
</div>
