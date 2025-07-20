<?php

declare(strict_types=1);

/**
 * @see GeneratorController function form
 * @var App\Invoice\Entity\Gentor $generator
 * @var Cycle\Database\Table $orm_schema
 * @var array $relations
 */

echo "<?php\n";
?>

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\<?= $generator->getCamelcase_capital_name(); ?>\<?= $generator->getCamelcase_capital_name(); ?>Form $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $<?= $generator->getSmall_singular_name(); ?>         
 */

<?php
    echo "?>";
echo '<?= Html::openTag(\'h1\'); ?><?= Html::encode($title) ?><?= Html::closeTag(\'h1\'); ?>';
echo "<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>";
echo "<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>";
echo "<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>";
echo "<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>";
echo "<?= Html::openTag('div',['class'=>'card-header']); ?>";

echo "<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>";
echo '<?= $title; ?>';
echo "<?= Html::closeTag('h1'); ?>";

echo "<?= Form::tag()";
echo '->post($urlGenerator->generate($actionName, $actionArguments))';
echo "->enctypeMultipartFormData()";
echo '->csrf($csrf)';
echo "->id('" . $generator->getCamelcase_capital_name() . "Form')";
echo "->open()";
echo "?>";
echo '<?= $button::backSave(); ?>';
echo "<?= Html::openTag('div', ['class' => 'container']); ?>";
echo "<?= Html::openTag('div', ['class' => 'row']); ?>";
echo "<?= Html::openTag('div', ['class' => 'col card mb-3']); ?>";
echo "<?= Html::openTag('div',['class' => 'card-header']); ?>";
echo "      <?= Html::openTag('h5'); ?>";
echo              '<?= Html::encode($title) ?>; ?>';
echo "      <?= Html::closeTag('h5'); ?>";

/**
 * @var App\Invoice\Entity\GentorRelation $relation
 */
foreach ($relations as $relation) {
    echo "    <?= Html::openTag('div'); ?>";
    echo '    <?= Field::select($' . 'form, ' . "'" . ($relation->getLowercase_name() ?? '') . "_id')";
    echo "      ->addInputAttributes([";
    echo "           'class' => 'form-control'";
    echo "      ])";
    echo '      ->value($form->get' . ucfirst($relation->getLowercase_name() ?? '') . "_id())";
    echo '      ->prompt($translator->translate(\'i.none\'))';
    echo '      ->optionsData($' . ($relation->getLowercase_name() ?? '') . 's)';
    echo '    ?>';
}
echo '      <?= Html::closeTag(\'div\'); ?>';

// exclude relations or fields ending in '_id'
/**
 * @var Cycle\Database\ColumnInterface $column
 */
foreach ($orm_schema->getColumns() as $column) {
    /**
     * If the column is not a relation column ending in _id
     * @see src/Invoice/Entity/Product
     * @see #[Column(type: 'integer(11)', nullable: true)]
     * @see private ?int $family_id = null;
     */
    if (substr($column->getName(), -3) <> '_id') {
        /**
         * @see src/Invoice/Entity/Client
         * @see #[Column(type: 'bool', default: false)]
         * @see private bool $client_active = false;
         */
        if (($column->getType() === 'bool') && ($column->getAbstractType() === 'bool')) {
            echo "<?= Html::openTag('div'); ?>";
            echo '<?= Field::checkbox($form,' . "'" . $column->getName() . "')";
            echo "    ->inputLabelAttributes(['class' => 'form-check-label'])";
            echo "    ->inputClass('form-check-input')";
            echo '    ->ariaDescribedBy($translator->translate(' . "'" . $column->getName() . '))';
            echo '?>';
            echo '<?= Html::closeTag(\'div\'); ?>';
        }
        /**
         * @see src/Invoice/Entity/ClientNote
         * @see #[Column(type: 'date', nullable: false)]
         * @see private mixed $date_note;
         */
        if (($column->getType() === 'mixed') && (($column->getAbstractType() === 'date'))) {
            echo "<?= Html::openTag('div'); ?>";
            echo '<?= Field::date($form,' . "'" . $column->getName() . "')";
            echo "    ->label()";
            echo '    ->value($form->get' . ucfirst($column->getName()) . '() ? ($form->get' . ucfirst($column->getName()) . '())->format(\'Y-m-d\') : \'\')';
            echo '?>';
            echo '<?= Html::closeTag(\'div\'); ?>';
        }
        /**
         * @see src/Invoice/Entity/Product
         * @see #[Column(type: 'decimal(20,2)', nullable: true)]
         * @see private ?float $purchase_price = null;
         */
        if (($column->getType() === 'float') && ($column->getAbstractType() === 'decimal')) {
            echo "<?= Html::openTag('div'); ?>";
            echo '<?= Field::text($form,' . "'" . $column->getName() . "')";
            echo '    ->label($translator->translate(' . $column->getName() . '))';
            echo '    ->addInputAttributes([';
            echo "        'class' => 'form-control'";
            echo '    ])';
            echo '    ->value($s->format_amount((float)($form->get' . ucfirst($column->getName()) . '() ?? 0.00)))';
            echo '    ->placeholder($translator->translate(' . "'" . $column->getName() . '))';
            echo '?>';
            echo '<?= Html::closeTag(\'div\'); ?>';
        }
        /**
         * @see src/Invoice/Entity/ClientNote
         * @see #[Column(type: 'longText', nullable:false)]
         * @see private string $note =  '';
         */
        if (($column->getType() === 'string') && ($column->getAbstractType() <> 'date')) {
            echo "<?= Html::openTag('div'); ?>";
            echo '<?= Field::text($form,' . "'" . $column->getName() . "')";
            echo '    ->label($translator->translate(' . $column->getName() . '))';
            echo '    ->addInputAttributes([';
            echo "        'class' => 'form-control'";
            echo '    ])';
            echo '    ->value(Html::encode(' . '$' . 'form->get' . $column->getName() . '()' . '))';
            echo '    ->placeholder($' . 'translator->translate(' . "'" . $column->getName() . '))';
            echo '?>';
            echo '<?= Html::closeTag(\'div\'); ?>';
        }

        if (($column->getType() === 'int') && ($column->getAbstractType() <> 'date') && ($column->getAbstractType() <> 'primary')) {
            echo "<?= Html::openTag('div'); ?>";
            echo '<?= Field::text($form,' . "'" . $column->getName() . "')";
            echo '    ->label($translator->translate(' . $column->getName() . '))';
            echo '    ->addInputAttributes([';
            echo "        'class' => 'form-control'";
            echo '    ])';
            echo '    ->value(Html::encode(' . '$' . 'form->get' . $column->getName() . '))';
            echo '    ->placeholder($' . 'translator->translate(' . "'" . $column->getName() . '))';
            echo '?>';
            echo '<?= Html::closeTag(\'div\'); ?>';
        }
    } // if substr
} // foreach columns

echo "<?= Html::closeTag('form'); ?>";

echo '<?= Html::closeTag(\'div\'); ?>';
echo '<?= Html::closeTag(\'div\'); ?>';
echo '<?= Html::closeTag(\'div\'); ?>';
echo '<?= Html::closeTag(\'div\'); ?>';
echo '<?= Html::closeTag(\'div\'); ?>';
