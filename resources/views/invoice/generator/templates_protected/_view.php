<?php  
    echo "<?php\n";             
?>

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

<?php  
    echo "?>\n";             
    echo '<?= Html::openTag(\'h1\'); ?><?= Html::encode($title) ?><?= Html::closeTag(\'h1\'); ?>'."\n";
    echo "<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>"."\n";
    echo "<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>"."\n";
    echo "<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>"."\n";
    echo "<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>";
    echo "<?= Html::openTag('div',['class'=>'card-header']); ?>"."\n";

    echo "<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>"."\n";
    echo '     <?= $translator->translate('."'i.add'); ?>"."\n";
    echo "<?= Html::closeTag('h1'); ?>"."\n";

    echo "<?= Form::tag()"."\n";
    echo '    ->post($urlGenerator->generate(...$action))'."\n";
    echo "    ->enctypeMultipartFormData()"."\n";
    echo '    ->csrf($csrf)'."\n";
    echo "    ->id('".$generator->getCamelcase_capital_name()."Form')"."\n";
    echo "    ->open()"."\n";
    echo "?>"."\n";
    echo "\n";
    echo '<?= $button::back_save(); ?>'."\n";
    echo "\n";
    echo "<?= Html::openTag('div', ['class' => 'container']); ?>"."\n";
    echo "<?= Html::openTag('div', ['class' => 'row']); ?>"."\n";
    echo "<?= Html::openTag('div', ['class' => 'col card mb-3']); ?>"."\n"; 
    echo "<?= Html::openTag('div',['class' => 'card-header']); ?>"."\n";
    echo "    <?= Html::openTag('h5'); ?>"."\n";
    echo '        <?= Html::encode($title); ?>'."\n";
    echo "    <?= Html::closeTag('h5'); ?>"."\n";
    
    // deal with relations here
    foreach ($relations as $relation){
        echo "    <?= Html::openTag('div'); ?>"."\n";
        echo '        <?= Field::select($'.'form, '."'". $relation->getLowercase_name()  ."_id')"."\n";
        echo "            ->addInputAttributes(["."\n";
        echo "                 'class' => 'form-control'"."\n";
        echo "            ])"."\n";
        echo '            ->value($form->get'.ucfirst($relation->getLowercase_name())."_id())"."\n";                
        echo '            ->prompt($translator->translate(\'i.none\'))'."\n";    
        echo '            ->optionsData($'. $relation->getLowercase_name().'s)'."\n";
        echo '        ?>'."\n";
        echo '    <?= Html::closeTag(\'div\'); ?>'."\n";
       
    }
    
    // exclude relations or fields ending in '_id'
    foreach ($orm_schema->getColumns() as $column) {
        /**
         * If the column is not a relation column ending in _id
         * @see src/Invoice/Entity/Product
         * @see #[Column(type: 'integer(11)', nullable: true)]
         * @see private ?int $family_id = null; 
         */
        if (substr($column, -3) <> '_id') {
            /**
             * @see src/Invoice/Entity/Client
             * @see #[Column(type: 'bool', default: false)]
             * @see private bool $client_active = false; 
             */
            if (($column->getType() === 'bool') && ($column->getAbstractType() === 'bool' )) {  
                echo "    <?= Html::openTag('div'); ?>"."\n";
                echo '        <?= Field::checkbox($form,'. "'". $column->getName()."')"."\n";
                echo "            ->inputLabelAttributes(['class' => 'form-check-label'])"."\n";    
                echo "            ->enclosedByLabel(true)"."\n";
                echo "            ->inputClass('form-check-input')"."\n";
                echo '            ->ariaDescribedBy($translator->translate('."'".$column->getName().'))'."\n";
                echo '        ?>'."\n"; 
                echo '      <?= Html::closeTag(\'div\'); ?>'."\n";
            }
            /**
             * @see src/Invoice/Entity/ClientNote
             * @see #[Column(type: 'date', nullable: false)]
             * @see private mixed $date_note; 
             */
            if (($column->getType() === 'mixed') && (($column->getAbstractType() === 'date' )))
            {
                echo "    <?= Html::openTag('div'); ?>"."\n";
                echo '        <?= Field::date($form,'. "'". $column->getName()."')"."\n";
                echo "            ->label()"."\n";
                echo '            ->value($form->get'. ucfirst($column->getName()). '() ? ($form->get'.ucfirst($column->getName()).'())->format(\'Y-m-d\') : \'\')'."\n";
                echo '         ?>'."\n";
                echo '     <?= Html::closeTag(\'div\'); ?>'."\n";        
            } 
            /**
             * @see src/Invoice/Entity/Product
             * @see #[Column(type: 'decimal(20,2)', nullable: true)]
             * @see private ?float $purchase_price = null; 
             */ 
            if (($column->getType() === 'float') && ($column->getAbstractType() === 'decimal' )) 
            {
                echo "    <?= Html::openTag('div'); ?>"."\n";
                echo '        <?= Field::text($form,'. "'". $column->getName()."')"."\n";
                echo '            ->label($translator->translate('.$column->getName().'))'."\n";    
                echo '            ->addInputAttributes(['."\n";
                echo "                'class' => 'form-control'"."\n";
                echo '            ])'."\n";
                echo '            ->value($s->format_amount((float)($form->get'. ucfirst($column->getName()).'() ?? 0.00)))'."\n";
                echo '            ->placeholder($'.'translator->translate('."'".$column->getName()."'".'))'."\n"; 
                echo '         ?>'."\n";
                echo '     <?= Html::closeTag(\'div\'); ?>'."\n";
            }
            /**
             * @see src/Invoice/Entity/ClientNote
             * @see #[Column(type: 'longText', nullable:false)]
             * @see private string $note =  ''; 
             */
            if (($column->getType() === 'string') && ($column->getAbstractType() <> 'date' )) {
                echo "    <?= Html::openTag('div'); ?>"."\n";
                echo '        <?= Field::text($form,'. "'". $column->getName()."')"."\n";
                echo '            ->label($translator->translate('.$column->getName().'))'."\n";    
                echo '            ->addInputAttributes(['."\n";
                echo "                'class' => 'form-control'"."\n";
                echo '            ])'."\n";
                echo '            ->value(Html::encode(' .'$'.'form->get'.$column->getName().'))'."\n";
                echo '            ->placeholder($'.'translator->translate('."'".$column->getName()."'".'))'."\n";    
                echo '         ?>'."\n";
                echo '    <?= Html::closeTag(\'div\'); ?>'."\n";
            }
            
            if (($column->getType() === 'int') && ($column->getAbstractType() <> 'date' ) && ($column->getAbstractType() <> 'primary' )) {
                echo "    <?= Html::openTag('div'); ?>"."\n";
                echo '        <?= Field::text($form,'. "'". $column->getName()."')"."\n";
                echo '            ->label($translator->translate('.$column->getName().'))'."\n";    
                echo '            ->addInputAttributes(['."\n";
                echo "                'class' => 'form-control'"."\n";
                echo '            ])'."\n";
                echo '            ->value(Html::encode(' .'$'.'form->get'.$column->getName().'))'."\n";
                echo '            ->placeholder($'.'translator->translate('."'".$column->getName()."'".'))'."\n";    
                echo '        ?>'."\n";
                echo '    <?= Html::closeTag(\'div\'); ?>'."\n";
            }
        } // if substr    
    } // foreach columns        
            
    echo "<?= Html::closeTag('form'); ?>"."\n";
    
    echo '<?= Html::closeTag(\'div\'); ?>'."\n";
    echo '<?= Html::closeTag(\'div\'); ?>'."\n";
    echo '<?= Html::closeTag(\'div\'); ?>'."\n";
    echo '<?= Html::closeTag(\'div\'); ?>'."\n";
    echo '<?= Html::closeTag(\'div\'); ?>'."\n";