<?php
declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\H6;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Html\Tag\Table;
use Yiisoft\Html\Tag\Tr;
use chillerlan\QRCode\Common\Version;
use chillerlan\QRCode\QRCode;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */
?>
<?= Html::openTag('div', ['class' => 'row']); ?>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h6>
                    <?= A::tag()
                           ->attributes(['style' => 'text-decoration:none'])
                           ->href('https://php-qrcode.readthedocs.io/en/main/')
                           ->content($translator->translate('qr.code')); ?>
                </h6>
            </div>
            <div class="row">
                <div class="panel-body">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[qr_version]" <?= $s->where('qr_version'); ?>>
                                <?= $translator->translate('qr.version'); ?>
                            </label>
                            <?php $body['settings[qr_version]'] = $s->getSetting('qr_version') ?: '40';?>
                           <input type="text" name="settings[qr_version]" id="settings[qr_version]"
                                class="form-control" 
                                value="<?= $body['settings[qr_version]'] ?? (string)Version::AUTO; ?>">
                        </div>
                        <div class="form-group">
                            <label for="settings[qr_ecc_level]">
                                <?= $translator->translate('qr.ecc.level'); ?>
                            </label>
                            <?php $body['settings[qr_ecc_level]'] = $s->getSetting('qr_ecc_level');?>
                            <select name="settings[qr_ecc_level]" id="settings[qr_ecc_level]"
                                class="form-control">
                                <option value="0" <?php $s->check_select($body['settings[qr_ecc_level]'], '0'); ?>><?= 'L'; ?></option>
                                <option value="1" <?php $s->check_select($body['settings[qr_ecc_level]'], '1'); ?>><?= 'M'; ?></option>
                                <option value="2" <?php $s->check_select($body['settings[qr_ecc_level]'], '2'); ?>><?= 'Q'; ?></option>
                                <option value="3" <?php $s->check_select($body['settings[qr_ecc_level]'], '3'); ?>><?= 'H'; ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="settings[qr_height_and_width]" <?= $s->where('qr_height_and_width'); ?>>
                                <?= $translator->translate('qr.height.and.width'); ?>
                            </label>
                            <?php $body['settings[qr_height_and_width]'] = $s->getSetting('qr_height_and_width');?>
                           <input type="text" name="settings[qr_height_and_width]" id="settings[qr_height_and_width]"
                                class="form-control" 
                                value="<?= isset($body['settings[qr_height_and_width]']) && !empty($body['settings[qr_height_and_width]'])
                                        ? (int)$body['settings[qr_height_and_width]'] : 60; ?>">
                        </div>
                        <div class="panel-heading">
                        <?=
                           H6::tag()
                           ->attributes(['class' => 'label label-info'])
                           ->content($translator->translate('qr.code.1'));
?>
                            <pre>
                                Html::openTag('div', ['id' => 'qr_code']);<br>
                                    QrCodeWidget::absoluteUrl($urlGenerator->generateAbsolute('inv/view', [<br>
                                        'id' => $inv_id,<br> 
                                        '_language' => $_language<br>
                                    ]), $translator->translate('qr.code'), 150);<br>
                                Html::closeTag('div');<br>
                            </pre>    
                        </div>
                        <div class="row">
                            <div class="panel-body">
                                <?php
            $pixels = (isset($body['settings[qr_height_and_width]']) && ($body['settings[qr_height_and_width]']))
                     ? (int)$body['settings[qr_height_and_width]'] : 60;
printf(Img::tag()
->width($pixels)
->height($pixels)
->src('%s')
->alt($translator->translate('qr.code'))
->render(), (string)(new QRCode())->render('http://invoice.myhost/invoice/inv/view/6'));
echo Table::tag()
->attributes([ 'class' => 'table table-info table-striped table-bordered'])
->rows(
    Tr::tag()
    ->headerStrings([
        $translator->translate('qr.code.1'),
        $translator->translate('qr.code.details'),
    ]),
    Tr::tag()
    ->dataStrings([
        $translator->translate('qr.code.source'),
        $translator->translate('qr.code.source.path'),
    ]),
    Tr::tag()
    ->dataStrings([
        '*.php',
        'php $company_logo_and_address',
    ]),
    Tr::tag()
    ->dataStrings([
        'Path',
        '\resources\views\invoice\template\invoice\pdf',
    ]),
    Tr::tag()
    ->dataStrings([
        'Controller/action',
        'inv/pdf -> pdfHelper/generate_inv_pdf',
    ]),
    Tr::tag()
    ->dataStrings([
        'src\invoice\Helpers\pdfHelper',
        'generate_inv_pdf -> generate_inv_html',
    ]),
    Tr::tag()
    ->dataStrings([
        $translator->translate('qr.code.type'),
        $translator->translate('qr.code.type.absolute.url'),
    ]),
    Tr::tag()
    ->dataStrings([
        $translator->translate('qr.absolute.url'),
        'http://invoice.myhost/invoice/inv/view/6',
    ]),
    Tr::tag()
    ->dataStrings([
        $translator->translate('qr.meaning'),
        $translator->translate('qr.meaning.benefit'),
    ]),
    Tr::tag()
    ->dataStrings([
        $translator->translate('qr.code.widget.used'),
        '\src\Widget\QrCode.php'
    ]),
    Tr::tag()
    ->dataStrings([
        $translator->translate('qr.code.level.1'),
        '(new QRCode)->render("http://invoice.myhost/invoice/inv/view/6")'
    ]),
    Tr::tag()
    ->dataStrings([
        $translator->translate('qr.code.settings.effect'),
        $translator->translate('qr.code.settings.effect.explanation')
    ]),
)
->render();
?>
                            </div>    
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?= Html::closeTag('div'); ?>

