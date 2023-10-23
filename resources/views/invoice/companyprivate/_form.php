<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Yii\Bootstrap5\Alert;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * 
 * @var \App\Contact\ContactForm $form
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

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('CompanyPrivateForm')
    ->open() ?>

<div id="headerbar">
<h1 class="headerbar-title"><?= $s->trans('companyprivates_form'); ?></h1>
<?php $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]); ?>        
<?php echo (string)$response->getBody(); ?><div id="content">
<div class="row">
 <div class="mb3 form-group">
    <label for="company_id" required><?= $company_public; ?></label>
    <select name="company_id" id="company_id" class="form-control" required>
       <option value=""><?= $company_public; ?></option>
         <?php foreach ($companies as $company) { ?>
          <option value="<?= $company->getId(); ?>"
           <?php $s->check_select(Html::encode($body['company_id'] ?? ''), $company->getId()) ?>
           ><?= $company->getName(); ?></option>
         <?php } ?>
    </select>
 </div>
 <div class="mb3 form-group">
   <input type="hidden" name="id" id="id" class="form-control"
 value="<?= Html::encode($body['id'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="tax_code"><?= $s->trans('tax_code'); ?></label>
   <input type="text" name="tax_code" id="tax_code" class="form-control"
 value="<?= Html::encode($body['tax_code'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="iban"><?= $s->trans('user_iban'); ?></label>
   <input type="text" name="iban" id="iban" class="form-control"
 value="<?= Html::encode($body['iban'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="gln"><?= $s->trans('gln'); ?></label>
   <input type="text" name="gln" id="gln" class="form-control"
 value="<?= Html::encode($body['gln'] ??  ''); ?>">
 </div>
 <div class="mb3 form-group">
   <label for="rcc"><?= $s->trans('sumex_rcc'); ?></label>
   <input type="text" name="rcc" id="rcc" class="form-control"
 value="<?= Html::encode($body['rcc'] ??  ''); ?>">
 </div>
    
 <div class="mb3 form-group">
    <label for="logo_filename"><?= $translator->translate('invoice.company.private.logo').': '; ?>
        <?= $body['logo_filename'] ?? '' ?>
    </label>
    <br>
    <?= 
        Html::file('file','',['name'=>'file', 'id'=>'file', 'class'=>'form-control']); 
    ?> 
 </div>
 <div class="mb-3 form-group has-feedback">
        <?php
           $startdate = $datehelper->get_or_set_with_style($body['start_date'] ?? new \DateTimeImmutable('now'));
        ?>
        <label form-label for="start_date"><?= $s->trans('start_date') .' ('.$datehelper->display().')'; ?></label>
        <div class="input-group">
            <input type="text" name="start_date" id="start_date" placeholder="<?= ' ('.$datehelper->display().')';?>"
                   class="form-control input-sm datepicker" readonly                   
                   value="<?= Html::encode($startdate instanceof \DateTimeImmutable || $startdate instanceof \DateTime ? $startdate->format($datehelper->style()) : $startdate); ?>" role="presentation" autocomplete="off">
            <span class="input-group-text">
            <i class="fa fa-calendar fa-fw"></i>
        </span>
        </div>        
</div>
    
<div class="mb-3 form-group has-feedback">
        <?php
           $enddate = $datehelper->get_or_set_with_style($body['end_date'] ?? new \DateTimeImmutable('now'));
        ?>
        <label form-label for="end_date"><?= $s->trans('end_date') .' ('.$datehelper->display().')'; ?></label>
        <div class="input-group">
            <input type="text" name="end_date" id="end_date" placeholder="<?= ' ('.$datehelper->display().')';?>"
                   class="form-control input-sm datepicker" readonly                   
                   value="<?= Html::encode($enddate instanceof \DateTimeImmutable || $startdate instanceof \DateTime ? $enddate->format($datehelper->style()) : $enddate); ?>" role="presentation" autocomplete="off">
            <span class="input-group-text">
            <i class="fa fa-calendar fa-fw"></i>
        </span>
        </div>        
</div>    

</div>

</div>

</div>
<?= Form::tag()->close() ?>
