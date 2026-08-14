<?php

declare(strict_types=1);

use App\Widget\Button;
use App\Widget\IdentityProviderButton;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\Table;
use Yiisoft\Html\Tag\Tr;
use Yiisoft\Html\Tag\Thead;
use Yiisoft\Html\Tag\Td;

/**
 * @var array $codes
 * @var string $csrf
 * @var string|null $error
 * @var App\Auth\Form\TwoFactorAuthenticationVerifyLoginForm $formModel
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Router\CurrentRoute             $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface    $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface  $translator
 */

?>

<!-- 2FA Login Verification View -->
<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card border border-dark shadow-2-strong rounded-3">
                <div class="card-header bg-dark text-white">
                    <h5 class="fw-normal h3 text-center"><?= $translator->translate('two.factor.authentication'); ?></h5>
                </div>
                <div class="card-body p-2 text-center">
                    <h6><?= $translator->translate('two.factor.authentication.new.six.digit.code'); ?></h6>
                </div>
                <div class="card-body p-2 text-center">
                    <?php
                        // Custom CSS styles (inline for demonstration)
                        $style = <<<CSS
                        <style>
                        .recovery-table {
                            border-collapse: collapse;
                            width: 100%;
                            background: #f9f9fb;
                            font-family: 'Segoe UI', Arial, sans-serif;
                            margin-top: 1em;
                            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
                        }
                        .recovery-table th, .recovery-table td {
                            border: 1px solid #e3e3e3;
                            padding: 12px 18px;
                            text-align: left;
                        }
                        .recovery-table th {
                            background: #4F8EF7;
                            color: #fff;
                            letter-spacing: 1px;
                            font-size: 1.05em;
                        }
                        .recovery-table tr:nth-child(even) {
                            background: #f0f4fa;
                        }
                        .recovery-table tr:hover td {
                            background: #e6f2ff;
                        }
                        </style>
                        CSS;

// Table header
$headerRow =  new Thead()
    ->rows(
         new Tr()->dataStrings(['#', $translator->translate('oauth2.backup.recovery.codes')]),
    );
$rows = [];
/**
 * @var string $index
 * @var string $code
 */
foreach ($codes as $index => $code) {
    $rows[] =  new Tr()->cells(
         new Td()->content((string) ((int) $index + 1)),
         new Td()->content(Html::encode($code)),
    );
}

// Render the table with a custom class for styling
echo $style;

if (!empty($codes)) {
    echo  new Table()
        ->header($headerRow)
        ->rows(...$rows)
        ->addAttributes(['class' => 'recovery-table'])
        ->render();
}
?>
                    <?php
    $button = new IdentityProviderButton($translator, $urlGenerator);
$regenerateCodesUrl = $urlGenerator->generate('auth/regenerateCodes');
echo $button->regenerateRecoveryCodes($regenerateCodesUrl);
?>
                </div>
                <div class="card-body p-2 text-center">
                    <?=  new Form()
    ->post($urlGenerator->generate('auth/verifyLogin'))
    ->class('form-floating')
    ->csrf($csrf)
    ->id('twoFactorAuthenticationVerfiyForm')
    ->open(); ?>
                    <?php
    // Six single-digit boxes are the default entry UI for the common
    // 6-digit OTP case (OtpBoxInput, keypad-copy-to-clipboard.ts). Backup
    // recovery codes are 8-character hex (0-9A-F, confirmed against
    // RecoveryCodeService::generateBackupCodes()), which digit-only boxes
    // can't represent — "Use a recovery code instead" swaps to showing
    // the real field below directly, letting it accept either format
    // exactly as it always has.
    echo Html::openTag('div', ['id' => 'otp-boxes-wrap']);
     echo Html::openTag('div', [
         'class' => 'otp-boxes',
         'id' => 'otp-boxes',
         'role' => 'group',
         'aria-label' => $translator->translate('layout.password.otp.verify.6'),
     ]);
      for ($i = 1; $i <= 6; $i++) {
       echo Html::input('tel', null, null, [
           'class' => 'otp-box form-control',
           'inputmode' => 'numeric',
           'pattern' => '[0-9]*',
           'maxlength' => 1,
           'autocomplete' => $i === 1 ? 'one-time-code' : 'off',
           'aria-label' => 'Digit ' . $i . ' of 6',
           'autofocus' => $i === 1,
       ]);
      }
     echo Html::closeTag('div');
    echo Html::closeTag('div');
    echo Html::openTag('div', ['class' => 'mb-2']);
     echo Html::button($translator->translate('layout.password.otp.use.recovery.code'), [
         'type' => 'button',
         'class' => 'btn btn-link btn-sm p-0',
         'id' => 'toggle-recovery-code',
         'data-use-recovery-label' => $translator->translate('layout.password.otp.use.recovery.code'),
         'data-use-code-label' => $translator->translate('layout.password.otp.use.6.digit.code'),
     ]);
    echo Html::closeTag('div');
?>
                    <?= Field::text($formModel, 'code')
    ->addInputAttributes(
        [
            'autocomplete' => 'current-code',
            'id' => 'code',
            'name' => 'code',
            'minlength' => 6,
            // otp = 6 digits, backup recovery code = 8 digits
            'maxlength' => 8,
            'type' => 'tel',
            'class' => 'otp-hidden-field',
        ],
    )
    ->error($error ?? '')
    ->required(true)
    ->labelClass('otp-hidden-field')
    ->addLabelAttributes([
        // Read by handleToggleRecoveryCode() to swap the label's text so
        // it always describes only the format actually expected right
        // now, rather than a combined "OTP / recovery code" line that's
        // irrelevant noise once the user has picked one or the other.
        'data-otp-label' => $translator->translate('layout.password.otp.verify.6'),
        'data-recovery-label' => $translator->translate('layout.password.otp.recovery.8'),
    ])
    ->label($translator->translate('layout.password.otp.verify.6'));
?>
                    <?= Field::submitButton()
    ->buttonId('code-button')
    ->buttonClass('btn btn-primary')
    ->name('code-button')
    ->content($translator->translate('layout.submit')) ?>
                    <?=  new Form()->close() ?>
                </div>
                <div class="card-body p-1 text-center">
                    <?php for ($i = 1; $i <= 9; $i++): ?>
                        <button type="button" class="btn btn-info btn-sm btn-digit" data-digit="<?= $i ?>"><?= $i ?></button>
                    <?php endfor; ?>
                    <button type="button" class="btn btn-info btn-sm btn-digit" data-digit="0">0</button>
                    <button type="button" class="btn btn-info btn-sm btn-clear-otp">Clear</button>
                </div>
            </div>
        </div>
    </div>
</div>