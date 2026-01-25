<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Html;



/**
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $errors
 */

?>

<div class="peppol-validation-errors">
    <h2>PEPPOL Validation Errors</h2>
    
    <?php if (empty($errors)): ?>
        <div class="alert alert-success">
            <strong>✓ Valid!</strong> No validation errors found.
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <strong>✗ Invalid!</strong> Found <?= count($errors) ?> validation error(s).
        </div>
    <?php endif; ?>
</div>

<?php echo Html::openTag('table', ['class' => 'table table-striped table-hover']); ?>
<?php echo Html::openTag('thead'); ?>
<?php echo Html::openTag('tr'); ?>
<?php echo Html::th('Error message', ['style' => 'width: 80px;']);
      echo Html::th('Line', ['style' => 'width: 80px;']);
      echo Html::th('XPath Location', ['style' => 'width: 15%;']);
      echo Html::th('Peppol', ['style' => 'width: 15%;']); 
      echo Html::closeTag('tr'); ?>
<?php echo Html::closeTag('thead'); ?>
<?php
    echo Html::openTag('tbody');
    /**
     * @var array $error
     */
    foreach ($errors as $error) {
        $rule = (string) $error['rule'];
        $url = 'https://docs.peppol.eu/poacc/'
                . 'billing/3.0/2025-Q4/rules/ubl-peppol/'. $rule;
        echo Html::openTag('tr');
        echo Html::openTag('td');
        echo Html::b((string) $error['text']);
        echo Html::closeTag('td');
        echo Html::openTag('td');
        echo (string) $error['line'];
        echo Html::closeTag('td');
        echo Html::openTag('td');
        echo (string) $error['xpath'];
        echo Html::closeTag('td');
        echo Html::openTag('td');
        echo A::tag()->href($url)->content($rule)->render();
        echo Html::closeTag('td');
        echo Html::closeTag('tr');
    };
    echo Html::closeTag('tbody');
    echo Html::closeTag('table');
?>


