<?php

declare(strict_types=1);

use App\Invoice\Entity\Inv;
use App\Widget\Button;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\Base\DataContext;
use Yiisoft\Yii\DataView\Column\CheckboxColumn;
use Yiisoft\Yii\DataView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @see config/common/params.php 'yiisoft/view => ['gridComponents' => Reference::to(GridComponents::class)]',
 */

/**
 * @var App\Invoice\DeliveryLocation\DeliveryLocationRepository $dlR
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\InvSentLog\InvSentLogRepository $islR
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $invs
 * @var Yiisoft\Data\Paginator\OffsetPaginator $sortedAndPagedPaginator
 * @var Yiisoft\Data\Reader\Sort $sort
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var array $order
 * @var bool $visible
 * @var bool $visibleToggleInvSentLogColumn
 * @var int $clientCount
 * @var int $decimalPlaces
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $label
 * @var string $modal_add_inv
 * @var string $modal_copy_inv_multiple
 * @var string $modal_create_recurring_multiple
 * @var string $sortString
 * @var string $status
 * @psalm-var positive-int $page
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientsDropdownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientGroupDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataYearMonthDropDownFilter
 */

?>
<?= $alert; ?>
<?php
echo Breadcrumbs::widget()
 ->links(
     BreadcrumbLink::to(
         label: $translator->translate('i.default_invoice_group'),
         url: $urlGenerator->generate(
             'setting/tab_index',
             [],
             ['active' => 'invoices'],
             'settings[default_invoice_group]'),
         active: true,
         attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $defaultInvoiceGroup ?? $translator->translate('i.not_set')
            ],
         encodeLabel: false
     ),
     BreadcrumbLink::to(
         label: $translator->translate('i.default_terms'),
         url: $urlGenerator->generate(
             'setting/tab_index',
             [],
             ['active' => 'invoices'],
             'settings[default_invoice_terms]'),
         active: false,
         attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $s->getSetting('default_invoice_terms') ?: $translator->translate('i.not_set')
            ],
         encodeLabel: false
     ),
     BreadcrumbLink::to(
         label:  $translator->translate('i.default_payment_method'),
         url: $urlGenerator->generate(
             'setting/tab_index',
             [],
             ['active' => 'invoices'],
             'settings[invoice_default_payment_method]'),
         active: false,
         attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $defaultInvoicePaymentMethod ?? $translator->translate('i.not_set')
            ],
         encodeLabel: false
     ),
     BreadcrumbLink::to(
         label: $translator->translate('i.invoices_due_after'),
         url: $urlGenerator->generate(
             'setting/tab_index',
             [],
             ['active' => 'invoices'],
            'settings[invoices_due_after]'),
         active: false,
         attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $s->getSetting('invoices_due_after') ?: $translator->translate('i.not_set')
            ],
         encodeLabel: false
     ),
     BreadcrumbLink::to(
         label: $translator->translate('i.generate_invoice_number_for_draft'),
         url: $urlGenerator->generate(
             'setting/tab_index',
             [],
             ['active' => 'invoices'],
              'settings[generate_invoice_number_for_draft]'),
         active: false,
         attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $s->getSetting('generate_invoice_number_for_draft') == '1' ? '✅' : '❌'
            ],
         encodeLabel: false
     ),   
     BreadcrumbLink::to(
         label: $translator->translate('i.recurring'),
         url: $urlGenerator->generate('invrecurring/index')
     ),
     BreadcrumbLink::to(
         label: $translator->translate('i.set_to_read_only').' '. $iR->getSpecificStatusArrayEmoji((int)$s->getSetting('read_only_toggle')),
         url:  $urlGenerator->generate(
             'setting/tab_index',
             [],
             ['active' => 'invoices'],
             'settings[read_only_toggle]')
     ),
 )
 ->listId(false)
 ->render();
?>
<?php
/**
 * Use with the checkbox column to copy invoices according to date.
 */

$copyInvoiceMultiple = A::tag()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'modal', 'title' => Html::encode($translator->translate('i.copy_invoice'))])
        ->addClass('btn btn-success')
        /**
         * Purpose: Trigger modal_copy_inv_multiple.php to pop up
         * @see id="modal-copy-inv-multiple" class="modal" on resources/views/invoice/inv/modal_copy_inv_multiple.php
         */
        ->href('#modal-copy-inv-multiple')
        ->content('☑️'.$translator->translate('i.copy_invoice'))
        ->id('btn-modal-copy-inv-multipe')
        ->render();

