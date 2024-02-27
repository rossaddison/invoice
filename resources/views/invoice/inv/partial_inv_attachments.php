<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\View\WebView;

/**
 * @var Yiisoft\Yii\View\Csrf $csrf
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var WebView $this
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 */
 if ($invEdit && $invView) { 
    $this->setTitle($translator->translate('i.add_files'));
 }
 ?>

<div class="panel panel-default no-margin">
    <div class="panel-heading">
        <?= I::tag()
            ->addClass('bi bi-info-circle')
            ->addAttributes([
                'tooltip' => 'data-bs-toggle', 
                'title' => $s->isDebugMode(5)
            ])
            ->content(' '.$translator->translate('i.attachments')); 
        ?>
    </div>
    <div class="panel-body clearfix">
        <div class="container">
            <?php if ($invView && $invEdit) { ?> 
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <div>
                    <div>
                        <h5><?= Html::encode($this->getTitle()) ?></h5>
                    </div>
                    <div>
                        <?= Form::tag()
                            ->post($urlGenerator->generate(...$action))
                            ->enctypeMultipartFormData()
                            ->csrf($csrf)
                            ->id('InvAttachmentsForm')
                            ->open()
                        ?>
                        <?= Field::file($form, 'attachFile')
                            ->containerClass('mb-3')
                            ->hideLabel()
                        ?>
                    </div>
                    <div>
                        <?= Field::buttonGroup()
                            ->addContainerClass('btn-group')
                            ->buttonsData([
                                [
                                    $translator->translate('layout.reset'),
                                    'type' => 'reset',
                                    'class' => 'btn btn-sm btn-danger',
                                ],
                                [
                                    $translator->translate('layout.submit'),
                                    'type' => 'submit',
                                    'title' => 'actions: inv/view_partial_inv_attachments and inv/attachment',
                                    'tooltip' => 'data-bs-toggle',
                                    'class' => 'btn btn-sm btn-primary',
                                    'name' => 'contact-button',
                                ],
                            ]) ?>
                        <?= Form::tag()->close() ?>
                    </div>
                </div>
            </div>
            <?php } ?>
            <?php if ($invView) { ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= $partial_inv_attachments_list; ?>
            </div>
            <?php } ?>
        </div>

    </div>
</div>
