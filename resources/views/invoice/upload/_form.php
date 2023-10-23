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
<form id="UploadForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
    <div id="headerbar">
        <h1 class="headerbar-title"><?= $s->trans('uploads_form'); ?></h1>
        <?php $response = $head->renderPartial('invoice/layout/header_buttons', ['s' => $s, 'hide_submit_button' => false, 'hide_cancel_button' => false]); ?>
        <?php echo (string) $response->getBody(); ?><div id="content">
            <div class="row">
                <div class="mb3 form-group">
                    <label for="client_id">Client</label>
                    <select name="client_id" id="client_id" readonly class="form-control">
                        <option value=""><?= $s->trans('client'); ?></option>
                        <?php foreach ($clients as $client) { ?>
                            <option value="<?= $client->getClient_id(); ?>"
                            <?php $s->check_select(Html::encode($body['client_id'] ?? ''), $client->getClient_id()) ?>
                                    ><?= $client->getClient_name(); ?></option>
                                <?php } ?>
                    </select>
                </div>
                <div class="mb3 form-group">
                    <input type="hidden" name="id" id="id" class="form-control"
                           value="<?= Html::encode($body['id'] ?? ''); ?>">
                </div>
                <div class="mb3 form-group">
                    <label for="url_key"><?= $translator->translate('invoice.upload.url.key'); ?></label>
                    <input type="text" name="url_key" id="url_key" class="form-control"
                           value="<?= Html::encode($body['url_key'] ?? ''); ?>">
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

</div>
</form>