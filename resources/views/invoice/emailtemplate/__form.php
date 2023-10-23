<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Yii\View\ViewRenderer $viewRenderer
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var array $errors
 * @var string $title
 */

$this->addJsFiles($assetManager->getJsFiles());

if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}
?>

<form id="emailtemplateForm" method="POST" action="<?= $urlGenerator->generate(...$action) ?>" enctype="multipart/form-data" >
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
  <div class="row">
    <div class="mb-3 form-group">
        <input type="text" class="form-control" name="email_template_title" id="email_template_title" placeholder="<?= $s->trans('title'); ?>" value="<?= Html::encode($body['email_template_title'] ?? '') ?>" required>
    </div>
    <div class="mb-3 form-group">
        <label for="email_template_type" class="control-label"><?= $s->trans('type'); ?></label>
        
        <div class="radio">
            <label>
                <input type="radio" name="email_template_type" id="email_template_type_invoice"
                       value="invoice" <?= Html::encode(($body['email_template_type'] ?? 'invoice') == 'invoice' ? 'checked' : ''); ?>>
                <?= $s->trans('invoice'); ?>
            </label>
        </div>
        <div class="radio">
            <label>
                <input type="radio" name="email_template_type" id="email_template_type_quote"
                       value="quote" <?= Html::encode(($body['email_template_type'] ?? 'invoice')== 'quote' ? 'checked' : ''); ?>>
                <?= $s->trans('quote'); ?>
            </label>
        </div>
    </div>
    <div class="mb-3 form-group">
    <label for="email_template_from_name"><?= $s->trans('from_name'); ?></label>    
                    <input type="text" name="email_template_from_name" id="email_template_from_name"
                           class="form-control taggable" placeholder="<?= $s->trans('from_name'); ?>"
                           value="<?= Html::encode($body['email_template_from_name'] ?? '') ?>" required>
    </div> 
    
    <div class="panel panel-default">
    <div class="panel-heading">    
    <fieldset>
        <h5><?= $translator->translate('invoice.email.template.from.source'); ?></h5><h6><?= str_repeat("&nbsp;", 5).  $translator->translate('invoice.email.template.from.email.leave.blank'); ?></h6>
      <div>
        <!-- see  src/Invoice/Asset/rebuild-1.13/js/mailer_ajax_email_addresses -->  
        <input type="radio" id="adminEmail" name="from_email" value="<?= $admin_email; ?>" />
        <label for="adminEmail"><?= $translator->translate('invoice.email.template.from.source.admin.email'); ?></label>
        <?= str_repeat("&nbsp;", 2); ?>
        <input type="radio" id="senderEmail" name="from_email" value="<?= $sender_email; ?>" />
        <label for="senderEmail"><?= $translator->translate('invoice.email.template.from.source.sender.email'); ?></label>
        <?= str_repeat("&nbsp;", 2); ?>
        <input type="radio" id="fromEmail" name="from_email" value="<?= $from_email; ?>" />
        <label for="fromEmail"><?= $translator->translate('invoice.email.template.from.source.froms.email'); ?></label>
      </div>
    </fieldset>
      
    <div id="email_option">
        <div class="mb-3 form-group">
            <label for="email_template_from_email"></label>
            <input class="form-control" type="text" id="email_template_from_email" name="email_template_from_email" value="<?= $body['email_template_from_email'] ?? ''; ?>">
        </div>
    </div>    
    </div>
    </div>
      
    <div class="mb-3 form-group">
                    <input type="text" name="email_template_bcc" id="email_template_bcc" class="form-control taggable" placeholder="<?= $s->trans('bcc'); ?>"
                           value="<?= Html::encode($body['email_template_bcc'] ?? '') ?>">
    </div>

    <div class="mb-3 form-group">
                    <input type="text" name="email_template_subject" id="email_template_subject"
                           class="form-control taggable" placeholder="<?= $s->trans('subject'); ?>"
                           value="<?= Html::encode($body['email_template_subject'] ?? '') ?>">
    </div>

    <div class="mb-3 form-group">
                    <select name="email_template_pdf_template" id="email_template_pdf_template"
                            class="form-control">
                        <option value=""><?= $s->trans('pdf_template'); ?></option>

                        <optgroup label="<?= $s->trans('invoices'); ?>">
                            <?php foreach ($invoice_templates as $template): ?>
                                <option class="hidden-invoice" value="<?= $template; ?>"
                                    <?php $s->check_select($body['email_template_pdf_template'] ?? $selected_pdf_template, $template); ?>>
                                    <?= ucfirst($template); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>

                        <optgroup label="<?= $s->trans('quotes'); ?>">
                            <?php foreach ($quote_templates as $template): ?>
                                <option class="hidden-quote" value="<?= $template; ?>"
                                    <?php $s->check_select($body['email_template_pdf_template'] ?? $selected_pdf_template, $template); ?>>
                                    <?= ucfirst($template); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
    </div>
    <div class="mb-3 form-group">
                            <br>
                            <div class="html-tags btn-group btn-group-sm">
                                <span class="html-tag btn btn-default" data-tag-type="text-paragraph">
                                    <i class="fa fa-fw fa-paragraph"></i>
                                </span>
                                <span class="html-tag btn btn-default" data-tag-type="text-linebreak">
                                    &lt;br&gt;
                                </span>
                                <span class="html-tag btn btn-default" data-tag-type="text-bold">
                                    <i class="fa fa-fw fa-bold">b</i>
                                </span>
                                <span class="html-tag btn btn-default" data-tag-type="text-italic">
                                    <i class="fa fa-fw fa-italic"></i>
                                </span>
                            </div>
                            <div class="html-tags btn-group btn-group-sm">
                                <span class="html-tag btn btn-default" data-tag-type="text-h1">H1</span>
                                <span class="html-tag btn btn-default" data-tag-type="text-h2">H2</span>
                                <span class="html-tag btn btn-default" data-tag-type="text-h3">H3</span>
                                <span class="html-tag btn btn-default" data-tag-type="text-h4">H4</span>
                            </div>
                            <div class="html-tags btn-group btn-group-sm">
                                <span class="html-tag btn btn-default" data-tag-type="text-code">
                                    <i class="fa fa-fw fa-code"></i>
                                </span>
                                <span class="html-tag btn btn-default" data-tag-type="text-hr">
                                    &lt;hr/&gt;
                                </span>
                                <span class="html-tag btn btn-default" data-tag-type="text-css">
                                    CSS
                                </span>
                            </div>

                            <textarea name="email_template_body" id="email_template_body" rows="8"
                                      class="email-template-body form-control taggable"><?= Html::encode($body['email_template_body'] ?? '') ?>
                            </textarea>

                            <br>

                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <?= $s->trans('preview'); ?>
                                    <span id="email-template-preview-reload" class="pull-right cursor-pointer">
                                        <i class="fa fa-refresh"></i>
                                    </span>
                                </div>
                                <div class="panel-body">
                                    <iframe id="email-template-preview"></iframe>
                                </div>
                            </div>

    </div>
    <div class="mb-3 form-group">
       <?=
            $email_template_tags;
       ?>  
    </div>
  </div>
  <button type="submit" class="btn btn-primary">Submit</button>
</form>