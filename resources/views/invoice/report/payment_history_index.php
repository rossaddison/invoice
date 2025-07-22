<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Helpers\DateHelper         $dateHelper
 * @var App\Invoice\Helpers\NumberHelper       $numberHelper
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var string                                 $actionName
 * @var string                                 $alert
 * @var array                                  $body
 * @var string                                 $csrf
 * @var string                                 $startTaxYear
 *
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */
?>

<div id="headerbar">
    <h1 class="headerbar-title"><?php echo $translator->translate('payment.history'); ?></h1>
</div>

<div id="content">

    <div class="row">
        <div class="col-xs-12 col-md-6 col-md-offset-3">

            <?php echo $alert; ?>

            <div id="report_options" class="panel panel-default">

                <div class="panel-heading">
                    <i class="fa fa-print"></i>
                    <?php echo $translator->translate('report.options'); ?>
                </div>

                <div class="panel-body">

                    <form method="post" action="<?php echo $urlGenerator->generate($actionName, $actionArguments); ?>"
                        <?php echo '1' === $s->getSetting('open_reports_in_new_tab') ? 'target="_blank"' : ''; ?>>

                        <input type="hidden" name="_csrf" value="<?php echo $csrf; ?>">

                        <div class="mb-3 form-group has-feedback">
                            <label for="from_date"><?php echo $translator->translate('from.date').' ('.$dateHelper->display().')'; ?></label>
                            <div class="input-group">
                                <input type="text" name="from_date" id="from_date" placeholder="<?php echo ' ('.$dateHelper->display().')'; ?>"
                                       class="form-control" readonly                   
                                       value="<?php echo $body['from_date'] = $startTaxYear; ?>" role="presentation" autocomplete="off">
                                <span class="input-group-text">
                                <i class="fa fa-calendar fa-fw"></i>
                            </span>
                            </div>        
                        </div> 

                        <div class="mb-3 form-group has-feedback">
                            <label for="to_date"><?php echo $translator->translate('to.date').' ('.$dateHelper->display().')'; ?></label>
                            <div class="input-group">
                                <input type="text" name="to_date" id="to_date" placeholder="<?php echo ' ('.$dateHelper->display().')'; ?>"
                                       class="form-control" readonly                   
                                       value="<?php echo $body['to_date'] = (new DateTimeImmutable('now'))->format('Y-m-d'); ?>" role="presentation" autocomplete="off">
                                <span class="input-group-text">
                                <i class="fa fa-calendar fa-fw"></i>
                            </span>
                            </div>        
                        </div>

                        <input type="submit" class="btn btn-success" name="btn_submit"
                               value="<?php echo $translator->translate('run.report'); ?>">
                   </form>

                </div>

            </div>

        </div>
    </div>

</div>
