<?php
declare(strict_types=1);

use App\Invoice\Asset\ReportAsset;
use Yiisoft\Html\Html;

/**
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
<html lang="<?= $translator->translate('i.cldr'); ?>">
<head>
    <title><?= Html::encode($translator->translate('i.payment_history')); ?></title>
</head>
<body>
<?php $this->beginBody(); ?> 
<h3 class="report_title">
    <?= Html::encode($translator->translate('i.payment_history')); ?><br>
    <small><?= $from_date . ' - ' . $to_date; ?></small>
</h3>

<table>
    <tr>
        <th><?= $translator->translate('i.date'); ?></th>
        <th><?= $translator->translate('i.invoice'); ?></th>
        <th><?= $translator->translate('i.client'); ?></th>
        <th><?= $translator->translate('i.payment_method'); ?></th>
        <th><?= $translator->translate('i.note'); ?></th>
        <th class="amount"><?= $translator->translate('i.amount'); ?></th>
    </tr>
    <?php
    $sum = 0.00;
    /**
     * @var DateTimeImmutable $result['payment_date']
     * @var string $result['payment_invoice']
     * @var string $result['payment_client']
     * @var string $result['payment_method'
     * @var string $result['payment_note']
     * @var float $result['payment_amount']
     * @var array $result
     */
    foreach ($results as $result) {
        ?>
        <tr>
            <td style="width:15%;"><?= ($result['payment_date'])->format('Y-m-d'); ?></td>
            <td style="width:15%;"><?= $result['payment_invoice']; ?></td>
            <td style="width:15%;"><?= $result['payment_client']; ?></td>
            <td style="width:15%;"><?= Html::encode($result['payment_method']); ?></td>
            <td style="width:15%;"><?= nl2br(Html::encode($result['payment_note'])); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?= $numberHelper->format_currency($result['payment_amount']);
                $sum = $sum + $result['payment_amount']; ?></td>
        </tr>
        <?php
    }

    if (!empty($results)) {
        ?>
        <tr>
            <td colspan=5><?= $translator->translate('i.total'); ?></td>
            <td style="width:15%;text-align:right;border-bottom: 0px solid black;"><?= $numberHelper->format_currency($sum); ?></td>
        </tr>
    <?php } ?>
</table>
<?php $this->endBody(); ?>
</body>
</html>
<?php $this->endPage(true); ?>