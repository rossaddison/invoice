<?php
declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\OffsetPagination;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var \App\Invoice\Entity\UserInv $userinv
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var TranslatorInterface $translator
 * @var WebView $this
 */ 

echo $alert;
?>
<?php
    $header = Div::tag()
        ->addClass('row')
        ->content(
            H5::tag()
                ->addClass('bg-primary text-white p-3 rounded-top')
                ->content(
                    I::tag()->addClass('bi bi-people')
                            ->content(' ' . Html::encode($s->trans('users')))
                )
        )
        ->render();

    $toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-danger me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName()))
        ->id('btn-reset')
        ->render();

    $toolbar = Div::tag();
?>
<br>
<div>
    <h5><?= $s->trans('users'); ?></h5>
    <div class="btn-group index-options">
        <a href="<?= $urlGenerator->generate('userinv/index',['page'=>1, 'active'=>2]); ?>"
           class="btn <?php echo $active == 2 ? 'btn-primary' : 'btn-default' ?>">
            <?= $s->trans('all'); ?>
        </a>
        <a href="<?= $urlGenerator->generate('userinv/index',['page'=>1, 'active'=>1]); ?>" style="text-decoration:none"
           class="btn  <?php echo $active == 1 ? 'btn-primary' : 'btn-default' ?>">
            <?= $s->trans('active'); ?>
        </a>
        <a href="<?= $urlGenerator->generate('userinv/index',['page'=>1, 'active'=>0]); ?>" style="text-decoration:none"
           class="btn  <?php echo $active == 0 ? 'btn-primary' : 'btn-default' ?>">
            <?= $s->trans('inactive'); ?>
        </a>
        <?= 
        Html::a(
                Html::tag('i', '', [
                    'class' => 'fa fa-plus'
                ]), 
                $urlGenerator->generate('userinv/add'), ['class' => 'btn btn-sm btn-primary']
        )->render();
        ?>
    </div>
</div>
<br>

