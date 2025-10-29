<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see App\Invoice\Client\ClientController function view search partial_notes
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
        <div class="panel-body position-relative" style="padding-right: 35px;">
            <?= nl2br(Html::encode($client_note->getNote())); ?>
            <button type="button" 
                    class="btn btn-danger btn-sm position-absolute client-note-delete-btn" 
                    data-note-id="<?= $client_note->getId(); ?>" 
                    style="top: 5px; right: 5px; padding: 2px 6px; font-size: 10px; width: 24px; height: 24px; line-height: 1; z-index: 10;"
                    title="Delete note">
                <i class="fa fa-times" style="font-size: 10px;"></i>
            </button>
        </div>
        <div class="panel-footer text-muted">
            <?= !is_string($dateNote = $client_note->getDate_note()) ? $dateNote->format('Y-m-d') : ''; ?>
        </div>
    </div>
<?php endforeach; ?>
