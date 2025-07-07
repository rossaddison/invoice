<?php

declare(strict_types=1);

use App\Widget\Button;
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
                        $headerRow = Thead::tag()
                            ->rows(
                                Tr::tag()->dataStrings(['#', $translator->translate('oauth2.backup.recovery.codes')]),
                            );
                        $rows = [];
                        /**
                         * @var string $index
                         * @var string $code
                         */
                        foreach ($codes as $index => $code) {
                            $rows[] = Tr::tag()->cells(
                                Td::tag()->content((string)((int)$index + 1)),
                                Td::tag()->content(Html::encode($code))
                            );
                        }
                        
                        // Render the table with a custom class for styling
                        echo $style;
                        
                        if (!empty($codes)) {
                            echo Table::tag()
                                ->header($headerRow)    
                                ->rows(...$rows)    
                                ->addAttributes(['class' => 'recovery-table'])
                                ->render();
                        }
                    ?>
                    <?php
                        $button = new Button($currentRoute, $translator, $urlGenerator);
                        $regenerateCodesUrl = $urlGenerator->generate('auth/regenerateCodes');
                        echo $button->regenerateRecoveryCodes($regenerateCodesUrl);
                    ?>
                </div>    
                <div class="card-body p-2 text-center">    
                    <?= Form::tag()
                        ->post($urlGenerator->generate('auth/verifyLogin'))
                        ->class('form-floating')
                        ->csrf($csrf)
                        ->id('twoFactorAuthenticationVerfiyForm')
                        ->open(); ?>
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
                            ]
                        )
                        ->error($error ?? '')
                        ->required(true)        
                        ->inputClass('form-control')
                        ->label($translator->translate('layout.password.otp.6.8'))
                        ->autofocus();
                    ?>
                    <?= Field::submitButton()
                        ->buttonId('code-button')
                        ->buttonClass('btn btn-primary')
                        ->name('code-button')
                        ->content($translator->translate('layout.submit')) ?>
                    <?= Form::tag()->close() ?>
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