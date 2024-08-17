<?php

   declare(strict_types=1);
      
   use App\Widget\OffsetPagination;
   use Yiisoft\Html\Html;
   use Yiisoft\Html\Tag\A;
   
   /**
    * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
    * @var Yiisoft\Translator\TranslatorInterface $translator 
    * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
    * @var array $email_templates
    * @var string $alert
    * @var string $csrf 
    */ 
   echo $alert;
?>
<div id="headerbar">
    <h1 class="headerbar-title"><?= $translator->translate('i.email_templates'); ?></h1>
    <div class="headerbar-item pull-right">
        <a class="btn btn-sm btn-primary" href="<?php echo $urlGenerator->generate('emailtemplate/add_invoice'); ?>">
            <i class="fa fa-plus"></i> <?= $translator->translate('i.invoice'); ?>
        </a>
        <br>
        <br>
        <a class="btn btn-sm btn-secondary" href="<?php echo $urlGenerator->generate('emailtemplate/add_quote'); ?>">
            <i class="fa fa-plus"></i> <?= $translator->translate('i.quote'); ?>
        </a>
    </div>
    <div class="headerbar-item pull-right">
        <?php
            $pagination = OffsetPagination::widget()
            ->paginator($paginator)
            ->urlGenerator(fn (string $page) => $urlGenerator->generate('emailtemplate/index', ['page' => $page]));
        ?>
        <?php
            if ($pagination->isPaginationRequired()) {
                 echo $pagination;
            }
        ?>
    </div>
</div>
<div>
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th><?= $translator->translate('i.title'); ?></th>
            <th><?= $translator->translate('i.type'); ?></th>
            <th><?= Html::openTag('h5'); ?><?= $translator->translate('i.preview'); ?><?= Html::closeTag('h5'); ?></th>
            <th><?= $translator->translate('i.options'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
            /**
             * @var App\Invoice\Entity\EmailTemplate $email_template
             */
            foreach ($email_templates as $email_template) { ?>
            <tr>
                <td><?= Html::encode($email_template->getEmail_template_title()); ?></td>
                <td><?= ucfirst($email_template->getEmail_template_type() ?? 'invoice'); ?></td>
                <td><?= A::tag()
                        ->href($urlGenerator->generate('emailtemplate/preview',
                                ['email_template_id' => $email_template->getEmail_template_id()]
                               )
                            )
                        ->content('🖼️');    
                    ?>
                </td>
                <td>
                    <?php
                        /**
                         * @see https://getbootstrap.com/docs/5.3/components/dropdowns/
                         */
                    ?>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                             <i class="fa fa-cog"></i>               
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="
                                    <?= $urlGenerator->generate('emailtemplate/view',
                                            ['email_template_id' => $email_template->getEmail_template_id()]); ?>" style="text-decoration: none ">
                                            <i class="fa fa-eye fa-margin"></i><?= $translator->translate('i.view'); ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="
                                    <?= $urlGenerator->generate('emailtemplate/edit'.($email_template->getEmail_template_type() == 'Invoice' ? '_invoice' : '_quote'),
                                            ['email_template_id'=>$email_template->getEmail_template_id()]); ?>" style="text-decoration: none ">
                                            <i class="fa fa-edit fa-margin"></i><?= $translator->translate('i.edit'); ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="
                                    <?= $urlGenerator->generate('emailtemplate/delete',
                                            ['email_template_id' => $email_template->getEmail_template_id()]); ?>" style="text-decoration: none ">
                                            <i class="fa fa-trash fa-margin"></i><?= $translator->translate('i.delete'); ?>
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
