<?php
    declare(strict_types=1); 
    
    use Yiisoft\Html\Html;
    
    /** 
     * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
     * @var \Yiisoft\Translator\TranslatorInterface $translator
     * @var App\Invoice\Setting\SettingRepository $s
     * @var string $csrf
     */
?>

<?= $alert; ?>

<div id="headerbar">
    <h1 class="headerbar-title"><?= Html::encode($translator->translate('invoice.report.sales.by.product')); ?></h1>
</div>

<div id="content">

    <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            <div id="report_options" class="panel panel-default">

                <div class="panel-heading">
                    <i class="fa fa-print"></i>
                    <?= $s->trans('report_options'); ?>
                </div>

                <div class="panel-body">
                    
                    
                    <form method="POST" action="<?= $urlGenerator->generate(...$action); ?>"  enctype="multipart/form-data"
                       <?php echo ($s->get_setting('open_reports_in_new_tab') === '1' ? 'target="_blank"' : ''); ?>>

                        <input type="hidden" id="_csrf" name="_csrf" value="<?= $csrf ?>">   

                        <div class="mb-3 form-group has-feedback">
                            <?php
                                $from_date = $datehelper->get_or_set_with_style($body['from_date'] ?? $start_tax_year);                                
                            ?>
                            <label for="from_date"><?= $s->trans('from_date') .' ('.$datehelper->display().')'; ?></label>
                            <div class="input-group">
                                <input type="text" name="from_date" id="from_date" placeholder="<?= ' ('.$datehelper->display().')';?>"
                                       class="form-control input-sm datepicker" readonly                   
                                       value="<?= null!== $from_date ? ($from_date instanceof \DateTimeImmutable ? $from_date($datehelper->style()) : $from_date) : null; ?>" role="presentation" autocomplete="off">
                                <span class="input-group-text">
                                <i class="fa fa-calendar fa-fw"></i>
                            </span>
                            </div>        
                        </div>  

                        <div class="mb-3 form-group has-feedback">
                            <?php
                               $to_date = $datehelper->get_or_set_with_style($body['to_date'] ?? new \DateTimeImmutable('now'));
                            ?>
                            <label for="to_date"><?= $s->trans('to_date') .' ('.$datehelper->display().')'; ?></label>
                            <div class="input-group">
                                <input type="text" name="to_date" id="to_date" placeholder="<?= ' ('.$datehelper->display().')';?>"
                                       class="form-control input-sm datepicker" readonly                   
                                       value="<?= null!== $to_date ? ($to_date instanceof \DateTimeImmutable ? $to_date->format($datehelper->style()) : $to_date) : null; ?>" role="presentation" autocomplete="off">
                                <span class="input-group-text">
                                <i class="fa fa-calendar fa-fw"></i>
                            </span>
                            </div>        
                        </div>
                        <input type="submit" class="btn btn-success" name="btn_submit"
                               value="<?= $s->trans('run_report'); ?>">
                    </form>

                </div>

            </div>

        </div>
    </div>

</div>



