<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Yii\Bootstrap5\Alert;
use App\Invoice\Helpers\DateHelper;

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
<div class="row">
 <div class="mb3 form-group">
<label for="tax_code" class="form-label" style="background:lightblue"><?= $s->trans('tax_code'); ?></label>
   <?= Html::encode($body['tax_code'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="iban" class="form-label" style="background:lightblue"><?= $s->trans('user_iban'); ?></label>
   <?= Html::encode($body['iban'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="gln" class="form-label" style="background:lightblue"><?= $s->trans('gln'); ?></label>
   <?= Html::encode($body['gln'] ?? ''); ?>
 </div>
 <div class="mb3 form-group">
<label for="rcc" class="form-label" style="background:lightblue"><?= $s->trans('sumex_rcc'); ?></label>
   <?= Html::encode($body['rcc'] ?? ''); ?>
 </div>    
 <div class="mb3 form-group">
    <label for="logo_filename"><?= $translator->translate('invoice.company.private.logo'); ?>
    </label>
    <?= Img::tag()                     
        // Logo loation: public ie. '/'
           ->src(($body['logo_filename'] ? '/'.$body['logo_filename'] : '/yii3_sign'))
           ->size(48,60); ?>
 </div>
 <div class="mb3 form-group">
   <label for="company_id" class="form-label" style="background:lightblue"><?= $s->trans('company')." ". $s->trans('name'); ?></label>
   <?= Html::encode($companyprivate->getCompany()->getName());?>
 </div>
 <div class="mb-3 form-group has-feedback">
        <label class="form-label" style="background:lightblue" for="start_date"><?= $s->trans('start_date'); ?></label>
        <?php
            $startdate = $body['start_date'] ?? null;
            if ($startdate && $startdate != "0000-00-00") {
                //use the DateHelper
                $datehelper = new DateHelper($s);
                $startdate = $datehelper->date_from_mysql($startdate);
            } else {
                $startdate = null;
            }
        ?>      
        <?= Html::encode($startdate); ?>        
</div>    
<div class="mb-3 form-group has-feedback">
        <label class="form-label" style="background:lightblue" for="end_date"><?= $s->trans('end_date'); ?></label>
        <?php
            $enddate = $body['end_date'] ?? null;
            if ($enddate && $enddate != "0000-00-00") {
                //use the DateHelper
                $datehelper = new DateHelper($s);
                $enddate = $datehelper->date_from_mysql($enddate);
            } else {
                $enddate = null;
            }
        ?>      
        <?= Html::encode($enddate); ?>        
</div>    
</div>
