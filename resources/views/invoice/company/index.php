<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var \App\Invoice\Entity\Company $company
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $canEdit
 */

echo $alert;
?>
<h1><?= $company_public; ?></h1>

<?php
    if ($canEdit) {
        echo Html::a('Add',
        $urlGenerator->generate('company/add'),
            ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']
     );
    }
    
    //list all the items
    
    /**
     * @var Company $company
     */
    foreach ($companies as $company){
      echo Html::br();
      $label = $company->getId() . " ";
      echo Html::label($label);
      echo Html::a($s->trans('edit'),
      $urlGenerator->generate('company/edit', ['id' => $company->getId()]),
            ['class' => 'btn btn-info btn-sm ms-2']
          );
      echo Html::a($s->trans('view'),
      $urlGenerator->generate('company/view', ['id' => $company->getId()]),
      ['class' => 'btn btn-warning btn-sm ms-2']
             );
      echo Html::a($s->trans('delete'),
      $urlGenerator->generate('company/delete', ['id' => $company->getId()]),
      ['class' => 'btn btn-danger btn-sm ms-2', 
       'onclick'=>"return confirm('". $s->trans('delete_record_warning'). "')"
      ]
      );
    }
?>