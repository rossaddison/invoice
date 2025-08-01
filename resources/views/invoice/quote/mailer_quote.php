<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Quote\MailerQuoteForm $form
 * @var Yiisoft\View\View $this
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $autoTemplate
 * @var string $autoTemplate['bcc']
 * @var string $autoTemplate['body']
 * @var string $autoTemplate['cc']
 * @var string $autoTemplate['subject']
 * @var string $autoTemplate['from_name']
 * @var string $autoTemplate['from_email']
 * @var string $settingStatusPdfTemplate
 * @var string $templateTags
 * @var string $alert
 * @var string $csrf
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $dropdownTitlesOfEmailTemplates
 * @psalm-var array<array-key, array<array-key, string>|string> $pdfTemplates
 */

?>

<?php
    $js3 =
       'function parsedata(data) {' .
            'if (!data) return {};' .
            "if (typeof data === 'object') return data;" .
            "if (typeof data === 'string') return JSON.parse(data);" .
            'return {};' .
        '};' .

       "$(document).ready(function ()  {" .
       'var new_key = "";' .
       'var new_val = "";' .
       'var template_fields = ["body", "subject", "from_name", "from_email", "cc", "bcc", "pdf_template"];' .
       "$('#mailerquoteform-email_template').change(function () {" .
            'var email_template_id = $(this).val();' .
            "if (email_template_id === '') return;" .

            "var url =  $(location).attr('origin') + " . '"/invoice/emailtemplate/get_content/"' . '+ email_template_id;' .
            "$.ajax({ type: 'GET'," .
                'contentType: "application/json; charset=utf-8",' .
                'data: {' .
                        'email_template_id: email_template_id' .
                '},' .
                'url: url,' .
                'cache: false,' .
                "dataType: 'json'," .
                'success: function (data) {' .
                    'var response = parsedata(data);' .
                    'if (response.success === 1) {' .
                        'for (var key in response.email_template) {' .
                            'if (response.email_template.hasOwnProperty(key)) {' .
                                'new_key = key.replace("email_template_", "");' .
                                'new_val = response.email_template[key];' .
                                'switch(new_key) {' .
                                    'case "subject":' .
                                        '$("#mailerquoteform-subject.email-template-subject.form-control").val(new_val);' .
                                        'break;' .
                                    'case "body":' .
                                        '$("textarea#mailerquoteform-body.email-template-body.form-control.taggable").val(new_val);' .
                                        'break;' .
                                    'case "from_name":' .
                                        '$("#mailerquoteform-from_name.email-template-from-name.form-control").val(new_val);' .
                                        'break;' .
                                    'case "from_email":' .
                                        '$("#mailerquoteform-from_email.email-template-from-email.form-control").val(new_val);' .
                                        'break;' .
                                    'case "cc":' .
                                        '$("#mailerquoteform-cc.email-template-cc.form-control").val(new_val);' .
                                        'break;' .
                                    'case "bcc":' .
                                        '$("#mailerquoteform-bcc.email-template-bcc.form-control").val(new_val);' .
                                        'break;' .
                                    'case "pdf_template":' .
                                        '$("#mailerquoteform-pdf_template.email-template-pdf-template.form-control").val(new_val).trigger(' . "'change');" .
                                        'break;' .
                                    'default:' .
                                '}' .

                            '}' .
                        '}' .
                    '}' .
                '}' .
            '});' .
        '});' .
    '});' .

    '$(document).ready(function() {' .
        // this is the email quote window, disable the invoice select
        "$('#tags_invoice').prop('disabled', disabled);" .
        "$('#tags_quote').prop('disabled', 'false');" .
    '});';
echo Html::script($js3)->type('module');
?>

<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-8">
            <div class="card border border-dark shadow-2-strong rounded-3">
                <div class="card-header bg-dark text-white">
                    <h1 class="fw-normal h3 text-center"><?= $translator->translate('email.quote') . ' #' . ($quote->getNumber() ?? '#') . ' => ' . ($quote->getClient()?->getClient_email() ?? '#') ?></h1>
                </div>
                <div class="card-body p-5 text-center">
                    <?= Form::tag()
                        ->post($urlGenerator->generate($actionName, $actionArguments))
                        ->enctypeMultipartFormData()
                        ->csrf($csrf)
                        ->id('MailerQuoteForm')
                        ->open()