/**
 * Use with the checkbox column to mark invoices as sent. Note an email is not sent. The invoices appear on the client's guest index
 * NB: Only invoices marked as sent can appear on the client's side. i.e no 'draft' invoices can appear on the client guest index
 * @see \invoice\src\Invoice\Asset\rebuild\js\inv.js $(document).on('click', '#btn-mark-as-sent', function () {
 */
$markAsSent = A::tag()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => Html::encode($translator->translate('i.sent'))])
        ->addClass('btn btn-success')
        ->content('☑️'.$translator->translate('i.sent').$iR->getSpecificStatusArrayEmoji(2))
        ->id('btn-mark-as-sent')
        ->render();

/**
 * Use with the checkbox column to mark invoices as draft. The customer will no longer be able to view the invoice on their side.
 * @see \invoice\src\Invoice\Asset\rebuild\js\inv.js $(document).on('click', '#btn-mark-as-draft', function () {
 */
$disabledMarkSentAsDraft = A::tag()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => Html::encode($translator->translate('invoice.security.disable.read.only.info'))])
        ->addAttributes([
            'disabled' => 'disabled',
            'style' => 'text-decoration:none',
        ])
        ->addClass('btn btn-success')
        ->content('☑️'.$translator->translate('i.draft').$iR->getSpecificStatusArrayEmoji(1))
        ->id('btn-mark-sent-as-draft')
        ->render();

$enabledMarkSentAsDraft = A::tag()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => Html::encode($translator->translate('i.draft'))])
        ->addAttributes([
            'style' => 'text-decoration:none',
        ])
        ->addClass('btn btn-success')
        ->content('☑️'.$translator->translate('i.draft').$iR->getSpecificStatusArrayEmoji(1))
        ->id('btn-mark-sent-as-draft')
        ->render();

/**
 * Used with the checkbox column to use resources/views/invoice/inv/modal_create_recurring_multiple.php
 * @see https://emojipedia.org/recycling-symbol
 */
$markAsRecurringMultiple = A::tag()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'modal'])
        ->addClass('btn btn-info')
        /**
         * Purpose: Trigger modal_create_recurring_modal.php to pop up
         * @see id="create-recurring-multiple" class="modal" on resources/views/invoice/inv/modal_create_recurring_multiple.php
         */
        ->href('#create-recurring-multiple')
        ->content('☑️'.$translator->translate('i.recurring').'♻️')
        ->render();

$toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-primary me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'inv/index'))
        ->id('btn-reset')
        ->render();

$allVisible = A::tag()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.hide.or.unhide.columns')])
        ->addClass('btn btn-warning me-1 ajax-loader')
        ->content('↔️')
        ->href($urlGenerator->generate('setting/visible'))
        ->id('btn-all-visible')
        ->render();

$toggleColumnInvSentLog = A::tag()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.hide.or.unhide.columns')])
        ->addClass('btn btn-info me-1 ajax-loader')
        ->content('↔️')
        ->href($urlGenerator->generate('setting/toggleinvsentlogcolumn'))
        ->id('btn-all-visible');

$enabledAddInvoiceButton = A::tag()
        ->addAttributes([
            'class' => 'btn btn-info',
            'data-bs-toggle' => 'modal',
            'style' => 'text-decoration:none'
        ])
        ->content('➕')
        ->href('#modal-add-inv')
        ->id('btn-enabled-invoice-add-button')
        ->render();

$disabledAddInvoiceButton = A::tag()
        ->addAttributes([
            'class' => 'btn btn-info',
            'data-bs-toggle' => 'tooltip',
            'title' => $translator->translate('i.add_client'),
            'disabled' => 'disabled',
            'style' => 'text-decoration:none',
        ])
        ->content('➕')
        ->href('#modal-add-inv')
        ->id('btn-disabled-invoice-add-button')
        ->render();

