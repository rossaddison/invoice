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
                        /*
                         * The input's own `size` attribute (see
                         * addInputAttributes() below) is set too, tied to
                         * maxlength rather than a hand-picked number here --
                         * but Bootstrap's .form-control class hardcodes
                         * `width: 100%`, which overrides the native `size`
                         * sizing entirely (confirmed: size alone, with
                         * .form-control applied, still renders full width).
                         * So max-width still has to be set explicitly here
                         * for .form-control inputs -- size is kept anyway
                         * since it's still the semantically correct
                         * attribute and costs nothing. margin: 0 auto is
                         * needed too, for the same reason -- .form-control
                         * is also display: block, not the plain <input>'s
                         * usual inline default, so the parent's own
                         * text-align: center doesn't center the box itself
                         * (only text inside it), just like a max-width
                         * alone on any other block element wouldn't.
                         */
                        #code {
                            max-width: 260px;
                            margin: 0 auto;
                            font-size: 1.75rem;
                            letter-spacing: 0.3em;
                            text-align: center;
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
                        // No visible label: the heading above already says
                        // "6-digit authentication code ... from your app"
                        // (the 8-digit backup-code option is likewise
                        // already covered by the recovery-codes table/
                        // button above, when relevant), and the floating
                        // label this field used to have just got clipped by
                        // the box below being narrowed for the code font.
                        // Kept as the accessible name via aria-label
                        // instead of disappearing from screen readers too.
                        $codeLabel = $translator->translate(
                            'layout.password.otp.6.8'
                        );
                    ?>
                    <?= Field::text($formModel, 'code')
    ->addInputAttributes(
        [
            'autocomplete' => 'current-code',
            'id' => 'code',
            'name' => 'code',
            // Defaults to the TOTP shape (6 numeric digits) -- the
            // #backupCodeToggle checkbox below switches these to 8 via
            // applyCodeMode() in keypad-copy-to-clipboard.ts when the
            // user says they're entering a backup recovery code instead.
            // A fixed 8 here previously meant nothing stopped over-typing
            // a TOTP code past 6 digits, even though the server (see
            // AuthTfaHelper::sanitizeAndValidateCode()) only ever accepts
            // exactly 6 or exactly 8, never anything in between.
            'minlength' => 6,
            'maxlength' => 6,
            // Visible width in characters, matching maxlength -- the
            // semantically correct native attribute for this, even though
            // Bootstrap's .form-control class (width: 100%) overrides its
            // actual sizing effect here; see the #code CSS rule below,
            // which is what really constrains the box's width.
            'size' => 6,
            'type' => 'tel',
            'aria-label' => $codeLabel,
        ],
    )
    ->error($error ?? '')
    ->required(true)
    ->inputClass('form-control form-control-lg',)
    ->containerClass('mb-3')
    ->label($codeLabel)
    ->hideLabel()
    ->autofocus();
?>
                    <div class="form-check form-check-inline small mt-2">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="backupCodeToggle"
                        >
                        <label class="form-check-label" for="backupCodeToggle">
                            <?= Html::encode($translator->translate(
                                'two.factor.authentication.use.backup.code'
                            )) ?>
                        </label>
                    </div>
                    <?= Field::submitButton()
    ->buttonId('code-button')
    ->buttonClass('btn btn-primary')
    ->name('code-button')
    ->content($translator->translate('layout.submit')) ?>
                    <?=  new Form()->close() ?>
                </div>
                <div id="digitPad" class="card-body p-1 text-center">
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