?> 
                    <?=
    $alert;
// The below panel is hidden but necessary for the emailtemplate.js to work with the quote dropdown
?>
                    <div class="panel panel-default" hidden>
                        <?= Html::tag('Label', $translator->translate('type'), ['for' => 'email_template_type', 'class' => 'control-label']) ?>
                        <?= Html::tag(
                            'Div',
                            Html::tag(
                                'Label',
                                Input::radio('email_template_type', 'invoice')
                                            ->disabled(true)
                                            ->id('email_template_type_invoice'),
                            ),
                            ['class' => 'radio'],
                        ); ?>                                            
                        <?= Html::tag(
                            'Div',
                            Html::tag(
                                'Label',
                                Input::radio('email_template_type', 'quote')
                                            ->disabled(false)
                                            ->readonly(true)
                                            ->id('email_template_type_quote')
                                            ->attribute('checked', 'checked'),
                            ),
                            ['class' => 'radio'],
                        ); ?>
                    </div>
                    <?= Html::tag('Label', $translator->translate('to.email')) ?>
                    <?= Field::email($form, 'to_email')->addInputAttributes(['value' => Html::encode($quote->getClient()?->getClient_email())])
                                                           ->hideLabel()
                                                           ->required(true); ?> 
                    
                    <?= Html::tag('Label', $translator->translate('email.template')) ?>                        
                    <?= Field::select($form, 'email_template')->optionsData($dropdownTitlesOfEmailTemplates, true, [], [])
                                                                  ->disabled(empty($dropdownTitlesOfEmailTemplates) ? true : false)
                                                                  ->hideLabel() ?>
                    
                    <?= Html::tag('Label', $translator->translate('from.name')) ?>
                    <?= Field::text($form, 'from_name')->addInputAttributes(['class' => 'email-template-from-name form-control'])
                                                           ->addInputAttributes(['value' => $autoTemplate['from_name'] ?? '' ?: Html::encode($userInv->getName())])
                                                           ->hideLabel()
                                                           ->required(true); ?>
                    
                    <?= (string) Html::tag('Label', $translator->translate('from.email')) . str_repeat("&nbsp;", 2) . ($autoTemplate['from_email'] ? $translator->translate('email.source.email.template') : $translator->translate('email.source.user.account')) ?>
                    <?= Field::email($form, 'from_email')->addInputAttributes(['value' => $autoTemplate['from_email'] ?? '' ?: Html::encode($userInv->getUser()?->getEmail())])->hideLabel()
                                                             ->addInputAttributes(['class' => 'email-template-from-email form-control'])
                                                             ->required(true); ?>                            
                    
                    <?= Html::tag('Label', $translator->translate('cc')) ?>
                    <?= Field::text($form, 'cc')->addInputAttributes(['class' => 'email-template-cc form-control'])
                                                    ->addInputAttributes(['value' => $autoTemplate['cc'] ?? '' ])
                                                    ->hideLabel()?>
                    
                    <?= Html::tag('Label', $translator->translate('bcc')) ?>
                    <?= Field::email($form, 'bcc')->addInputAttributes(['class' => 'email-template-bcc form-control'])
                                                      ->addInputAttributes(['value' => $autoTemplate['bcc'] ?? '' ])
                                                      ->hideLabel()?>
                                        
                    <?= Html::tag('Label', $translator->translate('subject')) ?>
                    <?= Field::text($form, 'subject')->addInputAttributes(['id' => 'mailerquoteform-subject'])
                                                         ->addInputAttributes(['class' => 'email-template-subject form-control'])
                                                         ->addInputAttributes(['value' => $autoTemplate['subject'] ?? '' ?: $translator->translate('quote') . '#' . ($quote->getNumber() ?? '#')])
                                                         ->hideLabel()
                                                         ->required(true); ?>
                    
                    <?= Html::tag('Label', $translator->translate('pdf.template')) ?>
                    <?= Field::select($form, 'pdf_template')->optionsData($pdfTemplates, true, [], [])
                                                                ->disabled(empty($pdfTemplates) ? true : false)
                                                                ->addInputAttributes(['class' => 'email-template-pdf-template form-control'])
                                                                ->addInputAttributes(['value' => $settingStatusPdfTemplate ?: ucfirst('invoice')])
                                                                ->hideLabel()?>
                    
                    <?= Html::tag('Label', $translator->translate('body')) ?>
                    
                    <?= Field::textarea($form, 'body')->addInputAttributes(['id' => 'mailerquoteform-body'])
                                                      ->addInputAttributes(['class' => 'email-template-body form-control taggable'])
                                                      ->addInputAttributes(['style' => 'height: 300px'])
                                                      ->maxlength(1500)
                                                      ->rows(120)
                                                      ->wrap('hard')
                                                      ->hideLabel()
