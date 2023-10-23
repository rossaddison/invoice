<?php
    declare(strict_types=1);
?>

<?php foreach ($custom_fields as $custom_field): ?>
<?php if ($custom_field->getLocation() == 1) {continue;} ?>
<div>
      <?= $cvH->print_field_for_pdf($inv_custom_values, $custom_field, $cvR); ?>
</div>    
<?php endforeach; ?>        
 