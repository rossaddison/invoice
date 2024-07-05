<?php

    declare(strict_types=1);
    
   /** 
    * @var App\Invoice\Helpers\DateHelper $dateHelper
    * @var App\Invoice\Helpers\NumberHelper $numberHelper
    * @var App\Invoice\Setting\SettingRepository $s
    * @var Yiisoft\Translator\TranslatorInterface $translator
    * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
    * @var string $actionName
    * @var string $alert
    * @var array $body
    * @var string $csrf
    * @var string $startTaxYear
    * @psalm-var array<string, Stringable|null|scalar> $actionArguments
    */
?>

<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('i.payment_history'); ?></h1>
</div>

<div id="content">

    <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            <?= $alert; ?>

            <div id="report_options" class="panel panel-default">

                <div class="panel-heading">
                    <i class="fa fa-print"></i>
                    <?= $translator->translate('i.report_options'); ?>
                </div>

                <div class="panel-body">

                    <form method="post" action="<?= $urlGenerator->generate($actionName, $actionArguments); ?>"
                        <?php echo ($s->get_setting('open_reports_in_new_tab') === '1' ? 'target="_blank"' : ''); ?>>

                        <input type="hidden" name="_csrf" value="<?= $csrf ?>">

                        <div class="mb-3 form-group has-feedback">
                            <label for="from_date"><?= $translator->translate('i.from_date') .' ('.$dateHelper->display().')'; ?></label>
                            <div class="input-group">
                                <input type="text" name="from_date" id="from_date" placeholder="<?= ' ('.$dateHelper->display().')';?>"
                                       class="form-control input-sm datepicker" readonly                   
                                       value="<?= $body['from_date'] = $startTaxYear; ?>" role="presentation" autocomplete="off">
                                <span class="input-group-text">
                                <i class="fa fa-calendar fa-fw"></i>
                            </span>
                            </div>        
                        </div> 

                        <div class="mb-3 form-group has-feedback">
                            <label for="to_date"><?= $translator->translate('i.to_date') .' ('.$dateHelper->display().')'; ?></label>
                            <div class="input-group">
                                <input type="text" name="to_date" id="to_date" placeholder="<?= ' ('.$dateHelper->display().')';?>"
                                       class="form-control input-sm datepicker" readonly                   
                                       value="<?= $body['to_date'] = (new \DateTimeImmutable('now'))->format($dateHelper->style()); ?>" role="presentation" autocomplete="off">
                                <span class="input-group-text">
                                <i class="fa fa-calendar fa-fw"></i>
                            </span>
                            </div>        
                        </div>

                        <input type="submit" class="btn btn-success" name="btn_submit"
                               value="<?= $translator->translate('i.run_report'); ?>">

                    </form>

                </div>

            </div>

        </div>
    </div>

</div>