?>
                    
                    <div class="html-tags btn-group btn-group-sm">
                        <span class="html-tag btn btn-default" data-tag-type="text-paragraph">
                            <i class="fa fa-paragraph"></i>
                        </span>
                        <span class="html-tag btn btn-default" data-tag-type="text-linebreak">
                            &lt;br&gt;
                        </span>
                        <span class="html-tag btn btn-default" data-tag-type="text-bold">
                            <i class="fa fa-bold"></i>
                        </span>
                        <span class="html-tag btn btn-default" data-tag-type="text-italic">
                            <i class="fa fa-italic"></i>
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
                            <i class="fa fa-code"></i>
                        </span>
                        <span class="html-tag btn btn-default" data-tag-type="text-hr">
                            &lt;hr/&gt;
                        </span>
                        <span class="html-tag btn btn-default" data-tag-type="text-css">
                            CSS
                        </span>
                    </div>
                    
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <?= $translator->translate('preview'); ?>
                            <div id="email-template-preview-reload" class="pull-right cursor-pointer">
                                <i class="fa fa-refresh"></i>
                            </div>
                        </div>
                        <div class="panel-body">
                            <iframe id="email-template-preview"></iframe>
                        </div>
                    </div>
                    
                    <div>
                        <?php echo $templateTags ?>
                    </div>                    
                    
                    <?= Field::file($form, 'attachFiles[]')
    ->containerClass('mb-3')
    ->multiple()
    ->hideLabel()
?>                   
                    <div>
                    <div class="form-group"><?= Html::tag('Label', $translator->translate('guest.url'), ['for' => 'quote-guest-url']); ?></label>
                        <div class="input-group">
                        <?=
        Field::text($form, 'guest_url')->readonly(true)
                                      ->addInputAttributes(['id' => 'quote-guest-url','readonly' => 'true',
                                          'value' => $urlGenerator->generate(
                                              'quote/url_key',
                                              ['url_key' => $quote->getUrl_key()],
                                          ),'class' => 'form-control']);

echo Html::tag(
    'Div',
    Html::tag('i', '', ['class' => 'fa fa-clipboard fa-fw']),
    ['class' => 'input-group-text to-clipboard cursor-pointer',
        'data-clipboard-target' => '#quote-guest-url','style' => 'height : 38px'],
);
?>
                        </div>
                    </div>
                    </div>
                    <?= Field::buttonGroup()
->addContainerClass('btn-group btn-toolbar float-end')
->buttonsData([
    [
        $translator->translate('cancel'),
        'type' => 'reset',
        'class' => 'btn btn-lg btn-danger',
        'name' => 'btn_cancel',
    ],
    [
        $translator->translate('send'),
        'type' => 'submit',
        'class' => 'btn btn-lg btn-primary',
        'name' => 'btn_send',
    ],
]) ?>
                    <?= Form::tag()->close(); ?>                    
                </div>                
            </div>
        </div>
    </div>
</div>
<?php
$js9 = "$(document).ready(function() {" .
        'var textContent = ' . $autoTemplate['body'] . ';' .
        '$("#mailerquoteform-body").val(textContent);' .
        '});';
echo Html::script($js9)->type('module');
?>