$toolbar = Div::tag();
?>
<div>
    <br>
    <div class="submenu-row">
        <!--  Route::get('/inv[/page/{page:\d+}[/status/{status:\d+}]]') -->
        <div class="btn-group index-options">
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 0]); ?>"
               class="btn btn-<?= $status == 0 ? $iR->getSpecificStatusArrayClass(0) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(0).' '.$translator->translate('i.all'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 1]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 1 ? $iR->getSpecificStatusArrayClass(1) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(1).' '.$translator->translate('i.draft'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 2]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 2 ? $iR->getSpecificStatusArrayClass(2) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(2).' '.$translator->translate('i.sent'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 3]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 3 ? $iR->getSpecificStatusArrayClass(3) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(3).' '.$translator->translate('i.viewed'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 4]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 4 ? $iR->getSpecificStatusArrayClass(4) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(4).' '.$translator->translate('i.paid'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 5]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 5 ? $iR->getSpecificStatusArrayClass(5) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(5).' '.$translator->translate('i.overdue'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 6]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 6 ? $iR->getSpecificStatusArrayClass(6) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(6).' '.$translator->translate('i.unpaid'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 7]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 7 ? $iR->getSpecificStatusArrayClass(7) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(7).' '.$translator->translate('i.reminder'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 8]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 8 ? $iR->getSpecificStatusArrayClass(8) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(8).' '.$translator->translate('i.letter'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 9]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 9 ? $iR->getSpecificStatusArrayClass(9) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(9).' '.$translator->translate('i.claim'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 10]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 10 ? $iR->getSpecificStatusArrayClass(10) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(10).' '.$translator->translate('i.judgement'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 11]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 11 ? $iR->getSpecificStatusArrayClass(11) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(11).' '.$translator->translate('i.enforcement'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 12]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 12 ? $iR->getSpecificStatusArrayClass(12) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(12).' '.$translator->translate('i.credit_invoice_for_invoice'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 13]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 13 ? $iR->getSpecificStatusArrayClass(13) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(13).' '.$translator->translate('i.loss'); ?>
            </a>
        </div>
    </div>
    <br>
</div>

