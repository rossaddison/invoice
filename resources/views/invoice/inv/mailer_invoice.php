<?php

declare(strict_types=1);

use Yiisoft\Form\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Input;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 */

?>

<?php
    $js2 = 
       'function parsedata(data) {'.             
            'if (!data) return {};'.
            "if (typeof data === 'object') return data;".
            "if (typeof data === 'string') return JSON.parse(data);".
            'return {};'.
        '};'.
            
       "$(document).ready(function ()  {".
       'var new_key = "";'.
       'var new_val = "";'.
       'var template_fields = ["body", "subject", "from_name", "from_email", "cc", "bcc", "pdf_template"];'.
       "$('#mailerinvform-email_template').change(function () {".     
            'var email_template_id = $(this).val();'.
            "if (email_template_id === '') return;".     
            
            "var url =  $(location).attr('origin') + ".'"/invoice/emailtemplate/get_content/"'.'+ email_template_id;'.
            "$.ajax({ type: 'GET',".
                'contentType: "application/json; charset=utf-8",'.
                'data: {'.
                        'email_template_id: email_template_id'.
                '},'.
                'url: url,'.
                'cache: false,'.
                "dataType: 'json',".
                'success: function (data) {'.
                    'var response = parsedata(data);'.
                    'if (response.success === 1) {'.
                        'for (var key in response.email_template) {'.
                            'if (response.email_template.hasOwnProperty(key)) {'.
                                'new_key = key.replace("email_template_", "");'.
                                'new_val = response.email_template[key];'. 
                                'switch(new_key) {'.
                                    'case "subject":'.
                                        '$("#mailerinvform-subject.email-template-subject.form-control").val(new_val);'.
                                    'break;'.
                                    'case "body":'.                                        
                                        '$("textarea#mailerinvform-body.email-template-body.form-control.taggable").val(new_val);'.
                                    'break;'.                                   
                                    'case "from_name":'.
                                        '$("#mailerinvform-from_name.email-template-from-name.form-control").val(new_val);'.
                                    'break;'.
                                    'case "from_email":'.                                    
                                        '$("#mailerinvform-from_email.email-template-from-email.form-control").val(new_val);'.
                                    'break;'.
                                    'case "cc":'.            
                                        '$("#mailerinvform-cc.email-template-cc.form-control").val(new_val);'.
                                    'break;'.                                    
                                    'case "bcc":'.            
                                        '$("#mailerinvform-bcc.email-template-bcc.form-control").val(new_val);'.
                                    'break;'.
                                    'case "pdf_template":'.
                                        '$("#mailerinvform-pdf_template.email-template-pdf-template.form-control").val(new_val).trigger('."'change');".
                                    'break;'.    
                                    'default:'.
                                '}'. 
                                                              
                            '}'.
                        '}'.  
                    '}'.
                '}'.
            '});'.
        '});'. 
    '});'.
            
    '$(document).ready(function() {'.
        // this is the email invoice window, disable the quote select
        "$('#tags_invoice').prop('disabled', false);".
        "$('#tags_quote').prop('disabled', 'disabled');".
    '});';      
    echo Html::script($js2)->type('module');
?>