<div id="content" class="table-content">  
<div class="card shadow">
        <?= GridView::widget()
        ->columns(
            DataColumn::create()
            ->attribute('active')
            ->value(static function ($model) use($s): string {
                        return $model->getActive() ? Html::tag('span',$s->trans('yes'),['class'=>'label active'])->render() 
                                                   : Html::tag('span',$s->trans('no'),['class'=>'label inactive'])->render();
            }),
            DataColumn::create()
            ->label($s->trans('user_all_clients'))        
            ->attribute('all_clients')
            ->value(static function ($model) use($s): string {
                        return $model->getAll_clients() ? Html::tag('span',$s->trans('yes'),['class'=>'label active'])->render()
                                                        : Html::tag('span',$s->trans('no'),['class'=>'label inactive'])->render();
            }),
            DataColumn::create()
            ->attribute('user_id')
            ->value(static function ($model) use ($urlGenerator) : string {
              return (string)Html::a($model->getUser()?->getLogin(),$urlGenerator->generate('user/profile',['login'=>$model->getUser()?->getLogin()]),[]);
            }),     
            DataColumn::create()
            ->attribute('name')
            ->value(static function ($model): string {
                        return $model->getName();
            }),
            DataColumn::create()
            ->label($s->trans('user_type'))        
            ->attribute('type')
            ->value(static function ($model) use ($s): string {
                $user_types = [
                    0 => $s->trans('administrator'),
                    1 => $s->trans('guest_read_only'),
                ];  
                return $user_types[$model->getType()];
            }),
            DataColumn::create()
            ->attribute('user_id')
            ->label($translator->translate('invoice.user.inv.role.accountant'))
            ->value(static function ($model) use ($manager, $translator, $urlGenerator): string {
              if ($manager->getPermissionsByUserId($model->getUser_id()) 
                === $manager->getPermissionsByRoleName('accountant')) { 
                return Html::tag('span', $translator->translate('invoice.general.yes'),['class'=>'label active'])->render(); 
              } else {
                return $model->getUser_id() !== '1' ? Html::a(
                  Html::tag('button',
                  Html::tag('span', $translator->translate('invoice.general.no'),['class'=>'label inactive'])
                  ,[
                     'type'=>'submit', 
                     'class'=>'dropdown-button',
                     'onclick'=>"return confirm("."'".$translator->translate('invoice.user.inv.role.warning.role') ."');"
                  ]),
                  $urlGenerator->generate('userinv/accountant',['user_id'=>$model->getUser_id()],[]),
                 )->render() : '';
              }
            }),
            DataColumn::create()
            ->attribute('user_id')
            ->label($translator->translate('invoice.user.inv.role.administrator'))
            ->value(static function ($model) use ($manager, $translator, $urlGenerator): string {
              if ($manager->getPermissionsByUserId($model->getUser_id()) 
                === $manager->getPermissionsByRoleName('admin')) { 
                return  Html::tag('span', $translator->translate('invoice.general.yes'),['class'=>'label active'])->render(); 
              } else {
                if (!$model->getUser_id()=='1') {
                return Html::a(
                  Html::tag('button',
                  Html::tag('span', $translator->translate('invoice.general.no'),['class'=>'label inactive'])
                  ,[
                     'type'=>'submit', 
                     'class'=>'dropdown-button',
                     'onclick'=>"return confirm("."'".$translator->translate('invoice.user.inv.role.warning.role') ."');"
                  ]),
                  $urlGenerator->generate('userinv/admin',['user_id'=>$model->getUser_id()],[]),
                 )->render();
                } // not id == 1 => use AssignRole console command to assign the admin role
                return '';
                } // else
            }),
            DataColumn::create()
            ->attribute('user_id')
            ->label($translator->translate('invoice.user.inv.role.observer'))
            ->value(static function ($model) use ($manager, $translator, $urlGenerator): string {
              if ($manager->getPermissionsByUserId($model->getUser_id()) 
                === $manager->getPermissionsByRoleName('observer')) { 
                return  Html::tag('span', $translator->translate('invoice.general.yes'),['class'=>'label active'])->render(); 
              } else {
                return $model->getUser_id() !== '1' ? Html::a(
                    Html::tag('button',
                    Html::tag('span', $translator->translate('invoice.general.no'),['class'=>'label inactive'])
                    ,[
                       'type'=>'submit', 
                       'class'=>'dropdown-button',
                       'onclick'=>"return confirm("."'".$translator->translate('invoice.user.inv.role.warning.role') ."');"
                    ]),
                    $urlGenerator->generate('userinv/observer',['user_id'=>$model->getUser_id()],[]),
                   )->render() : '';
              }
            }), 
            DataColumn::create()
            ->attribute('email')
            ->value(static function ($model): string {
                        return $model->getEmail();
            }),         
            DataColumn::create()
            ->label($s->trans('assigned_clients'))                
            ->attribute('type')
            ->value(static function ($model) use ($urlGenerator): string {
                        // The administrator has access to all clients so assigning clients is only applicable to guest user accounts
                        // Display the button only if the user has a guest account setup not to be confused with Yii's isGuest.
                        // Admin => 0, Guest => 1   not to be confused with admin User Table id which is 1 and UserInv Table user_id is 1.
                        return $model->getType() !== 0 ? Html::a(
                                    Html::tag('i','',['class'=>'fa fa-list fa-margin']),
                                    // UserInv is an extension of table user
                                    // The user_id will be retrieved in the controller not here 
                                    // Just pass the primary key of UserInv here below
                                        $urlGenerator->generate('userinv/client',['id'=>$model->getId()]),
                                        ['class'=>'btn btn-default']            
                                    )->render() : '';
            }),                        
            DataColumn::create()            
            ->label($s->trans('edit'))                
            ->attribute('type')
            ->value(static function ($model) use ($urlGenerator, $canEdit): string {
                        return $canEdit ? Html::a(
                                                            Html::tag('i','',['class'=>'fa fa-edit fa-margin']),
                                                        $urlGenerator->generate('userinv/edit',['id'=>$model->getId()]),[]                                         
                                                        )->render() : '';
                               
                        
            }),
            DataColumn::create()            
            ->label($s->trans('delete'))                
            ->attribute('type')
            ->value(static function ($model) use ($s, $urlGenerator): string {
                        return $model->getType() == 1 ? Html::a( Html::tag('button',
                                                            Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                                                            [
                                                                'type'=>'submit', 
                                                                'class'=>'dropdown-button',
                                                                'onclick'=>"return confirm("."'".$s->trans('delete_record_warning')."');"
                                                            ]
                                                            ),
                                                        $urlGenerator->generate('userinv/delete',['id'=>$model->getId()]),[]                                         
                                                        )->render() : '';
                               
                        
            }),         
        )
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->filterPosition('header')
        ->filterModelName('userinv')
        ->header($header)
        ->id('w5-grid')
        ->paginator($paginator)
        ->pagination(
        OffsetPagination::widget()
             ->menuClass('pagination justify-content-center')
             ->paginator($paginator)
             ->urlArguments([])
             ->render(),
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summary($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-user-inv'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('userinv/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );          
    ?> 
</div>
</div>