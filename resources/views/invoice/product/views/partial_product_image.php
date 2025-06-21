<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @see ...src\Invoice\Product\ProductController function view_partial_product_image
 * @var App\Invoice\Product\ImageAttachForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var string $actionName
 * @var string $csrf
 * @var bool $invEdit
 * @var bool $invView
 * @var string $partial_product_image_info
 * @var string $partial_product_image_list
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

if ($invEdit && $invView) {
    $this->setTitle($translator->translate('add.files'));
}
?>

<div class="panel panel-default no-margin">
    <div class="panel-heading">
        <i tooltip="data-bs-toggle" title="<?= $s->isDebugMode(8);?>"><?= $translator->translate('productimage.upload'); ?></i>
    </div>
    <div class="panel-body clearfix">
        <div class="container">
            <?php if ($invView && $invEdit) { ?> 
            <?php echo $partial_product_image_info; ?>
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <div>
                    <div>
                        <h5><?= Html::encode($this->getTitle()) ?></h5>
                    </div>
                    <div>
                        <?= Form::tag()
                           ->post($urlGenerator->generate($actionName, $actionArguments))
                           ->enctypeMultipartFormData()
                           ->csrf($csrf)
                           ->id('ImageAttachForm')
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
            <?= Html::openTag('div', ['class' => 'row']); ?>
                <?= $partial_product_image_list; ?>
            </div>
            <?php } ?>
        </div>

    </div>
</div>
