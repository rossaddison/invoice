<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}
?>
<h1><?= Html::encode($title) ?></h1>
<form id="ProductImageForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div id="headerbar">
        <h1 class="headerbar-title"><?= $translator->translate('invoice.productimage.form'); ?></h1>
        <?php $response = $head->renderPartial('invoice/layout/header_buttons', ['s' => $s, 'hide_submit_button' => false, 'hide_cancel_button' => false]); ?>
        <?php echo (string) $response->getBody(); ?><div id="content">
            <div class="row">
                <div class="mb3 form-group">
                    <label for="product_id">Product</label>
                    <select name="product_id" id="product_id" readonly class="form-control">
                        <option value=""><?= $s->trans('product'); ?></option>
                        <?php foreach ($products as $product) { ?>
                            <option value="<?= $product->getProduct_id(); ?>"
                            <?php $s->check_select(Html::encode($body['product_id'] ?? ''), $product->getProduct_id()) ?>
                                    ><?= $product->getProduct_name(); ?></option>
                                <?php } ?>
                    </select>
                </div>
                <div class="mb3 form-group">
                    <input type="hidden" name="id" id="id" class="form-control"
                           value="<?= Html::encode($body['id'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="file_name_original"><?= $translator->translate('invoice.upload.filename.original'); ?></label>
                    <input type="text" name="file_name_original" id="file_name_original" class="form-control"
                           value="<?= Html::encode($body['file_name_original'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="file_name_new"><?= $translator->translate('invoice.upload.filename.new'); ?></label>
                    <input type="text" name="file_name_new" id="file_name_new" class="form-control"
                           value="<?= Html::encode($body['file_name_new'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="description"><?= $translator->translate('invoice.upload.description'); ?></label>
                    <input type="text" name="description" id="description" class="form-control"
                           value="<?= Html::encode($body['description'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label form-label for="uploaded_date"><?= $translator->translate('invoice.upload.date') . " (" . $datehelper->display() . ") "; ?></label>
                    <input type="text" name="uploaded_date" id="uploaded_date" placeholder="<?= $datehelper->display(); ?>"
                           class="form-control input-sm datepicker"
                           value="<?= Html::encode($datehelper->date_from_mysql($body['uploaded_date'] ?? new DateTimeImmutable('now'))); ?>">
                </div>
            </div>
        </div>

    </div>


</form>