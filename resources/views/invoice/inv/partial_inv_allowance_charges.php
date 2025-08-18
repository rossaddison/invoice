<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var string $title
 * @var string $inv_allowance_charges
 */
?>
<div class="panel panel-default no-margin">
    <div class="panel-heading">
        <?= $title; ?>
    </div>
    <div class="panel-body clearfix">
        <div class="container">
            <div>
                <?= $inv_allowance_charges; ?>
            </div>
        </div>
    </div> 
</div>    