<?php
    /**
     * @var ColumnInterface[] $columns
     */
    $columns = [
        new CheckboxColumn(
            /**
             * @see header checkbox: name: 'checkbox-selection-all'
             */
            content: static function (Checkbox $input, DataContext $context) use ($translator): string {
                $inv = $context->data;
                if (($inv instanceof Inv) && (null !== ($id = $inv->getId()))) {
                    return Input::tag()
                           ->type('checkbox')
                           ->addAttributes([
                               'id' => $id,
                               'name' => 'checkbox[]',
                               'data-bs-toggle' => 'tooltip',
                               'title' => $inv->getInvAmount()->getTotal() == 0 ?
                                   $translator->translate('invoice.invoice.index.checkbox.add.some.items.to.enable') : ''])
                           ->value($id)
                           ->disabled($inv->getInvAmount()->getTotal() > 0 ? false : true)
                           ->render();
                }
                return '';
            },
            multiple: true
        ),
        new ActionColumn(
            buttons: [ 
                new ActionButton(
                    // is_read_only false, disable_read_only 0, status draft1 => ✎, not disabled
                    // is_read_only false, disable_read_only 1, status draft1 => ❗✎, not disabled
                    // is_read_only true, disable_read_only 0, status  sent2 => 🚫, disabled
                    // is_read_only true, disable_read_only 1, status sent2 => ❗, not disabled     
                    content: static function (Inv $inv) use ($s): string {
                        $iRO = $inv->getIs_read_only();
                        $dRO = $s->getSetting('disable_read_only');
                        $status = $inv->getStatus_id();
                        $icon = '';
                        $iconMap = [
                            /** editable draft */
                            'false' => [
                                /** protection is on */
                                '0' => [
                                    /** draft can be editable */
                                    '1' => '✎',
                                ],
                                /** protection is off */
                                '1' => [
                                    /** warning: editing a draft with protection off */
                                    '1' => '❗✎',
                                ]
                            ],
                            /** non editable invoice */
                            'true' => [
                                /** protection is on */
                                '0' => [
                                    /** an invoice marked as sent cannot be edited */
                                    '2' => '🚫'
                                ],
                                /** protection is off */
                                '1' => [
                                    /** warning: you are editing an invoice whilst protection is off */
                                    '2' => '❗'
                                ]
                            ]
                        ];
                        $iROString = $iRO ? 'true' : 'false';
                        /**
                         * @var array $iconMap[$iROString]
                         * @var array $iconMap[$iROString][$dRO]
                         * @var string $iconMap[$iROString][$dRO][$status]
                         */
                        $icon = $iconMap[$iROString][$dRO][$status] ?? '';
                        return !empty($icon) ? $icon : '';
                    },
                    url: static function (Inv $inv) use ($s, $urlGenerator): string {
                        $iRO = $inv->getIs_read_only();
                        $dRO = $s->getSetting('disable_read_only');
                        $status = $inv->getStatus_id();
                        $url = '';
                        $urlMap = [
                            /** editable draft **/
                            'false' => [
                                /** protection is on */
                                '0' => [
                                    '1' => $urlGenerator->generate('inv/edit', 
                                            ['id' => $inv->getId()]),
                                ],
                                /** protection is off */
                                '1' => [
                                    /** Allow editing of draft, even though protection is off */
                                    '1' => $urlGenerator->generate('inv/edit', 
                                            ['id' => $inv->getId()]),
                                ]
                            ],
                            /** not editable invoice */
                            'true' => [
                                /** protection is on */
                                '0' => [
                                    /** Invoice cannot be edited whilst protection is on */
                                    '2' => ''
                                ],
                                /** protection is off */
                                '1' => [
                                    /** Allow the editing of invoice whilst protection is off */
                                    '2' => $urlGenerator->generate('inv/edit', 
                                            ['id' => $inv->getId()]),
                                ]
                            ]
                        ];

                        $iROString = $iRO ? 'true' : 'false';
                        /**
                         * @var array $urlMap[$iROString]
                         * @var array $urlMap[$iROString][$dRO]
                         * @var string $urlMap[$iROString][$dRO][$status]
                         */
                        $url = $urlMap[$iROString][$dRO][$status] ?? '';
                        return $url;
                    },
                    attributes: static function (Inv $inv) use ($s, $translator): array {
                        $iRO = $inv->getIs_read_only();
                        $dRO = $s->getSetting('disable_read_only');
                        $status = $inv->getStatus_id();
                        $attributesMap = [
                            /** editable draft **/
                            'false' => [
                                /** protection is on */
                                '0' => [
                                    /** draft invoices can be edited */
                                    '1' => [
                                        'data-bs-toggle' => 'tooltip', 
                                        'title' => $translator->translate('i.edit')],
                                ],
                                /** protection is off */
                                '1' => [
                                    '1' => [
                                        'data-bs-toggle' => 'tooltip', 
                                        'title' => $translator->translate('invoice.security.disable.read.only.true.draft.check.and.mark'), 
                                        ],
                                ]
                            ],
                            /** not editable invoice */
                            'true' => [
                                /** protection is on */
                                '0' => [
                                    /** Invoice cannot be edited whilst protection is on */
                                    '2' => [
                                        'data-bs-toggle' => 'tooltip', 
                                        'title' => $translator->translate('i.sent'), 
                                        'disabled' => 'disabled',
                                        'aria-disabled' => 'true',  
                                        'style' => 'pointer-events:none'], 
                                ],
                                /** protection is off */
                                '1' => [
                                    /** Allow the editing of invoice whilst protection is off */
                                    '2' => [
                                        'data-bs-toggle' => 'tooltip', 
                                        'title' => $translator->translate('invoice.security.disable.read.only.true.sent.check.and.mark')]
                                ]
                            ]
                        ];
                        $iROString = $iRO ? 'true' : 'false';
                        /**
                         * @var array $attributesMap[$iROString]
                         * @var array $attributesMap[$iROString][$dRO]
                         * @var array $attributesMap[$iROString][$dRO][$status]
                         */
                        $attributes = $attributesMap[$iROString][$dRO][$status] ?? [];
                        return $attributes;
                    },      
                ),
                new ActionButton(
                    url: static function (Inv $inv) use ($translator, $urlGenerator): string {
                        return $urlGenerator->generate('inv/pdf_dashboard_exclude_cf', ['id' => $inv->getId()]);
                    },       
                    attributes: [
                       'data-bs-toggle' => 'tooltip',
                       'target' => '_blank',
                       'title' => $translator->translate('i.download_pdf'),
                       'class' => 'bi bi-file-pdf'
                   ]
                ),
                new ActionButton(
                    url: static function (Inv $inv) use ($translator, $urlGenerator): string {
                        return $urlGenerator->generate('inv/pdf_dashboard_include_cf', ['id' => $inv->getId()]);
                    },
                    attributes: [
                       'data-bs-toggle' => 'tooltip',
                       'target' => '_blank',
                       'title' => $translator->translate('i.download_pdf').'➡️'.$translator->translate('invoice.custom.field'),
                       'class' => 'bi bi-file-pdf-fill'
                   ],
                ),
                new ActionButton(
                    content: '📨',
                    url: static function (Inv $inv) use ($urlGenerator): string {
                        // draft invoices cannot be emailed
                        if ($inv->getStatus_id() !== 1) {
                            return $urlGenerator->generate('inv/email_stage_0', ['id' => $inv->getId()]);
                        }
                        return '';
                    },
                    attributes: [
                       'data-bs-toggle' => 'tooltip',
                       'title' => $translator->translate('invoice.email.warning.draft')
                   ],
                ),
            ]
        ),
        new DataColumn(
            'id',
            header: 'id',
            content: static fn (Inv $model) => (string)$model->getId(),
            withSorting: true
        ),
        new DataColumn(
            property: 'filterInvNumber',
            field: 'number',
            header: $translator->translate('invoice.invoice.number'),
            content: static function (Inv $model) use ($urlGenerator): A {
                return  A::tag()
                        ->addAttributes(['style' => 'text-decoration:none'])
                        ->content(($model->getNumber() ?? '#'). ' 🔍')
                        ->href($urlGenerator->generate('inv/view', ['id' => $model->getId()]));
            },
            filter: $optionsDataInvNumberDropDownFilter,
            withSorting: false
        ),
        new DataColumn( 
            header: '💳',    
            field: 'creditinvoice_parent_id',
            content: static function (Inv $model) use ($urlGenerator, $iR): A {
                $visible = $iR->repoInvUnLoadedquery($model->getCreditinvoice_parent_id());
                $url = ($model->getNumber() ?? '#'). '💳';
                return  A::tag()
                        ->addAttributes(['style' => 'text-decoration:none'])
                        ->content($visible ? $url : '')
                        ->href($urlGenerator->generate('inv/view', ['id' => $model->getCreditinvoice_parent_id()]));
            },
            withSorting: false
        ),
        new DataColumn(
            'invsentlogs',
            header: $translator->translate('invoice.email.logs.with.filter'),
            content: static function (Inv $model) use ($islR, $toggleColumnInvSentLog, $urlGenerator, $translator): string|A {
                $modelId = $model->getId();
                if (null !== $modelId) {
                    $count = $islR->repoInvSentLogEmailedCountForEachInvoice($modelId);
                    if ($count > 0) {
                        return $toggleColumnInvSentLog;
                    } else {
                        return '❌';
                    }
                }
                return '';
            }
        ),
        new DataColumn(
            'invsentlogs',
            header: $translator->translate('invoice.email.logs.with.filter'),
            content: static function (Inv $model) use ($islR, $toggleColumnInvSentLog, $urlGenerator, $translator): string|A {
                $modelId = $model->getId();
                if (null !== $modelId) {
                    $count = $islR->repoInvSentLogEmailedCountForEachInvoice($modelId);
                    if ($count > 0) {
                        $linkToInvSentLogWithFilterInv = A::tag()
                        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.email.logs')])
                        ->addClass('btn btn-success me-1')
                        ->content((string)$count)
                        ->href($urlGenerator->generate('invsentlog/index', [], ['filterInvNumber' => $model->getNumber()]))
                        ->id('btn-all-visible');
                        return $linkToInvSentLogWithFilterInv;
                    }
                }
                return '';
            }
        ),
        new DataColumn(
            content: static function (Inv $model) use ($islR, $urlGenerator, $gridComponents): string {
                $modelId = $model->getId();
                if (null !== $modelId) {
                    $invSentLogs = $islR->repoInvSentLogForEachInvoice($modelId);
                    /**
                     * @see Initialize an ArrayCollection
                     */
                    $model->setInvSentLogs();
                    /**
                     * @var App\Invoice\Entity\InvSentLog $invSentLog
                     */
                    foreach ($invSentLogs as $invSentLog) {
                        $model->addInvSentLog($invSentLog);
                    }
                    return $gridComponents->gridMiniTableOfInvSentLogsForInv(
                        $model,
                        $min_invsentlogs_per_row = 4,
                        $urlGenerator
                    );
                }
                return '';
            },   
            visible: $visibleToggleInvSentLogColumn,
            encodeContent: false        
        ),
        new DataColumn(
            'status_id',
            header: $translator->translate('i.status'),
            content: static function (Inv $model) use ($s, $iR, $irR, $translator): Yiisoft\Html\Tag\CustomTag {
                $label = $iR->getSpecificStatusArrayLabel((string)$model->getStatus_id());
                if (($model->getIs_read_only()) && $s->getSetting('disable_read_only') == '0') {
                    $label .=  ' 🚫';
                }
                if ($irR->repoCount((string) $model->getId()) > 0) {
                    $label .= $translator->translate('i.recurring'). ' 🔄';
                }
                return Html::tag('span', $iR->getSpecificStatusArrayEmoji((int)$model->getStatus_id()). $label, ['class' => 'label label-' . $iR->getSpecificStatusArrayClass((int)$model->getStatus_id())]);
            },
            withSorting: true
        ),
        /**
         * @see https://github.com/rossaddison/yii-dataview/commit/9e908d87cddd0661b440cb989429e1652e00a9fe
         */
        new DataColumn(
            property: 'filterClient',
            field: 'client_id',
            header: $translator->translate('i.client'),
            content: static fn (Inv $model): string => Html::encode($model->getClient()?->getClient_full_name()),
            filter: $optionsDataClientsDropdownFilter,
            withSorting: false,
            encodeContent: false    
        ),
        new DataColumn(
            property: 'filterClientGroup',
            field: 'client_id',
            header: $translator->translate('invoice.client.group'),
            content: static fn (Inv $model): string => $model->getClient()?->getClient_group() ?? '',
            filter: $optionsDataClientGroupDropDownFilter,
            withSorting: false
        ),
        new DataColumn(
            property: 'filterDateCreatedYearMonth',
            field: 'date_created',
            header: $translator->translate('invoice.datetime.immutable.date.created.mySql.format.year.month.filter'),
            content: static fn (Inv $model): string => ($model->getDate_created())->format('Y-m-d'),
            filter: $optionsDataYearMonthDropDownFilter,
            withSorting: false
        ),
        new DataColumn(
            'time_created',
            header: $translator->translate('invoice.datetime.immutable.time.created'),
            // Show only the time of the DateTimeImmutable
            content: static fn (Inv $model): string => ($model->getTime_created())->format('H:i:s')
        ),
        new DataColumn(
            'date_modified',
            header: $translator->translate('invoice.datetime.immutable.date.modified'),
            content: static function (Inv $model) use ($dateHelper): Label {
                if ($model->getDate_modified() <> $model->getDate_created()) {
                    return Label::tag()
                           ->attributes(['class' => 'label label-danger'])
                           ->content(Html::encode($model->getDate_modified()->format('Y-m-d')));
                } else {
                    return Label::tag()
                           ->attributes(['class' => 'label label-success'])
                           ->content(Html::encode($model->getDate_modified()->format('Y-m-d')));
                }
            }
        ),
        new DataColumn(
            'date_due',
            header: $translator->translate('i.due_date'),
            content: static function (Inv $model) use ($dateHelper): Label {
                $now = new \DateTimeImmutable('now');
                return Label::tag()
                        ->attributes(['class' => $model->getDate_due() > $now ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode(!is_string($dateDue = $model->getDate_due()) ? $dateDue->format('Y-m-d') : ''));
            },
            withSorting: true
        ),
        new DataColumn(
            property: 'filterInvAmountTotal',
            field: 'id',
            header: $translator->translate('i.total') . '➡️'. $s->getSetting('currency_symbol'),
            content: static function (Inv $model) use ($decimalPlaces): Label {
                $invAmountTotal = $model->getInvAmount()->getTotal();
                return
                    Label::tag()
                        ->attributes(['class' => $invAmountTotal > 0.00 ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode(null !== $invAmountTotal
                                ? number_format($invAmountTotal, $decimalPlaces)
                                : number_format(0, $decimalPlaces)));
            },
            filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                    ->addAttributes(['style' => 'max-width: 50px']),
            withSorting: false
        ),
        new DataColumn(
            'id',
            header: $translator->translate('i.paid') . '➡️'. $s->getSetting('currency_symbol'),
            content: static function (Inv $model) use ($decimalPlaces): Label {
                $invAmountPaid = $model->getInvAmount()->getPaid();
                return Label::tag()
                        ->attributes(['class' => $model->getInvAmount()->getPaid() < $model->getInvAmount()->getTotal() ? 'label label-danger' : 'label label-success'])
                        ->content(Html::encode(null !== $invAmountPaid
                                ? number_format($invAmountPaid > 0.00 ? $invAmountPaid : 0.00, $decimalPlaces)
                                : number_format(0, $decimalPlaces)));
            },
            withSorting: false
        ),
         new DataColumn(
             'id',
             header: $translator->translate('i.balance')  . '➡️'. $s->getSetting('currency_symbol'),
             content: static function (Inv $model) use ($decimalPlaces): Label {
                 $invAmountBalance = $model->getInvAmount()->getBalance();
                 return  Label::tag()
                         ->attributes(['class' => $invAmountBalance > 0.00 ? 'label label-success' : 'label label-warning'])
                         ->content(Html::encode(null !== $invAmountBalance
                                 ? number_format($invAmountBalance > 0.00 ? $invAmountBalance : 0.00, $decimalPlaces)
                                 : number_format(0, $decimalPlaces)));
             },
             withSorting: false
         ),
        new DataColumn(
            header: '🚚',
            content: static function (Inv $model) use ($urlGenerator): A {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-plus fa-margin']), $urlGenerator->generate(
                    'del/add',
                    [
                    /**
                     *
                     * @see DeliveryLocation add function getRedirectResponse
                     * @see config/common/routes/routes.php Route::methods([Method::GET, Method::POST], '/del/add/{client_id}[/{origin}/{origin_id}/{action}]')
                     */
                    'client_id' => $model->getClient_id()
                ],
                    [
                    'origin' => 'inv',
                    'origin_id' => $model->getId(),
                    'action' => 'index'
                ]
                ));
            },
            visible: $visible,
            withSorting: false
        ),
        new DataColumn(
            'quote_id',
            header: $translator->translate('invoice.quote.number.status'),
            content: static function (Inv $model) use ($urlGenerator, $qR): string|A {
                $quote_id = $model->getQuote_id();
                $quote = $qR->repoQuoteUnloadedquery($quote_id);
                if (null !== $quote) {
                    $statusId = $quote->getStatus_id();
                    if (null !== $statusId) {
                        return Html::a(
                            ($quote->getNumber() ?? '#') . ' ' . $qR->getSpecificStatusArrayLabel((string)$statusId),
                            $urlGenerator->generate('quote/view', ['id' => $quote_id]),
                            [
                                'style' => 'text-decoration:none',
                                'class' => 'label ' . $qR->getSpecificStatusArrayClass((string)$statusId)
                            ]
                        );
                    }
                }
                return '';
            },
            visible: $visible,
            withSorting: false
        ),
        new DataColumn(
            'so_id',
            header: $translator->translate('invoice.salesorder.number.status'),
            content: static function (Inv $model) use ($urlGenerator, $soR): string {
                $so_id = $model->getSo_id();
                $so = $soR->repoSalesOrderUnloadedquery($so_id);
                if (null !== $so) {
                    $statusId = $so->getStatus_id();
                    if (null !== $statusId) {
                        return (string) Html::a(($so->getNumber() ?? '#') . ' ' . $soR->getSpecificStatusArrayLabel((string)$statusId), $urlGenerator->generate('salesorder/view', ['id' => $so_id]), ['style' => 'text-decoration:none', 'class' => 'label ' . $soR->getSpecificStatusArrayClass($statusId)]);
                    }
                } else {
                    return '';
                }
                return '';
            },
            visible: $visible,
            withSorting: false
        ),
        new DataColumn(
            'delivery_location_id',
            header: $translator->translate('invoice.delivery.location.global.location.number'),
            content: static function (Inv $model) use ($dlR): string {
                $delivery_location_id = $model->getDelivery_location_id();
                $delivery_location = (($dlR->repoCount($delivery_location_id) > 0) ? $dlR->repoDeliveryLocationquery($delivery_location_id) : null);
                return null !== $delivery_location ? Html::encode($delivery_location->getGlobal_location_number()) : '';
            },
            visible: $visible,
            withSorting: false
        ),
        new DataColumn(
            header: $translator->translate('i.delete'),
            content: static function (Inv $model) use ($s, $translator, $urlGenerator): A|Label {
                return $model->getIs_read_only() === false && $s->getSetting('disable_read_only') === (string) 0 && $model->getSo_id() === '0' && $model->getQuote_id() === '0' ? 
                        A::tag()->content(
                            Html::tag(
                                'button',
                                Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                                [
                                    'type' => 'submit',
                                    'class' => 'dropdown-button',
                                    'onclick' => "return confirm(" . "'" . $translator->translate('i.delete_record_warning') . "');"
                                ]
                            ),
                            $urlGenerator->generate('inv/delete', ['id' => $model->getId()])) : Label::tag();
            },
            visible: $visible,
            withSorting: false
        )
    ];
