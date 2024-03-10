<?php
declare(strict_types=1); 

use Yiisoft\Arrays\ArrayHelper;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('UserInvForm')
    ->open() ?>

<?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?= Html::encode($title) ?>
        <?= Html::closeTag('h1'); ?>
        <?= $button::back_save(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['id' => 'content']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group has-feedback']); ?>
            <?= Field::errorSummary($form)
                ->errors($errors)
                ->header($translator->translate('invoice.error.summary'))
                ->onlyCommonErrors()
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group no-margin']); ?>
            <?php 
                $optionsDataUser = [];
                foreach ($users as $user) {
                    $optionsDataUser[$user->getId()] = ucfirst($user->getLogin());
                }
                echo Field::select($form, 'user_id')
                ->label($translator->translate('i.users'))    
                ->addInputAttributes([
                    'hidden' => 'hidden',
                    'class' => 'form-control',
                    'id' => 'user_id'
                ])
                ->hideLabel(true)        
                ->optionsData($optionsDataUser)
                ->value(Html::encode($form->getUser_id() ?? ''));
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?php 
              $types = [
                  0 => $translator->translate('i.administrator'),
                  1 => $translator->translate('i.guest_read_only'),
              ]  
            ?>
            <?php 
                $optionsDataType = [];
                foreach ($types as $key => $value) {
                    $optionsDataType[$key] = $value;
                }
                echo Field::select($form, 'type')
                ->label($translator->translate('i.type'))    
                ->addInputAttributes([
                    'hidden' => 'hidden',
                    'class' => 'form-control',
                    'id' => 'type'
                ])
                ->hidelabel(true)        
                ->optionsData($optionsDataType)
                ->value(Html::encode($form->getType()));        
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Html::openTag('div', ['class' => 'p-2']); ?> 
                <?= Field::hidden($form, 'active')
                    ->hideLabel(true)
                    ->value(Html::encode($form->getActive()))
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?><?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
            <?= Field::hidden($form, 'all_clients')
                ->hideLabel(true)
                ->value(Html::encode($form->getAll_clients()));
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div', ['class' => 'mb-3 form-group no-margin']); ?>
            <?php 
                $optionsDataLanguage = [];
                foreach (ArrayHelper::map($s->expandDirectoriesMatrix($aliases->get('@language'), 0),'name','name') as $language)  {
                    $optionsDataLanguage[$language] = ucfirst($language);
                }
                echo Field::select($form, 'language')
                ->label($translator->translate('i.language'))    
                ->addInputAttributes([
                    'class' => 'form-control',
                    'id' => 'language'
                ])
                ->optionsData($optionsDataLanguage)
                ->value(Html::encode($form->getLanguage()))
                ->hint($translator->translate('invoice.hint.this.field.is.required'));        
            ?>
        <?= Html::closeTag('div'); ?>   
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'name')
                ->label($translator->translate('i.name'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.name'),
                    'class' => 'form-control',
                    'id' => 'name'
                ])
                ->value(Html::encode($form->getName() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'company')
                ->label($translator->translate('i.company'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.company'),
                    'class' => 'form-control',
                    'id' => 'company'
                ])
                ->value(Html::encode($form->getCompany() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>   
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'address_1')
                ->label($translator->translate('i.street_address'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.street_address'),
                    'class' => 'form-control',
                    'id' => 'address_1'
                ])
                ->value(Html::encode($form->getAddress_1() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'address_2')
                ->label($translator->translate('i.street_address_2'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.street_address'),
                    'class' => 'form-control',
                    'id' => 'address_2'
                ])
                ->value(Html::encode($form->getAddress_2() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'city')
                ->label($translator->translate('i.city'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.city'),
                    'class' => 'form-control',
                    'id' => 'city'
                ])
                ->value(Html::encode($form->getCity() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'state')
                ->label($translator->translate('i.state'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.state'),
                    'class' => 'form-control',
                    'id' => 'state'
                ])
                ->value(Html::encode($form->getState() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'zip')
                ->label($translator->translate('i.zip'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.zip'),
                    'class' => 'form-control',
                    'id' => 'zip'
                ])
                ->value(Html::encode($form->getZip() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'country')
                ->label($translator->translate('i.country'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.country'),
                    'class' => 'form-control',
                    'id' => 'country'
                ])
                ->value(Html::encode($form->getCountry() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::telephone($form, 'phone')
                ->label($translator->translate('i.phone'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.phone'),
                    'class' => 'form-control',
                    'id' => 'phone'
                ])
                ->value(Html::encode($form->getPhone() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::telephone($form, 'fax')
                ->label($translator->translate('i.fax'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.fax'),
                    'class' => 'form-control',
                    'id' => 'fax'
                ])
                ->value(Html::encode($form->getFax() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
           <?= Field::telephone($form, 'mobile')
                ->label($translator->translate('i.mobile'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.mobile'),
                    'class' => 'form-control',
                    'id' => 'mobile'
                ])
                ->value(Html::encode($form->getMobile() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::email($form, 'email')
                ->label($translator->translate('i.email') . str_repeat(' ', 2). $translator->translate('invoice.email.template.user.account.leave.blank'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.email'),
                    'class' => 'form-control',
                    'id' => 'email'
                ])
                ->value(Html::encode($form->getEmail() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::password($form, 'password')
                ->label($translator->translate('i.password'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.password'),
                    'class' => 'form-control',
                    'id' => 'password'
                ])
                ->value(Html::encode($form->getPassword() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'web')
                ->label($translator->translate('i.web_address'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.web_address'),
                    'class' => 'form-control',
                    'id' => 'web'
                ])
                ->value(Html::encode($form->getWeb() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'tax_code')
                ->label($translator->translate('i.tax_code'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.tax_code'),
                    'class' => 'form-control',
                    'id' => 'tax_code'
                ])
                ->value(Html::encode($form->getTax_code() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'subscribernumber')
                ->label($translator->translate('i.user_subscriber_number'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.user_subscriber_number'),
                    'class' => 'form-control',
                    'id' => 'subscribernumber'
                ])
                ->value(Html::encode($form->getSubscribernumber() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'iban')
                ->label($translator->translate('i.user_iban'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.user_iban'),
                    'class' => 'form-control',
                    'id' => 'iban'
                ])
                ->value(Html::encode($form->getSubscribernumber() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
         <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'gln')
                ->label($translator->translate('invoice.delivery.location.global.location.number'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('invoice.delivery.location.global.location.number'),
                    'class' => 'form-control',
                    'id' => 'gln'
                ])
                ->value(Html::encode($form->getGln() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
        <?= Html::openTag('div',['class' => 'mb-3 form-group']); ?>
            <?= Field::text($form, 'rcc')
                ->label($translator->translate('i.sumex_rcc'))
                ->addInputAttributes([
                    'placeholder' => $translator->translate('i.sumex_rcc'),
                    'class' => 'form-control',
                    'id' => 'rcc'
                ])
                ->value(Html::encode($form->getRcc() ?? ''))
                ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
            ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>
