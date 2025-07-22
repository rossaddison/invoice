<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use Yiisoft\Html\Html;

/*
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Assets\AssetManager $assetManager
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var string $from_date
 * @var string $to_date
 * @var array $results
 */

$assetManager->register(ReportAsset::class);

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?php echo $translator->translate('cldr'); ?>">
<head>
    <title><?php echo Html::encode($translator->translate('payment.history')); ?></title>
</head>
<body>
<?php $this->beginBody(); ?> 
<h3 class="report_title">
    <?php echo Html::encode($translator->translate('payment.history')); ?><br>
    <small><?php echo $from_date.' - '.$to_date; ?></small>
</h3>

<table>
    <tr>
        <th><?php echo $translator->translate('date'); ?></th>
        <th><?php echo $translator->translate('invoice'); ?></th>
        <th><?php echo $translator->translate('client'); ?></th>
        <th><?php echo $translator->translate('payment.method'); ?></th>
        <th><?php echo $translator->translate('note'); ?></th>
        <th class="amount"><?php echo $translator->translate('amount'); ?></th>
    </tr>
    <?php
    $sum = 0.00;
/**
 * @var DateTimeImmutable $result['payment_date']
 * @var string            $result['payment_invoice']
 * @var string            $result['payment_client']
 * @var string            $result['payment_method'
 * @var string            $result['payment_note']
 * @var float             $result['payment_amount']
 * @var array             $result
 */
foreach ($results as $result) {
    ?>
        <tr>
            <td style="width:15%;"><?php echo $result['payment_date']->format('Y-m-d'); ?></td>
            <td style="width:15%;"><?php echo $result['payment_invoice']; ?></td>
            <td style="width:15%;"><?php echo $result['payment_client']; ?></td>
            <td style="width:15%;"><?php echo Html::encode($result['payment_method']); ?></td>
            <td style="width:15%;"><?php echo nl2br(Html::encode($result['payment_note'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?php echo $numberHelper->format_currency($result['payment_amount']);
    $sum = $sum + $result['payment_amount']; ?></td>
        </tr>
        <?php
}

if (!empty($results)) {
    ?>
        <tr>
            <td colspan=5><?php echo $translator->translate('total'); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?php echo $numberHelper->format_currency($sum); ?></td>
        </tr>
    <?php } ?>
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true); ?>