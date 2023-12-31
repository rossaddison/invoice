<?php
    declare(strict_types=1);
    
    use Yiisoft\Html\Html;
    
    /**
    * @var \Yiisoft\View\View $this
    * @var array $body
    * @var string $csrf
    * @var string $action
    * @var string $title
    */
    
    echo $alert;
?>

<form method="post" id="task-form">
    <input type="hidden" name="_csrf" value="<?= $csrf; ?>">
    <div id="headerbar">
        <h1 class="headerbar-title"><?php $translator->translate('i.tasks_form'); ?></h1>
        <?php
            $response = $head->renderPartial('invoice/layout/header_buttons',['s'=>$s, 'hide_submit_button'=>false ,'hide_cancel_button'=>false]);
            echo (string)$response->getBody();
        ?>
    </div>
    <div id="content">
        <?php 
            $body['id'] = $body['id'] ?? '';            
        ?>
        <?php if ($body['id'] && $body['status'] === 4) : ?>
            <div class="alert alert-warning small"><?= $translator->translate('i.info_task_readonly') ?></div>
        <?php endif ?>

        <?= Html::openTag('div', ['class' => 'row']); ?>
            <div class="col-xs-12 col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php if ($body['id']) : ?>
                            #<?= $body['id']; ?>&nbsp;
                            <?= $body['name'] ?? ''; ?>
                        <?php else : ?>
                            <?= $translator->translate('i.new_task'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label for="name"><?= $translator->translate('i.task_name'); ?></label>
                            <input type="text" name="name" id="name" class="form-control has-feedback" autocomplete="on" required
                                   value="<?= $body['name'] ?? ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="description"><?= $translator->translate('i.task_description'); ?></label>
                            <textarea name="description" id="description" class="form-control has-feedback" required rows="3"
                            ><?= $body['description'] ?? ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="price"><?= $translator->translate('i.task_price'); ?></label>
                            <div class="input-group">
                                <input type="number" name="price" id="price" class="form-control" min="0" step=".500" required 
                                       value="<?php echo (float)$numberhelper->format_amount($body['price'] ?? "0.001"); ?>">
                                <div class="input-group-text">
                                    <?= $s->get_setting('currency_symbol') ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="tax_rate_id"><?= $translator->translate('i.tax_rate'); ?></label>
                            <select name="tax_rate_id" id="tax_rate_id" class="form-control">
                                <?php foreach ($tax_rates as $tax_rate) { ?>
                                    <option value="<?= $tax_rate->getTax_rate_id(); ?>"
                                        <?= $s->check_select($body['tax_rate_id'] ?? '', $tax_rate->getTax_rate_id()); ?>>
                                        <?= $tax_rate->getTax_rate_name() . ' (' . (float)$numberhelper->format_amount($tax_rate->getTax_rate_percent()) . '%)'; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <div class="mb-3 form-group has-feedback">        
                            <?php
                                $fdate = $datehelper->get_or_set_with_style($body['finish_date'] ?? new \DateTimeImmutable('now'));
                            ?>
                            <label form-label for="finish_date"><?= $translator->translate('i.task_finish_date') .' ('.$datehelper->display().')'; ?></label>
                            <div class="input-group">
                                <input type="text" name="finish_date" id="finish_date" placeholder="<?= ' ('.$datehelper->display().')';?>"
                                       class="form-control input-sm datepicker" readonly
                                       value="<?php if ($fdate <> null) {echo Html::encode($fdate instanceof \DateTime ? $fdate->format($datehelper->style()) : $fdate);} ?>" role="presentation" autocomplete="off">
                                <span class="input-group-text">
                                <i class="fa fa-calendar fa-fw"></i>
                            </span>
                            </div>        
                        </div>
                        
                        <div class="form-group">
                            <label for="status"><?= $translator->translate('i.status'); ?></label>
                            <select name="status" id="status" 
                            	class="form-control">
                                <?php foreach ($statuses as $key => $status) {
                                    $body['status'] = $body['status'] ?? ''; 
                                    if ($body['status'] !== 4 && $key === 4) {
                                        continue;
                                    } ?>
                                    <option value="<?= $key; ?>" <?= $s->check_select((string)$key, $body['status'] ?? ''); ?>>
                                        <?= $status['label']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?= $translator->translate('i.extra_information'); ?>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="project_id"><?= $translator->translate('i.project'); ?>: </label>
                            <select name="project_id" id="project_id" class="form-control">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php foreach ($projects as $project) { ?>
                                    <option value="<?= $project->getId(); ?>"
                                        <?= $s->check_select($body['project_id'], $project->getId()); ?>>
                                        <?= Html::encode($project->getName()); ?>
                                    </option>
                                <?php } ?>
                            </select>                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php 
      if ($body['id'] && $body['status'] === 4) {
        $js509 = "$(document).ready(function () {
              $('#task-form').find(':input').prop('disabled', 'disabled');
              $('#btn-submit').hide();
              $('#btn-cancel').prop('disabled', false);
        });";
        echo Html::script($js509)->type('module');
      }
    ?>
</form>
