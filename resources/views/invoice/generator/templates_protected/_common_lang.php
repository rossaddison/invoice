<?php 
   echo "<?php\n";    
?>

declare(strict_types=1);

return [
<?php foreach ($combined_array as $key => $value) {
    $rtrim = rtrim($value, ',');
    echo "'".$key."' => '".$rtrim."',"."\n";
}?>
];