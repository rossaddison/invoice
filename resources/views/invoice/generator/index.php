<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
/**
 * @var \App\Invoice\Entity\Gentor $generators
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $canEdit
 * @var string $id
 */

echo $alert;
?>
    <h1><?= Html::encode($translator->translate('invoice.generator')); ?></h1>
    <div>        
        <?php
        if ($canEdit) {
            echo Html::a('Add',
                $urlGenerator->generate('generator/add'),
                ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']
            );
            
            //list all the generators
            foreach ($generators as $generator){
                echo Html::br();
                $label = $generator->getGentor_id() . " ";               
                echo Html::label($label);
                echo '<div class="btn-group">';
                echo Html::a($generator->getCamelcase_capital_name(),$urlGenerator->generate('generator/view',['id' => $generator->getGentor_id()]),['class' => 'btn btn-primary btn-sm active','aria-current' => 'page']);
                $relations = $grr->repoGeneratorquery($generator->getGentor_id());
                foreach ($relations as $relation) {
                    echo Html::a($relation->getLowercase_name(),$urlGenerator->generate('generatorrelation/edit',['id' => $relation->getRelation_id()]),['class' => 'btn btn-primary btn-sm']);
                }
                echo Html::a(Html::tag('button',
                          Html::tag('i','',['class'=>'fa fa-pencil fa-margin']),
                          [
                              'type'=>'submit', 
                              'class'=>'dropdown-button'
                          ]
                          ),
                $urlGenerator->generate('generator/edit', ['id' => $generator->getGentor_id()]),
                []
                );                
                echo Html::a(Html::tag('button',
                          Html::tag('i','',['class'=>'fa fa-eye fa-margin']),
                          [
                              'type'=>'submit', 
                              'class'=>'dropdown-button'
                          ]
                          ),
                $urlGenerator->generate('generator/view', ['id' => $generator->getGentor_id()]),
                []
                );
                //modal delete button
                echo '</div>';
                echo str_repeat("&nbsp;", 2);
                echo '<div class="btn-group">';
                echo Html::a( Html::tag('button',
                          Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                          [
                              'type'=>'submit', 
                              'class'=>'dropdown-button',
                              'onclick'=>"return confirm("."'".$s->trans('delete_record_warning')."');"
                          ]
                          ),
                          $urlGenerator->generate('generator/delete',['id'=>$generator->getGentor_id()]),
                          []                                         
                        )->render();
                echo '</div>';
                echo Html::a('Entity'.DIRECTORY_SEPARATOR.$generator->getCamelcase_capital_name(),
                $urlGenerator->generate('generator/entity',['id' => $generator->getGentor_id()]),
                ['class' => 'btn btn-secondary btn-sm ms-2']
                );
                echo Html::a($generator->getCamelcase_capital_name().'Repository',
                $urlGenerator->generate('generator/repo',['id' => $generator->getGentor_id()]),
                ['class' => 'btn btn-secondary btn-sm ms-2']
                );
                echo Html::a($generator->getCamelcase_capital_name().'Service',
                $urlGenerator->generate('generator/service',['id' => $generator->getGentor_id()]),
                ['class' => 'btn btn-secondary btn-sm ms-2']
                );
                
                echo Html::a($generator->getCamelcase_capital_name().'Controller',
                $urlGenerator->generate('generator/controller',['id' => $generator->getGentor_id()]),
                ['class' => 'btn btn-secondary btn-sm ms-2']
                );
                echo Html::a($generator->getCamelcase_capital_name().'Form',
                $urlGenerator->generate('generator/form',['id' => $generator->getGentor_id()]),
                ['class' => 'btn btn-secondary btn-sm ms-2']
                );
                echo Html::a('index',
                $urlGenerator->generate('generator/_index',['id' => $generator->getGentor_id()]),
                ['class' => 'btn btn-secondary btn-sm ms-2']
                );
                if (!empty($generator->isKeyset_paginator_include()) || !empty($generator->isOffset_paginator_include())) {
                  if (!empty($generator->getFilter_field())) {  
                    echo Html::a('index_adv_paginator_with_filter',
                    $urlGenerator->generate('generator/_index_adv_paginator_with_filter',['id' => $generator->getGentor_id()]),
                    ['class' => 'btn btn-secondary btn-sm ms-2']
                    );
                  } else {  
                    echo Html::a('index_adv_paginator',
                    $urlGenerator->generate('generator/_index_adv_paginator',['id' => $generator->getGentor_id()]),
                    ['class' => 'btn btn-secondary btn-sm ms-2']
                    );
                  }
                }                
                echo Html::a('_view',
                $urlGenerator->generate('generator/_view',['id' => $generator->getGentor_id()]),
                ['class' => 'btn btn-secondary btn-sm ms-2']
                );
                echo Html::a('_form',
                $urlGenerator->generate('generator/_form',['id' => $generator->getGentor_id()]),
                ['class' => 'btn btn-secondary btn-sm ms-2']
                );
                echo Html::a('_route',
                $urlGenerator->generate('generator/_route',['id' => $generator->getGentor_id()]),
                ['class' => 'btn btn-secondary btn-sm ms-2']
                );
                echo Html::br();
            }           
        }
        ?>
    </div>
<?php
echo Html::closeTag('div');
?>