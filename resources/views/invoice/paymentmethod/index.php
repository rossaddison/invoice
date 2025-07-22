<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Entity\PaymentMethod       $paymentmethod
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array                                  $payment_methods
 * @var bool                                   $canEdit
 * @var string                                 $alert
 * @var string                                 $id
 */
echo $alert;

?>
<div id="headerbar">
    <h1 class="headerbar-title"><?php echo $translator->translate('payment.methods'); ?></h1>
    <div class="headerbar-item pull-right">
        <?php echo Html::a($translator->translate('new'), $urlGenerator->generate('paymentmethod/add'), ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']); ?>
    </div>
</div>

<div id="content" class="table-content">
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
            <tr>
                <th><?php echo $translator->translate('payment.method'); ?></th>
                <th><?php echo $translator->translate('active'); ?></th>
                <th><?php echo $translator->translate('options'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
                /**
                 * @var App\Invoice\Entity\PaymentMethod $payment_method
                 */
                foreach ($payment_methods as $payment_method) { ?>
                <tr>
                    <td><?php echo Html::encode($payment_method->getName()); ?></td>
                    <td><?php echo 1 == $payment_method->getActive() ? '✅' : '❌'; ?></td>
                    <td>
                        <div class="options btn-group">
                        <a class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown" href="#">
                              <i class="fa fa-cog"></i>
                              <?php echo $translator->translate('options'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a href="<?php echo $urlGenerator->generate('paymentmethod/view', ['id' => $payment_method->getId()]); ?>" style="text-decoration:none"><i class="fa fa-eye fa-margin"></i>
                                     <?php echo $translator->translate('view'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $urlGenerator->generate('paymentmethod/edit', ['id' => $payment_method->getId()]); ?>" style="text-decoration:none"><i class="fa fa-edit fa-margin"></i>
                                     <?php echo $translator->translate('edit'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $urlGenerator->generate('paymentmethod/delete', ['id' => $payment_method->getId()]); ?>" style="text-decoration:none" onclick="return confirm('<?php echo $translator->translate('delete.record.warning'); ?>');">
                                     <i class="fa fa-trash fa-margin"></i><?php echo $translator->translate('delete'); ?>                                    
                                </a>
                            </li>
                        </ul>
                        </div>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
