<?php
    declare(strict_types=1); 
    
    use App\Invoice\Entity\CustomField;
    use Yiisoft\Html\Html;
    use Yiisoft\Html\Tag\Form;
    use DateTimeImmutable;
?>        
    <div>
        <?= $alert; ?>
    </div>    
    <?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('PaymentForm')
    ->open() ?>  

    <div id="headerbar">
        <h1 class="headerbar-title"><?= $s->trans('payment_form'); ?></h1>
        <?php
            $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]);
            echo (string)$response->getBody();
        ?>
    </div>

    <div class="row">
        <div class="mb3 form-group">            
                <div class="col-xs-12 col-sm-2 text-right text-left-xs">
                    <label for="inv_id" class="control-label" required><?= $s->trans('invoice') ." - ". ($body['inv_id'] ?? ''); ?></label>
                </div>
                <div class="col-xs-12 col-sm-6">
                    <?php if ($open_invs_count > 0) { ?>
                        <select name="inv_id" id="inv_id" class="form-control" required <?= ($edit ? 'hidden' : ''); ?>>
                                    <?php foreach ($open_invs as $inv) { 
                                        $inv_amount = $iaR->repoInvquery((int)$inv->getId());                                        
                                    ?>                                        
                                        <option value="<?=  $inv->getId(); ?>"
                                            <?php $s->check_select($body['inv_id'] ?? '', $inv->getId()); ?>>
                                            <?=  $inv->getNumber() . ' - ' . 
                                                 $clienthelper->format_client($cR->repoClientquery($inv->getClient_id())) . 
                                                 ' - ' . 
                                                 $numberhelper->format_currency($inv_amount->getBalance()); 
                                            ?>
                                        </option>
                                    <?php } ?>
                        </select>
                    <?php } else { ?>
                        <select name="inv_id" id="inv_id" class="form-control">
                                        <option value="0"><?= $s->trans('none'); ?></option> 
                        </select>
                    <?php } ?>
                </div>
            
        </div>

        <div class="mb3 form-group">
            <?php
                $pdate = $datehelper->get_or_set_with_style($body['payment_date'] ?? new DateTimeImmutable('now'));                                
            ?>
            <div class="col-xs-12 col-sm-2 text-right text-left-xs">
                <label for="payment_date" class="control-label" required><?= $s->trans('date'); ?></label>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="input-group">
                    <input name="payment_date" id="payment_date" placeholder="<?= ' ('.$datehelper->display().')';?>"
                           class="form-control input-sm datepicker" readonly
                             value="<?= null!== $pdate ? ($pdate instanceof \DateTimeImmutable ? $pdate($datehelper->style()) : $pdate) : null; ?>" role="presentation" autocomplete="off">
                    <span class="input-group-text">
                        <i class="fa fa-calendar fa-fw"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="mb3 form-group">
            <div class="col-xs-12 col-sm-2 text-right text-left-xs">
                <label for="amount" class="control-label" required><?= $s->trans('amount'); ?></label>
            </div>
            <div class="col-xs-12 col-sm-6">               
                <input type="number" name="amount" id="amount" class="form-control" min="0" step=".01" value="<?= ($body['amount'] ?? 0.001); ?>" required>
            </div>
        </div>

        <div class="mb3 form-group">
            <div class="col-xs-12 col-sm-2 text-right text-left-xs">
                <label for="payment_method_id" class="control-label" required>
                    <?= $s->trans('payment_method'); ?>
                </label>
            </div>
            <div class="col-xs-12 col-sm-6 payment-method-wrapper">
                <?php if ($payment_methods) { ?>
                    <select id="payment_method_id" name="payment_method_id" class="form-control" required>
                        <?php foreach ($payment_methods as $payment_method) { ?>
                            <option value="<?=  $payment_method->getId(); ?>"
                                <?php $s->check_select(Html::encode($body['payment_method_id'] ?? ''), $payment_method->getId()) ?>>
                                <?=  $payment_method->getName(); ?>
                            </option>
                        <?php } ?>
                    </select>
                <?php } else { ?>
                    <select name="payment_method_id" id="inv_id" class="form-control">>
                        <option value="0"><?= $s->trans('none'); ?></option> 
                    </select>
                <?php } ?>
            </div>
        </div>
        <div class="mb3 form-group">
            <div class="col-xs-12 col-sm-2 text-right text-left-xs">
                <label for="note" class="control-label" required><?= $s->trans('note'); ?></label>
            </div>
            <div class="col-xs-12 col-sm-6">
                <textarea name="note" class="form-control" required><?=  $body['note'] ?? ''; ?></textarea>
            </div>
        </div>
        <?php foreach ($custom_fields as $custom_field): ?>            
        <div class="mb3 form-group">
        <?php if ($custom_field instanceof CustomField) { ?>
        <?= $cvH->print_field_for_form($payment_custom_values,
                                       $custom_field,
                                       // Custom values to fill drop down list if a dropdown box has been created
                                       $custom_values, 
                                       // Class for div surrounding input
                                       'col-xs-12 col-sm-6',
                                       // Class surrounding above div
                                       'form-group',
                                       // Label class similar to above
                                       'control-label'); ?>
        <?php } ?>    
        </div>    
        <?php endforeach; ?>        
    </div> 
<?= Form::tag()->close() ?>
