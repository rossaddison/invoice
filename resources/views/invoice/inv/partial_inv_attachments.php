<?php

declare(strict_types=1);

use Yiisoft\Form\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\View\WebView;

/**
 * @var Yiisoft\Yii\View\Csrf $csrf
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var WebView $this
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 */
 if ($invEdit && $invView) { 
    $this->setTitle($s->trans('add_files'));
 }
 ?>

<div class="panel panel-default no-margin">
    <div class="panel-heading">
        <i tooltip="data-bs-toggle" title="<?= $s->isDebugMode(5);?>"><?= $s->trans('attachments'); ?></i>
    </div>
    <div class="panel-body clearfix">
        <div class="container">
            <?php if ($invView && $invEdit) { ?> 
            <div class="row">
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
            <div class="row">
                <?= $partial_inv_attachments_list; ?>
            </div>
            <?php } ?>
        </div>

    </div>
</div>