?>
<?php
$toolbarString =
    Form::tag()->post($urlGenerator->generate('inv/index'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-end m-3')->content($allVisible)->encode(false)->render() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'client_id', 'warning', $translator->translate('i.client'), false))->encode(false)->render().
    // use the checkboxcolumn to copy multiple invoices accrding to a new date
    Div::tag()->addClass('float-end m-3')->content($copyInvoiceMultiple)->encode(false)->render() .
    // use the checkboxcolumn to mark invoices as sent
    Div::tag()->addClass('float-end m-3')->content($markAsSent)->encode(false)->render() .
    // use the checkboxcolumn to mark sent invoices as draft
    (
        $s->getSetting('disable_read_only') === (string) 0
        ? Div::tag()->addClass('float-end m-3')->content($disabledMarkSentAsDraft)->encode(false)->render()
        : Div::tag()->addClass('float-end m-3')->content($enabledMarkSentAsDraft)->encode(false)->render()
    ).
    // use the checkboxcolumn to mark invoices as recurring
    Div::tag()->addClass('float-end m-3')->content($markAsRecurringMultiple)->encode(false)->render() .
    (
        $clientCount == 0
        ? Div::tag()->addClass('float-end m-3')->content($disabledAddInvoiceButton)->encode(false)->render()
        : Div::tag()->addClass('float-end m-3')->content($enabledAddInvoiceButton)->encode(false)->render()
    ).
    Form::tag()->close();

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));

