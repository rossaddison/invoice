<?php 
   use Yiisoft\Strings\Inflector;
   echo "<?php\n";
   $random = 99999999999999999;
?>

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\<?= $generator->getCamelcase_capital_name(); ?> $<?= $generator->getSmall_singular_name()."\n"; ?>
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var OffsetPaginator $paginator
 * @var string $id
 */
 
 echo $alert;

?>
<?php 
        $inf = new Inflector();
        echo '<h1>'.$inf->toSentence($generator->getPre_entity_table(),'UTF-8').'</h1>'."\n"; 
        echo "<?= Html::a(Html::tag('"."i','',['class'=>'fa fa-plus btn btn-primary fa-margin']),".'$urlGenerator->generate('."'". $generator->getSmall_singular_name()."/add'),[]); ?>";
        echo '<br>';  
?>


<?php   echo "<?php\n"; ?>
    
    $header = Div::tag()
      ->addClass('row')
      ->content(
        H5::tag()
        ->addClass('bg-primary text-white p-3 rounded-top')
        ->content(
          I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('put.your.translation.here'))
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
       
    echo GridView::widget()
      ->columns(
        DataColumn::create()
        ->attribute('id')
        ->label($s->trans('id'))
        ->value(static fn($model) => $model->getId()),
        DataColumn::create()
        ->label($s->trans('view'))
        ->value(static function ($model) use ($urlGenerator): string {
          return Html::a(Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']), $urlGenerator->generate('<?= $generator->getSmall_singular_name(); ?>/view', ['id' => $model->getId()]), [])->render();
        }
        ),
        DataColumn::create()
        ->label($s->trans('edit'))
        ->value(static function ($model) use ($urlGenerator): string {
          return Html::a(Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']), $urlGenerator->generate('<?= $generator->getSmall_singular_name(); ?>/edit', ['id' => $model->getId()]), [])->render();
        }
        ),
        DataColumn::create()
        ->label($s->trans('delete'))
        ->value(static function ($model) use ($s, $urlGenerator): string {
          return Html::a(Html::tag('button',
              Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
              [
                'type' => 'submit',
                'class' => 'dropdown-button',
                'onclick' => "return confirm(" . "'" . $s->trans('delete_record_warning') . "');"
              ]
            ),
            $urlGenerator->generate('<?= $generator->getSmall_singular_name(); ?>/delete', ['id' => $model->getId()]), []
          )->render();
        }
        ),
      )
      ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
      ->filterPosition('header')
      ->filterModelName('<?= $generator->getSmall_singular_name(); ?>')
      ->header($header)
      ->id('w<?= $random; ?>-grid')
      ->paginator($paginator)
      ->pagination(
        OffsetPagination::widget()
        ->menuClass('pagination justify-content-center')
        ->paginator($paginator)
        // No need to use page argument since built-in. Use status bar value passed from urlGenerator to inv/guest
        //->urlArguments(['status'=>$status])
        ->render(),
      )
      ->rowAttributes(['class' => 'align-middle'])
      ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
      ->summary($grid_summary)
      ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
      ->emptyText((string) $translator->translate('invoice.invoice.no.records'))
      ->tableAttributes(['class' => 'table table-striped text-center h-<?= $random; ?>', 'id' => 'table-<?= $generator->getSmall_singular_name(); ?>'])
      ->toolbar(
        Form::tag()->post($urlGenerator->generate('<?= $generator->getSmall_singular_name(); ?>/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );