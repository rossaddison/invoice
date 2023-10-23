<?php
echo "<?php\n";
?>

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;

/**
* @var \Yiisoft\View\View $this
* @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
* @var array $body
* @var string $csrf
* @var string $action
* @var string $title
*/

<?php
echo "?>";
?>

<?php echo '<h1><?= Html::encode($title) ?></h1>'; ?>

<?php
echo '<div class="row">' . "\n";
foreach ($orm_schema->getColumns() as $column) {
  //if the column is not a relation column
  if ((substr($column, -3) <> '_id') && ($column->getName() <> 'id')) {
    if ($column->getAbstractType() <> 'date') {
      echo ' <div class="row mb3 form-group">' . "\n";
      echo '<label for="' . $column->getName() . '" class="text-bg col-sm-2 col-form-label " style="background:lightblue">' . '<?= $s' . "->trans('" . $column->getName() . "'); ?>";
      echo '</label>' . "\n";
      echo '<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($body[' . "'" . $column->getName() . "'" . '] ??' . " ''" . '); ?></label>' . "\n";
      echo ' </div>' . "\n";
    }
    if ($column->getAbstractType() === 'date') {
      echo '<div class="row mb3 form-group">' . "\n";
      echo '  <label for="' . $column->getName() . '" class="text-bg col-sm-2 col-form-label" style="background:lightblue">' . '<?= $s' . "->trans('" . $column->getName() . "'); ?>";
      echo '  </label>' . "\n";
      echo '<?php $date = $body[' . "'" . $column->getName() . "'" . '];';
      echo ' if ($date && $date != "0000-00-00") {';
      echo '    $datehelper = new DateHelper($s);';
      echo '  $date = $datehelper->date_from_mysql($date);';
      echo '} else {';
      echo '  $date = null;';
      echo '}';
      echo '?>';
      echo '<label class="text-bg col-sm-10 col-form-label"><?= Html::encode($date); ?></label>';
      echo '</div>' . "\n";
    }
  }
}
foreach ($relations as $relation) {
  echo ' <div class="row mb3 form-group">' . "\n";
  echo '   <label for="' . $relation->getLowercase_name() . '_id" class="text-bg col-sm-2 col-form-label" style="background:lightblue">' . '<?= $s' . "->trans('" . $relation->getLowercase_name() . "'); ?>";
  echo '</label>' . "\n";
  echo '<label class="text-bg col-sm-10 col-form-label"><?= $' . $generator->getSmall_singular_name() . '->get' . $relation->getCamelcase_name() . '()->get' . ucfirst($relation->getView_field_name()) . '();?></label>' . "\n";
  echo ' </div>' . "\n";
}
echo '</div>' . "\n";
?>