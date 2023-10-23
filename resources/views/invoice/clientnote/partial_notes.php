<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
?>

<?php foreach ($client_notes as $client_note) : ?>
    <div class="panel panel-default small">
        <div class="panel-body">
            <?= nl2br(Html::encode($client_note->getNote())); ?>
        </div>
        <div class="panel-footer text-muted">
            <?= $datehelper->date_from_mysql($client_note->getDate()); ?>
        </div>
    </div>
<?php endforeach; ?>
