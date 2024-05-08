<?php
    declare(strict_types=1); 
    
    use Yiisoft\Html\Html;
?> 

<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('i.invoice_aging'); ?></h1>
</div>

<div id="content">

    <?= Html::openTag('div', ['class' => 'row']); ?>
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            <?= $alert; ?>

            <div id="report_options" class="panel panel-default">

                <div class="panel-heading">
                    <i class="fa fa-print"></i>
                    <?= $translator->translate('i.report_options'); ?>
                </div>

                <div class="panel-body">
                    <form method="post" action="<?= $urlGenerator->generate(...$action); ?>"
                        <?php echo ($s->get_setting('open_reports_in_new_tab') === '1' ? 'target="_blank"' : ''); ?>>

                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">

                        <input type="submit" class="btn btn-success"
                               name="btn_submit" value="<?= $translator->translate('i.run_report'); ?>">

                    </form>
                </div>

            </div>

        </div>
    </div>

</div>
