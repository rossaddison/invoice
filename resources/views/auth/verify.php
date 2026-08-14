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
                    <h6
                        id="otp-header"
                        data-otp-header="<?= Html::encode($translator->translate('two.factor.authentication.new.six.digit.code')) ?>"
                        data-recovery-header="<?= Html::encode($translator->translate('two.factor.authentication.new.recovery.code')) ?>"
                    ><?= $translator->translate('two.factor.authentication.new.six.digit.code'); ?></h6>
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
    // Two separate box groups (OtpBoxInput, generalized in
    // keypad-copy-to-clipboard.ts to take a character set): 6 digit-only
    // boxes for the common OTP case, 8 hex-character boxes for backup
    // recovery codes (0-9A-F, confirmed against
    // RecoveryCodeService::generateBackupCodes()). "Use a recovery code
    // instead" swaps which group is shown. The real FormModel-bound
    // #code field stays in the DOM throughout, permanently visually
    // hidden — it's purely the sync target both groups write into, so
    // validation, error display and submission all keep working against
    // the same field name the backend already expects, unchanged.
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
    echo Html::openTag('div', ['id' => 'recovery-boxes-wrap', 'class' => 'd-none']);
     echo Html::openTag('div', [
         'class' => 'otp-boxes',
         'id' => 'recovery-boxes',
         'role' => 'group',
         'aria-label' => $translator->translate('layout.password.otp.recovery.8'),
     ]);
      for ($i = 1; $i <= 8; $i++) {
       echo Html::input('text', null, null, [
           'class' => 'otp-box recovery-box form-control',
           'inputmode' => 'text',
           'pattern' => '[0-9A-Fa-f]*',
           'maxlength' => 1,
           'autocomplete' => 'off',
           'aria-label' => 'Character ' . $i . ' of 8',
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
            // otp = 6 digits, backup recovery code = 8 hex characters
            'maxlength' => 8,
            'type' => 'tel',
            'class' => 'otp-hidden-field',
            // Both box groups' aria-labels already describe whichever is
            // actually shown — this is only reached by a screen reader
            // user tabbing directly to the (permanently hidden) sync
            // field itself, so a combined description covers either case.
            'aria-label' => $translator->translate('layout.password.otp.verify.6')
                . ' / ' . $translator->translate('layout.password.otp.recovery.8'),
        ],
    )
    ->error($error ?? '')
    ->required(true)
    ->hideLabel();
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