<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-8">
            <div class="card border border-dark shadow-2-strong rounded-3">
                <div class="card-header bg-dark text-white">
                    <h1 class="fw-normal h3 text-center"><?= $s->trans('email_invoice'). ' #'. $invoice->getNumber(). ' => '.$invoice->getClient()->getClient_email() ?></h1>
                </div>
                <div class="card-body p-5 text-center">
                    <?= Form::tag()
                        // $action => 'action' => ['inv/email_stage_2', ['id' => $inv_id]],
                        ->post($urlGenerator->generate(...$action))
                        ->enctypeMultipartFormData()
                        ->csrf($csrf)
                        ->id('MailerInvForm')
                        ->open()
                    ?> 
                    
                    <?= 
                        $alert;
                        // The below panel is hidden but necessary for the emailtemplate.js to work with the invoice dropdown 
                    ?>
                    
                    <div class="panel panel-default" hidden>
                        <?= Html::tag('Label',$s->trans('type'),['for'=>'email_template_type', 'class'=>'control-label']) ?>
                        <?= Html::tag('Div', 
                                Html::tag('Label',
                                Input::radio('email_template_type', 'quote')
                                        ->disabled(true)
                                        ->id('email_template_type_quote')),['class'=>'radio']); ?>                                            
                        <?= Html::tag('Div', 
                                Html::tag('Label',
                                Input::radio('email_template_type', 'invoice')
                                        ->disabled(false)
                                        ->readonly(true)
                                        ->id('email_template_type_invoice')
                                        ->attribute('checked','checked')),['class'=>'radio']); ?>
                    </div>
                    <?= Html::tag('Label',$s->trans('to_email')) ?>
                    <?= Field::email($form, 'to_email')->addInputAttributes(['value'=> Html::encode($invoice->getClient()->getClient_email())])
                                                       ->hideLabel() ?> 
                    
                    <?= Html::tag('Label',$s->trans('email_template')) ?>                        
                    <?= Field::select($form, 'email_template')->optionsData($dropdown_titles_of_email_templates, true,[],[])
                                                              ->disabled(empty($dropdown_titles_of_email_templates) ? true : false)
                                                              ->hideLabel() ?>
                    
                    <?= Html::tag('Label',$s->trans('from_name')) ?>
                    <?= Field::text($form, 'from_name')->addInputAttributes(['class'=>'email-template-from-name form-control']) 
                                                       ->addInputAttributes(['value'=> $auto_template['from_name'] ?? '' ?: (null!==$userinv ? Html::encode($userinv->getName()) : '')])->hideLabel()?>
                    
                    <?= Html::tag('Label',$s->trans('from_email')). str_repeat("&nbsp;", 2). ($auto_template['from_email'] ? $translator->translate('invoice.email.source.email.template') : $translator->translate('invoice.email.source.user.account')) ?>
                    <?= Field::email($form, 'from_email')->addInputAttributes(['value'=> $auto_template['from_email'] ?? '' ?: (null!==$userinv ? Html::encode($userinv->getEmail()) : '')  ])->hideLabel()
                                                         ->addInputAttributes(['class'=>'email-template-from-email form-control']) ?>                            
                    
                    <?= Html::tag('Label',$s->trans('cc')) ?>
                    <?= Field::text($form, 'cc')->addInputAttributes(['class'=>'email-template-cc form-control'])  
                                                ->addInputAttributes(['value'=>$auto_template['cc'] ?? '' ])
                                                ->hideLabel()?>
                    
                    <?= Html::tag('Label',$s->trans('bcc')) ?>
                    <?= Field::email($form, 'bcc')->addInputAttributes(['class'=>'email-template-bcc form-control'])
                                                  ->addInputAttributes(['value'=>$auto_template['bcc'] ?? '' ])
                                                  ->hideLabel()?>
                                        
                    <?= Html::tag('Label',$s->trans('subject')) ?>
                    <?= Field::text($form, 'subject')->addInputAttributes(['id'=>'mailerinvform-subject'])
                                                     ->addInputAttributes(['class'=>'email-template-subject form-control'])
                                                     ->addInputAttributes(['value'=>$auto_template['subject'] ?? '' ?: $s->trans('invoice'). '#'. $invoice->getNumber() ])
                                                     ->hideLabel() ?>
                    
                    <?= Html::tag('Label',$s->trans('pdf_template')) ?>
                    <?= Field::select($form, 'pdf_template')->optionsData($pdf_templates, true,[],[])
                                                            ->disabled(empty($pdf_templates) ? true : false)
                                                            ->addInputAttributes(['class'=>'email-template-pdf-template form-control'])
                                                            ->addInputAttributes(['value'=> $setting_status_pdf_template ?: ucfirst('invoice')])
                                                            ->hideLabel()?>
                    
                    <?= Html::tag('Label',$s->trans('body')) ?>
                    
                    <?= Field::textarea($form, 'body')->addInputAttributes(['id'=>'mailerinvform-body'])
                                                  ->addInputAttributes(['class'=>'email-template-body form-control taggable'])
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
                            <?= $s->trans('preview'); ?>
                            <div id="email-template-preview-reload" class="pull-right cursor-pointer">
                                <i class="fa fa-refresh"></i>
                            </div>
                        </div>
                        <div class="panel-body">
                            <iframe id="email-template-preview"></iframe>
                        </div>
                    </div>
                    <div>
                        <?php echo $template_tags ?>
                    </div>                    
                    
                    <?= Field::file($form, 'attachFiles[]')
                        ->containerClass('mb-3')
                        ->multiple()
                        ->hideLabel()
                    ?>                   
                    <div>
                    <div class="form-group">3
                        
                        <div class="input-group">
                        <?=
                            Field::text($form,'guest_url')->readonly(true)
                                                          ->addInputAttributes(['id'=>'invoice-guest-url','readonly'=>'true',
                                                          'value'=> $urlGenerator->generate('inv/url_key',
                                                          ['url_key' => $invoice->getUrl_key(),'gateway'=>'']),'class'=>'form-control']);
                    
                            echo Html::tag('Div', Html::tag('i','',['class'=>'fa fa-clipboard fa-fw']),
                                                  ['class'=>'input-group-text to-clipboard cursor-pointer', 
                                                   'data-clipboard-target'=>'#invoice-guest-url','style' =>'height : 38px']);                                                      
                        ?>
                        </div>
                    </div>
                    </div>
                    <?= Field::buttonGroup()
                        ->addContainerClass('btn-group btn-toolbar float-end')
                        ->buttonsData([
                            [
                                $s->trans('cancel'),
                                'type' => 'reset',
                                'class' => 'btn btn-lg btn-danger',
                                'name'=> 'btn_cancel'
                            ],
                            [
                                $s->trans('send'),
                                'type' => 'submit',
                                'class' => 'btn btn-lg btn-primary',
                                'name' => 'btn_send'
                            ],
                        ]) ?>
                    <?= Form::tag()->close(); ?>                    
                </div>                
            </div>
        </div>
    </div>
</div>
<?php
// Fill the form with the template data
$js6 = "$(document).ready(function() {".
        'var textContent = '.$auto_template['body'].';'.
        '$("#mailerinvform-body").val(textContent);'.
        '});';
    echo Html::script($js6)->type('module');
?>

