<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * Related logic: see ...src\Invoice\Product\ProductController function view_partial_product_image.
 *
 * @var App\Invoice\Product\ImageAttachForm    $form
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView                   $this
 * @var string                                 $actionName
 * @var string                                 $csrf
 * @var bool                                   $invEdit
 * @var bool                                   $invView
 * @var string                                 $partial_product_image_info
 * @var string                                 $partial_product_image_list
 *
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
if ($invEdit && $invView) {
    $this->setTitle($translator->translate('add.files'));
}
?>

<div class="panel panel-default no-margin">
    <div class="panel-heading">
        <i tooltip="data-bs-toggle" title="<?php echo $s->isDebugMode(8); ?>"><?php echo $translator->translate('productimage.upload'); ?></i>
    </div>
    <div class="panel-body clearfix">
        <div class="container">
            <?php if ($invView && $invEdit) { ?> 
            <?php echo $partial_product_image_info; ?>
            <?php echo Html::openTag('div', ['class' => 'row']); ?>
                <div>
                    <div>
                        <h5><?php echo Html::encode($this->getTitle()); ?></h5>
                    </div>
                    <div>
                        <?php echo Form::tag()
                            ->post($urlGenerator->generate($actionName, $actionArguments))
                            ->enctypeMultipartFormData()
                            ->csrf($csrf)
                            ->id('ImageAttachForm')
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
                                    'type'  => 'submit',
                                    'class' => 'btn btn-sm btn-primary',
                                    'name'  => 'contact-button',
                                ],
                            ]); ?>
                        <?php echo Form::tag()->close(); ?>
                    </div>
                </div>
            </div>
            <?php } ?>
            <?php if ($invView) { ?>
            <?php echo Html::openTag('div', ['class' => 'row']); ?>
                <?php echo $partial_product_image_list; ?>
            </div>
            <?php } ?>
        </div>

    </div>
</div>
