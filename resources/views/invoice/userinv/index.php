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
 * @var \Yiisoft\Translator\TranslatorInterface $translator
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
                            ->content(' ' . Html::encode($translator->translate('i.users')))
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
    <h5><?= $translator->translate('i.users'); ?></h5>
    <div class="btn-group index-options">
        <a href="<?= $urlGenerator->generate('userinv/index',['page'=>1, 'active'=>2]); ?>"
           class="btn <?php echo $active == 2 ? 'btn-primary' : 'btn-default' ?>">
            <?= $translator->translate('i.all'); ?>
        </a>
        <a href="<?= $urlGenerator->generate('userinv/index',['page'=>1, 'active'=>1]); ?>" style="text-decoration:none"
           class="btn  <?php echo $active == 1 ? 'btn-primary' : 'btn-default' ?>">
            <?= $translator->translate('i.active'); ?>
        </a>
        <a href="<?= $urlGenerator->generate('userinv/index',['page'=>1, 'active'=>0]); ?>" style="text-decoration:none"
           class="btn  <?php echo $active == 0 ? 'btn-primary' : 'btn-default' ?>">
            <?= $translator->translate('i.inactive'); ?>
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
<?php 
    $columns = [
        new DataColumn(
            'active',
            content: static function ($model) use($translator): string {
                        return $model->getActive() ? Html::tag('span',$translator->translate('i.yes'),['class'=>'label active'])->render() 
                                                   : Html::tag('span',$translator->translate('i.no'),['class'=>'label inactive'])->render();
            }
        ),
        new DataColumn(
            'all_clients',
            header:  $translator->translate('i.user_all_clients'),        
            content: static function ($model) use($translator): string {
                        return $model->getAll_clients() ? Html::tag('span',$translator->translate('i.yes'),['class'=>'label active'])->render()
                                                        : Html::tag('span',$translator->translate('i.no'),['class'=>'label inactive'])->render();
            }
        ),
        new DataColumn(
            'user_id',
            content: static function ($model) use ($urlGenerator) : string {
            return (string)Html::a($model->getUser()?->getLogin(),$urlGenerator->generate('user/profile',['login'=>$model->getUser()?->getLogin()]),[]);
        }),     
        new DataColumn(
            'name',
            content: static function ($model): string {
                return $model->getName();
            }
        ),
        new DataColumn(
            'type',    
            header:  $translator->translate('i.user_type'),        
            content: static function ($model) use ($translator): string {
            $user_types = [
                0 => $translator->translate('i.administrator'),
                1 => $translator->translate('i.guest_read_only'),
            ];  
            return $user_types[$model->getType()];
        }),
        new DataColumn(
            'user_id',
            header:  $translator->translate('invoice.user.inv.role.accountant'),
            content: static function ($model) use ($manager, $translator, $urlGenerator): string {
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
            }
        ),
        new DataColumn(
            'user_id',
            header:  $translator->translate('invoice.user.inv.role.administrator'),
            content: static function ($model) use ($manager, $translator, $urlGenerator): string {
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
            }
        ),
        new DataColumn(
            'email',
            content: static function ($model): string {
                return $model->getEmail();
        }),         
        new DataColumn(
            'type',
            header:  $translator->translate('i.assigned_clients'),                
            content: static function ($model) use ($urlGenerator, $ucR): string { 
                return Html::a(
                                Html::tag('i', str_repeat(' ',1).(string)count($ucR->get_assigned_to_user($model->getUser_id())),
                                ['class'=>'fa fa-list fa-margin']),
                                $urlGenerator->generate('userinv/client',['id'=>$model->getId()]),
                                ['class'=> count($ucR->get_assigned_to_user($model->getUser_id())) > 0 
                                        ? 'btn btn-success' 
                                        : 'btn btn-danger']            
                            )->render();
        }),      
        new DataColumn(            
            'type',
            header:  $translator->translate('i.edit'),                
            content: static function ($model) use ($urlGenerator, $canEdit): string {
                        return $canEdit ? Html::a(
                                                            Html::tag('i','',['class'=>'fa fa-edit fa-margin']),
                                                        $urlGenerator->generate('userinv/edit',['id'=>$model->getId()]),[]                                         
                                                        )->render() : '';


        }),
        new DataColumn(            
            'type',
            header:  $translator->translate('i.delete'),                
            content: static function ($model) use ($translator, $urlGenerator): string {
                        return $model->getType() == 1 ? Html::a( Html::tag('button',
                                                            Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                                                            [
                                                                'type'=>'submit', 
                                                                'class'=>'dropdown-button',
                                                                'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                                                            ]
                                                            ),
                                                        $urlGenerator->generate('userinv/delete',['id'=>$model->getId()]),[]                                         
                                                        )->render() : '';


            }),
        ];
    ?>
    <?= GridView::widget()
    ->columns(...$columns)
    ->dataReader($paginator)        
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    //->filterPosition('header')
    //->filterModelName('userinv')
    ->header($header)
    ->id('w5-grid')
    ->pagination(
    OffsetPagination::widget()
        ->paginator($paginator)
         ->render(),
    )
    ->rowAttributes(['class' => 'align-middle'])
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
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