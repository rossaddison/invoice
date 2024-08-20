<?php

declare(strict_types=1);

namespace App\Invoice\Helpers;

use App\Invoice\CustomValue\CustomValueRepository as cvR;
use App\Invoice\Entity\CustomField;
use App\Invoice\Entity\CustomValue;
use App\Invoice\Setting\SettingRepository as SRepo;
use App\Invoice\Helpers\DateHelper as DHelp;
use Yiisoft\FormModel\Field;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\B;
use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Select;
use Yiisoft\Translator\TranslatorInterface as Translator;

Class CustomValuesHelper {

    private SRepo $s;
    private DHelp $d;

    public function __construct(SRepo $s) {
        $this->s = $s;
        $this->d = new DHelp($s);
    }

    public function format_date(mixed $txt): string {
        if ($txt == null) {
            return '';
        }
        /** @var \DateTimeImmutable $txt */
        return $this->d->date_from_mysql($txt);
    }

    /**
     * @param $txt
     * @return string
     */
    public function format_text(string|null $txt): string {
        if ($txt == null) {
            return '';
        }
        return $txt;
    }

    /**
     * @param Translator $translator
     * @param string $txt
     * 
     * @return string
     */
    public function format_boolean(Translator $translator, string $txt) : string {
        if ($txt === "1") {
            return $translator->translate('i.true');
        } else if ($txt === "0") {
            return $translator->translate('i.false');
        }
        return $translator->translate('i.false');
    }

    /**
     * @param $txt
     * @return string
     */
    public function format_avs(string $txt) {
        $matches = [];
        if (!preg_match('/(\d{3})(\d{4})(\d{4})(\d{2})/', $txt, $matches)) {
            return $txt;
        }
        return $matches[1] . "." . $matches[2] . "." . $matches[3] . "." . $matches[4];
    }

    /**
     * @param $txt
     * @return string
     */
    public function format_fallback(string $txt) : string {
        return $this->format_text($txt);
    }

    // Note: $custom_value can be an array of dropdown list values
    // eg see payment/_form.php 
    public function print_field_for_form(CustomField $custom_field,
                                         FormModel $formModel,
                                         Translator $translator,   
                                         array $entity_custom_values, 
                                         array  $custom_value
                                        ): void 
    {
        $fieldValue =   null!==$this->form_value($entity_custom_values, $custom_field->getId()) 
                        ? $this->form_value($entity_custom_values, $custom_field->getId())
                        : gettype($this->form_value($entity_custom_values, $custom_field->getId()));
        
        switch ($custom_field->getType()) {
        case 'DATE':
            $dateValue = $fieldValue == "" ? "" : $fieldValue;                    

            echo Field::date($formModel, 'custom_field_id')
            ->label($custom_field->getLabel())                        
            ->addInputAttributes([
                'name' => 'custom['. $custom_field->getId().']',
                'id' => $custom_field->getId()    
            ])
            ->required($custom_field->getRequired() == 1 ? true : false)    
            ->hint($custom_field->getRequired() == 1 
                   ? $translator->translate('invoice.hint.this.field.is.required')
                   : $translator->translate('invoice.hint.this.field.is.not.required'))
            ->value($dateValue); 

            break;
        
        // Select only one item from the drop-down list
        case 'SINGLE-CHOICE':
            /** @var array $choices */
            $choices = $custom_value[$custom_field->getId()];

            $optionsData = [];
            /** @var CustomValue $choice */    
            foreach ($choices as $choice) {
                $optionsData[(int)$choice->getId()] = Html::encode($choice->getValue());
            }    

            echo Label::tag()
            ->forId('custom['.$custom_field->getId().']')
            ->content(Html::encode($custom_field->getLabel()));            

            echo Select::tag()
            ->addAttributes(
                [
                    'id' => $custom_field->getId(),
                    'name' => 'custom['.$custom_field->getId().']',
                    'class' => 'form-control'
                ]    
            )
            ->disabled(false)    
            ->optionsData($optionsData)
            ->multiple(false)    
            ->required($custom_field->getRequired() == 1 ? true : false)
            ->value($fieldValue ?? '');    
            break;

        // Select more than one item from the drop-down    
        case 'MULTIPLE-CHOICE':
            /** @var array $choices */
            $choices = $custom_value[$custom_field->getId()];
            // Previously selected choices as arrays ie. selChoices, that have been 
            // serialized to mySql, must now be highlighted (greyed) in the dropdown
            // The mySql serialized $fieldValue eg. a:2:{i:0;s:2:"41";i:1;s:2:"43";} 
            // must now be unserialized to an array and placed in '->values($selChoices)'
            // Search 'serialize' in e.g. src/Invoice/Client/ClientController
            $selChoices = $this->is_serialized($fieldValue, true) ? (array)unserialize((string)$fieldValue) : [];
            $optionsData = [];
            /** @var CustomValue $choice */    
            foreach ($choices as $choice) {
                $optionsData[(int)$choice->getId()] = Html::encode($choice->getValue());
            }

            echo Label::tag()
            ->forId('custom['.$custom_field->getId().']')
            ->content(Html::encode($custom_field->getLabel()));            
            
            /**
             * @psalm-suppress PossiblyInvalidArgument $selChoices
             */
            echo Select::tag()
            ->addAttributes([
                'class' => 'form-control',
                'id' => $custom_field->getId(),
                'name' => 'custom['.$custom_field->getId().']'
            ])
            ->disabled(false)    
            ->multiple(true)
            ->optionsData($optionsData)
            ->required($custom_field->getRequired() == 1 ? true : false)
            ->values($selChoices);    
            break;
        
        case 'BOOLEAN':
            echo Field::checkbox($formModel, 'custom_field_id')
            ->addInputAttributes([
                'name' => 'custom['. $custom_field->getId().']',
                'id' => $custom_field->getId()    
            ])
            ->disabled(false)
            ->enclosedByLabel(true)
            ->inputClass('form-check-input')
            ->inputLabelAttributes(['class' => 'form-check-label']) 
            ->inputLabel($custom_field->getLabel())
            ->value($fieldValue);
            break;
            
        case 'NUMBER':
            echo Field::number($formModel, 
                               'custom_field_id', 
                               [],
                               //$this->s->get_config_theme_bootstrap5_horizontal(),
                               'bootstrap5-vertical')
            ->label($custom_field->getLabel())
            ->addInputAttributes([
                'name' => 'custom['. $custom_field->getId().']',
                'id' => $custom_field->getId()    
            ])
            ->value((int)$fieldValue ?: 0)
            ->required($custom_field->getRequired() == 1 ? true : false)    
            ->hint($custom_field->getRequired() == 1 
                ? $translator->translate('invoice.hint.this.field.is.required')
                : $translator->translate('invoice.hint.this.field.is.not.required'));
            break;
            
        default:
            echo Field::text($formModel, 'custom_field_id')
            ->label($custom_field->getLabel())
            ->required(true)
            ->addInputAttributes([
                'name' => 'custom['. $custom_field->getId().']',
                'id' => $custom_field->getId()    
            ])
            ->required($custom_field->getRequired() == 1 ? true : false)    
            ->hint($custom_field->getRequired() == 1 
               ? $translator->translate('invoice.hint.this.field.is.required')
               : $translator->translate('invoice.hint.this.field.is.not.required'))
            ->value(Html::encode((string)$fieldValue ?: ''));   
        }
    }
    
    /**
     * @param CustomField $custom_field
     * @param FormModel $formModel
     * @param array $entity_custom_values
     * @param array $custom_value
     * @return void
     */
    public function print_field_for_view(CustomField $custom_field, FormModel $formModel, array $entity_custom_values, array $custom_value): void {
        $fieldValue = null!==$this->form_value($entity_custom_values, $custom_field->getId()) 
                      ? $this->form_value($entity_custom_values, $custom_field->getId()) 
                      : '';
        switch ($custom_field->getType()) {
        case 'DATE':
            $dateValue = $fieldValue == "" ? "" : $fieldValue;                    

            echo Field::date($formModel, 'custom_field_id')
            ->label($custom_field->getLabel())                        
            ->addInputAttributes([
                'name' => 'custom['. $custom_field->getId().']',
                'id' => $custom_field->getId(),
            ])
            ->disabled(true)        
            ->value($dateValue); 

            break;
        case 'SINGLE-CHOICE':
            /** @var array $choices */
            $choices = $custom_value[$custom_field->getId()];
            $optionsData = [];
            /** @var CustomValue $choice */    
            foreach ($choices as $choice) {
                $optionsData[(int)$choice->getId()] = Html::encode($choice->getValue());
            }    

            echo Label::tag()
            ->forId('custom['.$custom_field->getId().']')
            ->content(Html::encode($custom_field->getLabel()));            
            if (null!== $fieldValue) { 
                echo Select::tag()
                ->addAttributes(
                    [
                        'id' => $custom_field->getId(),
                        'name' => 'custom['.$custom_field->getId().']',
                        'class' => 'form-control'
                    ]    
                )
                ->disabled(true)    
                ->optionsData($optionsData)
                ->multiple(false)
                ->disabled(true)
                ->required($custom_field->getRequired() == 1 ? true : false)
                ->value($fieldValue);
            } else {
                echo '';
            }    
            break;   
        case 'MULTIPLE-CHOICE':
            /** @var array $choices */
            $choices = $custom_value[$custom_field->getId()];
           
            $selChoices = $this->is_serialized($fieldValue, true) ? (array)unserialize((string)$fieldValue) : [];
            
            $optionsData = [];
            /** @var CustomValue $choice */    
            foreach ($choices as $choice) {
                $optionsData[(int)$choice->getId()] = Html::encode($choice->getValue());
            }

            echo Label::tag()
            ->forId('custom['.$custom_field->getId().']')
            ->content(Html::encode($custom_field->getLabel()));            
            
            /**
             * @psalm-suppress PossiblyInvalidArgument $selChoices
             */
            echo Select::tag()
                ->addAttributes([
                'class' => 'form-control',
                'id' => $custom_field->getId(),
                'name' => 'custom['.$custom_field->getId().']'
            ])
            ->disabled(true)    
            ->multiple(true)
            ->optionsData($optionsData)
            ->required($custom_field->getRequired() == 1 ? true : false)            
            ->values($selChoices);
            break;
        case 'BOOLEAN':
            echo Field::checkbox($formModel, 'custom_field_id')
            ->addInputAttributes([
                'name' => 'custom['. $custom_field->getId().']',
                'id' => $custom_field->getId(),
            ])    
            ->disabled(true)
            ->enclosedByLabel(true)
            ->inputLabelAttributes(['class' => 'form-check-label']) 
            ->inputLabel($custom_field->getLabel())
            ->inputClass('form-check-input')
            ->value((string)$fieldValue ?: '0');
            break;
        case 'NUMBER':
            echo Field::number($formModel, 'custom_field_id')
            ->disabled(true)
            ->label($custom_field->getLabel())
            ->value(Html::encode((int)$fieldValue ?: 0));
            break;
        default:
            echo Field::text($formModel, 'custom_field_id')
            ->disabled(true)
            ->label($custom_field->getLabel())
            ->value(Html::encode((string)$fieldValue ?: ''));
        }
    }    
    
    /**
     * @param Translator $translator
     * @param array $entity_custom_values
     * @param CustomField $custom_field
     * @param cvR $cvR
     * @return void
     */
    public function print_field_for_pdf(Translator $translator, array $entity_custom_values, CustomField $custom_field, cvR $cvR): void 
    {
        echo Br::tag();
        $content = Label::tag()->content(Html::encode($custom_field->getLabel()));
        echo B::tag()
             ->content($content)
             ->render();

        $fieldValue = null!==$this->form_value($entity_custom_values, $custom_field->getId()) 
                      ? $this->form_value($entity_custom_values, $custom_field->getId())
                      : gettype($this->form_value($entity_custom_values, $custom_field->getId()));

        echo Html::openTag('div');
            switch ($custom_field->getType()) {
                case 'DATE':
                    $dateValue = $fieldValue == "" ? "" : $fieldValue;                    
                    echo Label::tag()
                    ->content((string)$dateValue);
                    echo Br::tag();
                    echo Br::tag();
                    break;
                case 'SINGLE-CHOICE':
                    echo Label::tag()
                    ->content((string)$this->selected_value($entity_custom_values,$custom_field->getId(),$cvR));    
                    echo Br::tag();    
                    break;
                case 'MULTIPLE-CHOICE':
                    if ($this->is_serialized($fieldValue, true)) {
                        $array = (array)unserialize((string)$fieldValue);
                        /**
                         * @var int $key
                         * @var string $value
                         */
                        foreach( $array as $key => $value) { 
                            $custom_value = $cvR->repoCustomValuequery($value);
                            if (null!==$custom_value) {
                                $customValue = $custom_value->getValue();
                                echo Label::tag()
                                ->content($customValue);   
                                echo Br::tag();
                            }    
                        }
                    }
                    break;
                case 'BOOLEAN':
                    echo Label::tag()
                    ->content((null!==$this->form_value($entity_custom_values,$custom_field->getId()) 
                                      ? $translator->translate('i.true') 
                                      : $translator->translate('i.false')));
                    echo Br::tag();
                    break;
                case 'NUMBER':
                    echo Div::tag()
                    ->content(Html::encode($fieldValue)); 
                    echo Br::tag();
                    break;
                default:
                    echo Div::tag()
                    ->content(Html::encode($fieldValue)); 
                    echo Br::tag(); 
            } 
        echo Html::closeTag('div');
    }
    
    /**
     * @link https://developer.wordpress.org/reference/functions/is_serialized/
     * @param mixed $entry_data
     * @param bool $strict
     * @return bool
     */
    public function is_serialized(mixed $entry_data, $strict = true) : bool {
	// If it isn't a string, it isn't serialized.
	if ( ! is_string( $entry_data ) ) {
		return false;
	}
	$data = trim( $entry_data );
	if ( 'N;' === $data ) {
		return true;
	}
	if ( strlen( $data ) < 4 ) {
		return false;
	}
	if ( ':' !== $data[1] ) {
		return false;
	}
	if ( $strict ) {
		$lastc = substr( $data, -1 );
		if ( ';' !== $lastc && '}' !== $lastc ) {
			return false;
		}
	} else {
		$semicolon = strpos( $data, ';' );
		$brace     = strpos( $data, '}' );
		// Either ; or } must exist.
		if ( false === $semicolon && false === $brace ) {
			return false;
		}
		// But neither must be in the first X characters.
		if ( false !== $semicolon && $semicolon < 3 ) {
			return false;
		}
		if ( false !== $brace && $brace < 4 ) {
			return false;
		}
	}
	$token = $data[0];
	switch ( $token ) {
		case 's':
			if ( $strict ) {
				if ( '"' !== substr( $data, -2, 1 ) ) {
					return false;
				}
			} elseif ( ! str_contains( $data, '"' ) ) {
				return false;
			}
			// Or else fall through.
		case 'a':
		case 'O':
		case 'E':
			return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
		case 'b':
		case 'i':
		case 'd':
			$end = $strict ? '$' : '';
			return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
	}
	return false;
    }
    
    /**
     * Return the value of the custom field in the relevant custom table e.g. inv_custom
     * Note the string could be serialized; Normally containing the multiple 
     * values of a Multiple Choice Dropdown
     * @param array $entity_custom_values
     * @param string $custom_field_id
     * @return string|int|null
     */
    public function form_value(array $entity_custom_values, string $custom_field_id) : string|int|null {                                                                                                                                         
        /** @var CustomValue $entity_custom_value */
        foreach ($entity_custom_values as $entity_custom_value) {
            if ($entity_custom_value->getCustom_field_id() == $custom_field_id) {
                return $entity_custom_value->getValue();
            }
        }
        return null;
    }
    
    /**
     * @param array $entity_custom_values
     * @param string $custom_field_id
     * @param cvR $cvR
     * @return string|int|null
     */           
    public function selected_value(array $entity_custom_values, string $custom_field_id, cvR $cvR) : string|int|null {
      $form_custom_value = $this->form_value($entity_custom_values, $custom_field_id);
      if (($form_custom_value !== '') && (null!==$form_custom_value)) {
        $custom_value = $cvR->repoCustomValuequery((string)$form_custom_value);
        /** @var CustomValue $custom_value */
        return $custom_value->getValue();
      } 
      return '';
    }
}