$sort = Sort::only(['id', 'status_id', 'number', 'date_created', 'date_due', 'client_id'])
                // (@see vendor\yiisoft\data\src\Reader\Sort
                // - => 'desc'  so -id => default descending on id
                ->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($invs))
        ->withPageSize($defaultPageSizeOffsetPaginator > 0 ? $defaultPageSizeOffsetPaginator : 1)
        ->withCurrentPage($page)
        ->withSort($sort)
        ->withToken(PageToken::next((string)$page));

$grid_summary = $s->grid_summary(
    $sortedAndPagedPaginator,
    $translator,
    $defaultPageSizeOffsetPaginator,
    $translator->translate('invoice.invoice.invoices'),
    $label
);

echo GridView::widget()
// unpack the contents within the array using the three dot splat operator
->bodyRowAttributes(['class' => 'align-left'])
->tableAttributes(['class' => 'table table-striped h-75', 'id' => 'table-invoice'])
->columns(...$columns)
->dataReader($sortedAndPagedPaginator)
->urlCreator($urlCreator)
// the up and down symbol will appear at first indicating that the column can be sorted
// Ir also appears in this state if another column has been sorted
->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
// the up arrow will appear if column values are ascending
->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
// the down arrow will appear if column values are descending
->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->emptyCell($translator->translate('i.not_set'))
->emptyCellAttributes(['style' => 'color:red'])
//->header($gridComponents->header(' ' . $translator->translate('i.invoice')))
->id('w3-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($sortedAndPagedPaginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'inv').' '.$grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('invoice.invoice.no.records'))
->toolbar($toolbarString);
?>

<?php echo $modal_add_inv; ?>
<?php echo $modal_create_recurring_multiple; ?>
<?php echo $modal_copy_inv_multiple; ?>