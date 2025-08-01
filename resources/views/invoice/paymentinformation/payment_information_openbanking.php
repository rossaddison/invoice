<?php

use App\Invoice\Setting\SettingRepository as sR;
use Yiisoft\Html\Html;
use Yiisoft\Translator\Translator;
use Yiisoft\Router\FastRoute\UrlGenerator;

/**
 * @var sR           $s
 * @var Translator   $translator
 * @var UrlGenerator $urlGenerator
 * @var string       $alert
 * @var string       $title
 * @var float        $balance
 * @var float        $total
 * @var string       $authUrl
 * @var string       $client_chosen_gateway
 * @var array|object $client_on_invoice
 * @var object|array $invoice
 * @var string       $inv_url_key
 * @var bool         $authToken
 * @var bool         $is_overdue
 * @var bool         $disable_form
 * @var string       $companyLogo
 * @var string       $partial_client_address
 * @var string       $payment_method
 * @var string       $provider
 * @var string       $json_encoded_items
 * @var string       $wonderfulId
 * @var string       $amountFormatted
 * @var string       $reference
 * @var string       $createdAt
 * @var string       $updatedAt
 * @var string       $status
 * @var string       $paymentLink
 */

$clientName = '';
if (is_object($client_on_invoice) && method_exists($client_on_invoice, 'getClient_name')) {
    /** @var string|null $maybeName */
    $maybeName = $client_on_invoice->getClient_name();
    if (is_string($maybeName)) {
        $clientName = $maybeName;
    }
} elseif (is_array($client_on_invoice) && isset($client_on_invoice['client_name']) && is_string($client_on_invoice['client_name'])) {
    $clientName = $client_on_invoice['client_name'];
}

?>

<?= Html::openTag('div', ['class' => 'container py-4']); ?>
    <!-- Alert message -->
    <?php if (!empty($alert)): ?>
        <div class="mb-3">
            <?= $alert; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <div class="d-flex align-items-center">
                <?php if (!empty($companyLogo)): ?>
                    <span class="me-3"><?= $companyLogo; ?></span>
                <?php endif; ?>
                <h2 class="mb-0"><?= Html::encode($title); ?></h2>
            </div>
        </div>
        <div class="card-body">

            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="fs-4 mb-0">
                        <strong><?= Html::encode($translator->translate('amount.payment')) ?>:</strong>
                        <span class="text-success fw-bold display-6" style="letter-spacing:1px;">
                            <?= Html::encode($s->format_currency($balance)); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p>
                        <strong><?= Html::encode($translator->translate('client')); ?>:</strong>
                        <?= Html::encode($clientName); ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($partial_client_address)): ?>
                <div class="mb-2"><?= $partial_client_address; ?></div>
            <?php endif; ?>

            <p>
                <strong><?= Html::encode($translator->translate('invoice')) ?>:</strong>
                <?= Html::encode($inv_url_key); ?>
            </p>

            <?php if ($is_overdue): ?>
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?= Html::encode($translator->translate('invoice.is.overdue')); ?>
                </div>
            <?php endif; ?>

            <?php if ($disable_form): ?>
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle-fill"></i>
                    <?= Html::encode($translator->translate('form.disabled.already.paid')); ?>
                </div>
            <?php endif; ?>

            <!-- Payment Actions -->
            <div class="mt-4">
                <?php if (!empty($authUrl) && !$disable_form): ?>
                    <a href="<?= $authUrl; ?>"
                       class="btn btn-primary btn-lg"
                       rel="noopener noreferrer"
                       target="_blank">
                        <?= Html::encode($translator->translate('open.banking.pay.with') . $provider); ?>
                    </a>
                <?php elseif ($disable_form): ?>
                    <p class="text-muted">
                        <?= Html::encode($translator->translate('open.banking.payment.not.required')); ?>
                    </p>
                <?php elseif ($authToken): ?>
                    <div class="wonderful-payment-summary mb-3">
                        <h4 class="mb-3"><?= Html::encode($translator->translate('details')); ?></h4>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <tbody>
                                    <tr>
                                        <th scope="row"><?= 'Wonderful Id'; ?></th>
                                        <td><?= Html::encode($wonderfulId); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?= Html::encode($translator->translate('Amount')); ?></th>
                                        <td>
                                            <span class="text-success fw-bold fs-5">
                                                <?= Html::encode($amountFormatted); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?= Html::encode($translator->translate('Status')); ?></th>
                                        <td><?= Html::encode(ucfirst($status)); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?= Html::encode($translator->translate('Reference')); ?></th>
                                        <td><?= Html::encode($reference); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?= Html::encode($translator->translate('Created At')); ?></th>
                                        <td><?= Html::encode($createdAt); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if ('created' == $status): ?>
                        <a href="<?= Html::encode($paymentLink); ?>"
                           class="btn btn-success btn-lg"
                           rel="noopener noreferrer"
                           target="_blank">
                            <?= Html::encode($translator->translate('open.banking.pay.with') . ' Wonderful'); ?>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">
                        <?= Html::encode($translator->translate('open.banking.not.configured')); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?= Html::closeTag('div'); ?>