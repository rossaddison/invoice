<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Modal;

/**
 * @var \App\Invoice\Entity\Sumex $sumex
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var bool $canEdit
 * @var string $id
 */

echo $alert;
?>

<h1>Sumexs</h1>

<div>
<?php
    if ($canEdit) {
        echo Html::a('Add',
        $urlGenerator->generate('sumex/add'),
            ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']
     );
    //list all the items
    foreach ($sumexs as $sumex){
      echo Html::br();
      $label = $sumex->getId() . " ";
      echo Html::label($label);
      echo Html::a('Edit',
      $urlGenerator->generate('sumex/edit', ['invoice' => $sumex->getInvoice()]),
            ['class' => 'btn btn-info btn-sm ms-2']
          );
      echo Html::a('View',
      $urlGenerator->generate('sumex/view', ['id' => $sumex->getId()]),
      ['class' => 'btn btn-warning btn-sm ms-2']
             );
      //modal delete button
      echo Modal::widget()
      ->title('Please confirm that you want to delete this record')
      ->titleOptions(['class' => 'text-center'])
      ->options(['class' => 'testMe'])
      ->size(Modal::SIZE_SMALL)
      ->headerOptions(['class' => 'text-danger'])
      ->bodyOptions(['class' => 'modal-body', 'style' => 'text-align:center;',])
      ->footerOptions(['class' => 'text-dark'])
      ->footer(
                  Html::button(
                  'Close',
                  [
                              'type' => 'button',
                              'class' => ['btn btn-success btn-sm ms-2'],
                              'data' => [
                              'bs-dismiss' => 'modal',
                   ],
                   ]
                   ).                   Html::a('Yes Delete it Please ... I am sure!',
                   $urlGenerator->generate('sumex/delete', ['id' => $sumex->getId()]),
                   ['class' => 'btn btn-danger btn-sm ms-2']
                              )
                        )
      ->withoutCloseButton()
      ->toggleButton([
                      'class' => ['btn btn-danger btn-sm ms-2'],
                      'label' => 'Delete',
                      ])
      ->begin();
      echo '<p>Are you sure you want to delete this record? </p>';
      echo Modal::end();
      echo Html::br();
    }
    }
?>
</div>