<?php

    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
    
    /**
     * @see App\Invoice\Client\ClientController function view search partial_notes
     * 
     * @var App\Invoice\Helpers\DateHelper $dateHelper
     * @var array $client_notes
     */
?>

<?php 
    /**
     * @var App\Invoice\Entity\ClientNote
     */
    foreach ($client_notes as $client_note) : ?>
    <div class="panel panel-default small">
        <div class="panel-body">
            <?= nl2br(Html::encode($client_note->getNote())); ?>
        </div>
        <div class="panel-footer text-muted">
            <?= !is_string($dateNote = $client_note->getDate_note()) ? $dateNote->format($dateHelper->style()) : ''; ?>
        </div>
    </div>
<?php endforeach; ?>
