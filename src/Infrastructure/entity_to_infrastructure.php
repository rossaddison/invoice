<?php

declare(strict_types=1);

/**
 * Entity-to-Infrastructure mapping registry.
 *
 * Each key is the short entity name matching src/Invoice/Entity/{Name}.php.
 *
 * Implemented entries are arrays with five keys:
 *   'class'           => FQCN of the infrastructure persistence class
 *   'req_id'          => bool — true when the class uses the reqId() pattern:
 *                          • method is named reqId(), return type int
 *                            (non-nullable)
 *                          • throws \LogicException when $id is null
 *                          • companion isPersisted(): bool method is present
 *                          • setId(int $id): void is present
 *                        false means the class still uses the old getId().
 *   'var_annotations' => bool — true when every @var annotation in the
 *                        infrastructure class itself carries a
 *                        fully-qualified, correct-namespace type:
 *                          • no bare @var $variable (type stripped)
 *                          • no @var referencing App\Invoice\Entity\{X}
 *                            for any {X} already converted
 *                            (entity_removed => true)
 *                        Checked as part of the Psalm run. Must be reset
 *                        to false whenever any referenced entity is
 *                        converted, and re-verified (use statement + @var
 *                        updated) before setting true.
 *   'callers'         => string[] — every file outside the infrastructure
 *                        class that references App\Invoice\Entity\{Name}.
 *                        Populated from Stage 2 grep output. Each entry
 *                        is a path relative to the project root.
 *                        Empty array [] once all callers are updated and
 *                        callers_updated is set to true.
 *   'callers_updated' => bool — true when every file listed in 'callers'
 *                        has been manually updated:
 *                          • use App\Invoice\Entity\{Name} replaced with
 *                            the infrastructure FQCN
 *                          • @var App\Invoice\Entity\{Name} annotations
 *                            updated accordingly
 *                          • no bare @var $variable left behind from a
 *                            failed substitution
 *                        This step cannot be automated reliably and must
 *                        be done by hand, verified by a full project-wide
 *                        Psalm run before setting true.
 *   'view_get_id_updated' => bool — true when every view file that calls
 *                        getId() on this infrastructure class has been
 *                        updated to call reqId() instead. getId() no
 *                        longer exists on infrastructure classes; any
 *                        remaining call is a silent Psalm error. Check:
 *                          grep -rn "getId()" resources/views/
 *                        and confirm each hit is against an entity that
 *                        has not yet been converted (safe to leave) or
 *                        against an infrastructure class (must change).
 *                        Also update the @var annotation above the
 *                        foreach loop if still pointing to Entity FQCN.
 *   'null_guards_removed' => bool — true when all null guards in caller
 *                        files that became redundant after the getId() →
 *                        reqId() conversion have been removed. reqId()
 *                        returns int (non-nullable), so any guard of the
 *                        form:
 *                          if (null !== $id) { ... }
 *                          if ($id !== null) { ... }
 *                          if (isset($id)) { ... }
 *                        that wraps a block using the id variable is now
 *                        a RedundantCondition in Psalm and the guard
 *                        should be removed, leaving only the inner body.
 *                        Applies in repositories, views, and forms.
 *                        Use convert_get_id.php --dry-run to surface
 *                        these before applying, then verify with Psalm.
 *   'group_use'       => bool — true when the infrastructure FQCN is
 *                        imported using PHP group use syntax in every
 *                        caller file where the namespace would otherwise
 *                        exceed 85 characters on a single use line.
 *                        Group use syntax:
 *                          use App\Infrastructure\Persistence\{Name}\{
 *                              {Name},
 *                          };
 *                        Preferred over a long single-line import or an
 *                        arbitrary alias. Not required when the import
 *                        fits within 85 characters on one line.
 *   'psalm'           => bool — true when:
 *                        vendor/bin/psalm
 *                        reports zero errors at errorLevel 1 with 100%
 *                        type inference across the full project. Must be
 *                        reset to false after any edit and re-verified
 *                        before setting back to true.
 *                        Requires var_annotations => true first.
 *   'entity_removed'  => bool — true when the corresponding
 *                        src/Invoice/Entity/{Name}.php has been deleted,
 *                        leaving no remnant of the old entity class.
 *                        false means the file still exists and the
 *                        migration is incomplete.
 *
 * Pending entries (infrastructure class not yet created) are null.
 *
 * ==========================================================================
 * CONVERSION PROCESS — staged approach
 * ==========================================================================
 *
 * Converting a heavily-referenced entity (e.g. Inv, Product, Quote) in one
 * pass produces hundreds of cascading Psalm errors. Always work in stages:
 *
 * STAGE 0 — choose the right entity next
 *
 *   PRIORITY CHECK — run this first:
 *   Grep inside the already-converted infrastructure classes for any
 *   remaining App\Invoice\Entity\ imports. These entities are blocking
 *   existing infrastructure classes from being fully clean and MUST be
 *   converted before all others:
 *
 *   grep -rn "App\\Invoice\\Entity\\" \
 *     src/Infrastructure/Persistence/
 *
 *   Each entity name that appears in the output is a priority target.
 *   Convert these before picking from the general ranked list below.
 *
 *   GENERAL RANKING — after all priority targets are cleared:
 *   Rank remaining entities by number of referencing files, lowest
 *   first. Never convert an entity whose own relations still point to
 *   unconverted entity classes:
 *
 *   grep -rl "App\\Invoice\\Entity\\" src/ \
 *     | xargs grep -h "App\\Invoice\\Entity\\" \
 *     | grep -oP "App\\\\Invoice\\\\Entity\\\\[A-Za-z]+" \
 *     | sort | uniq -c | sort -n
 *
 *   GREP COMMAND GLOSSARY
 *   grep -rl "pattern" src/
 *     -r  recurse into every subdirectory
 *     -l  list file names only (not matching lines)
 *     Produces: every file that contains the pattern anywhere.
 *
 *   | xargs grep -h "pattern"
 *     xargs  passes each filename from the pipe as an argument
 *     -h     suppress the filename prefix on each output line
 *     Produces: every matching line across all those files,
 *               with no filename attached.
 *
 *   | grep -oP "App\\\\Invoice\\\\Entity\\\\[A-Za-z]+"
 *     -o  print only the matched portion, one match per line
 *     -P  use Perl-compatible regex (enables \\ escape sequences)
 *     [A-Za-z]+  one or more letters — captures the class name
 *     Produces: one fully-qualified entity class name per line,
 *               e.g. App\Invoice\Entity\TaxRate
 *
 *   | sort | uniq -c | sort -n
 *     sort     alphabetically groups identical lines together
 *     uniq -c  collapses duplicates, prefixing each with a count
 *     sort -n  re-sorts numerically so lowest count appears first
 *     Produces: a ranked list — least-referenced entity at top,
 *               most-referenced at bottom.
 *
 * CRITICAL — @var ANNOTATIONS ABOVE foreach LOOPS IN VIEWS
 *   *** COMPLETE THIS BEFORE STAGE 1 (before creating the infrastructure
 *   class). Doing it first means every view already carries the correct
 *   FQCN by the time Psalm first runs, eliminating an entire category of
 *   cascading errors from the outset. ***
 *
 * STAGE 1 — create the infrastructure class
 *   Create src/Infrastructure/Persistence/{Name}/{Name}.php.
 *   Add entry here with all flags false.
 *
 * STAGE 2 — audit callers before touching anything
 *   Two greps are required — the first finds fully-qualified references,
 *   the second finds files that import by short name:
 *
 *   grep -rl "App\\Invoice\\Entity\\{Name}" src/ resources/
 *   grep -rl "{Name}" src/ resources/ --include="*.php"
 *
 *   Combine both result lists (deduplicated) into the 'callers' array.
 *   Using only the first grep will miss files that reference the class
 *   by short name after a use statement, producing Psalm errors after
 *   the entity file is deleted.
 *
 * STAGE 3 — apply reqId() pattern
 *   Update the infrastructure class: reqId(), isPersisted(), setId().
 *   Set 'req_id' => true.
 *
 * STAGE 4 — update @var annotations in the infrastructure class
 *   Ensure no bare @var $variable and no stale entity namespace
 *   for any already-converted class. Set 'var_annotations' => true.
 *
 * (continued from CRITICAL above)
 *   Without a correctly typed @var annotation immediately above a
 *   foreach loop in a view file, Psalm cannot infer the type of the
 *   loop variable. This produces cascading errors on every method call
 *   inside the loop — typically 3–5 errors per loop. These errors
 *   look unrelated (MixedMethodCall, MixedArgument, etc.) and are
 *   difficult to trace back to the missing annotation.
 *
 *   Every foreach loop in a view that iterates over an entity result
 *   MUST have the annotation in place BEFORE the entity is deleted:
 *
 *     /**
 *      * @var App\Infrastructure\Persistence\{Name}\{Name} $var
 *      * /
 *     foreach ($repository->repoQuery() as $var) {
 *
 *   After conversion, update the namespace from
 *   App\Invoice\Entity\{Name} to the infrastructure FQCN.
 *   This is the single most effective preventative measure against
 *   Psalm error cascades during the conversion process.
 *
 * STAGE 5 — update all external callers manually
 *   For each file from the Stage 2 list:
 *     • Replace use App\Invoice\Entity\{Name} with infrastructure FQCN
 *     • Update every @var App\Invoice\Entity\{Name} annotation
 *     • Verify no bare @var $variable was left behind
 *   This step cannot be automated reliably. Do it by hand.
 *   Set 'callers_updated' => true.
 *
 * STAGE 6 — run full project-wide Psalm
 *   vendor/bin/psalm
 *   Must report zero errors at errorLevel 1, 100% type inference.
 *   Set 'psalm' => true only after a clean run.
 *
 * STAGE 7 — delete the old entity file
 *   Delete src/Invoice/Entity/{Name}.php.
 *   Set 'entity_removed' => true.
 *
 * STAGE 8 — run full project-wide Psalm
 *   vendor/bin/psalm
 *   Any remaining errors indicate missed callers from Stage 5.
 *   Resolve before considering the conversion complete.
 *
 * ==========================================================================
 *
 * Update this file when:
 *   • A new class is added under
 *     src/Infrastructure/Persistence/{Name}/{Name}.php
 *     — add an entry with all flags false, then flip each to true
 *     as that step is completed.
 *   • The reqId() refactor is completed    — 'req_id'             => true.
 *   • @var annotations verified            — 'var_annotations'    => true.
 *   • All external callers updated         — 'callers_updated'    => true.
 *   • Null guards removed from callers     — 'null_guards_removed' => true.
 *   • View getId() calls replaced          — 'view_get_id_updated' => true.
 *   • Group use applied where needed       — 'group_use'          => true.
 *   • Psalm passes cleanly after any edit  — 'psalm'              => true.
 *   • src/Invoice/Entity/{Name}.php deleted — 'entity_removed'    => true.
 *   • Any referenced entity is converted   — reset 'var_annotations',
 *     'callers_updated', and 'psalm' to false until use statements,
 *     @var annotations, and all callers are updated and re-verified.
 */

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use App\Infrastructure\Persistence\Task\Task;
use App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount;
use App\Infrastructure\Persistence\ProductClient\ProductClient;
use App\Infrastructure\Persistence\SalesOrderAllowanceCharge\SalesOrderAllowanceCharge;
use App\Infrastructure\Persistence\CategoryPrimary\CategoryPrimary;
use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\Client\Client;
use App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\SalesOrderItemAllowanceCharge\{
    SalesOrderItemAllowanceCharge,
};
use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem;
use App\Infrastructure\Persistence\TaxRate\TaxRate;
use App\Infrastructure\Persistence\UserCustom\UserCustom;

return [
    // -------------------------------------------------------------------------
    // Implemented — all checks complete
    // -------------------------------------------------------------------------
    'AllowanceCharge'   => [
        'class'               => AllowanceCharge::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'group_use'           => true,
        'view_get_id_updated' => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'CategoryPrimary'   => [
        'class'               => CategoryPrimary::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'CategorySecondary' => [
        'class'               => CategorySecondary::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [
            'src/Invoice/CategorySecondary/CategorySecondaryRepository.php',
            'src/Invoice/CategorySecondary/CategorySecondaryForm.php',
            'resources/views/invoice/categorysecondary/index.php',
        ],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'Client'            => [
        'class'               => Client::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'DeliveryLocation'  => [
        'class'               => DeliveryLocation::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'TaxRate'           => [
        'class'               => TaxRate::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'SalesOrderItemAllowanceCharge' => [
        'class'               => SalesOrderItemAllowanceCharge::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [
            'src/Invoice/SalesOrderItemAllowanceCharge/SalesOrderItemAllowanceChargeRepository.php',
            'src/Invoice/SalesOrderItemAllowanceCharge/SalesOrderItemAllowanceChargeForm.php',
            'src/Invoice/SalesOrderItem/SalesOrderItemService.php',
            'src/Invoice/Helpers/PdfHelper.php',
            'src/Invoice/Quote/Trait/QuoteToSo.php',
            'src/Invoice/SalesOrder/SalesOrderController.php',
            'src/Invoice/SalesOrderAmount/SalesOrderAmountService.php',
            'resources/views/invoice/salesorder/partial_item_table.php',
            'resources/views/invoice/template/salesorder/pdf/salesorder.php',
            'resources/views/invoice/template/salesorder/public/SalesOrder_Web.php',
        ],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],

    'UserCustom'        => [
        'class'               => UserCustom::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [
            'src/Invoice/UserCustom/UserCustomRepository.php',
        ],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],

    // -------------------------------------------------------------------------
    // Implemented — reqId() refactor still needed (currently uses getId())
    // -------------------------------------------------------------------------
    'InvAllowanceCharge' => [
        'class'               => InvAllowanceCharge::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],

    // -------------------------------------------------------------------------
    // Pending — infrastructure class not yet created
    // -------------------------------------------------------------------------
    'ProductClient' => [
        'class'               => ProductClient::class,
        'req_id'              => true,
        'var_annotations'     => false,
        'callers'             => [
            'src/Infrastructure/Persistence/Client/Client.php',
            'src/Invoice/Entity/Product.php',
            'src/Invoice/ProductClient/ProductClientController.php',
            'src/Invoice/ProductClient/ProductClientForm.php',
            'src/Invoice/ProductClient/ProductClientRepository.php',
            'src/Invoice/ProductClient/ProductClientService.php',
            'resources/views/invoice/productclient/_form.php',
            'resources/views/invoice/productclient/_view.php',
        ],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'ClientCustom'                  => null,
    'ClientNote'                    => null,
    'ClientPeppol'                  => null,
    'Company'                       => null,
    'CompanyPrivate'                => null,
    'Contract'                      => null,
    'CustomField'                   => null,
    'CustomValue'                   => null,
    'Delivery'                      => null,
    'DeliveryParty'                 => null,
    'EmailTemplate'                 => null,
    'Family'                        => null,
    'FamilyCustom'                  => null,
    'FromDropDown'                  => null,
    'Gentor'                        => null,
    'GentorRelation'                => null,
    'Group'                         => null,
    'Inv'                           => null,
    'InvAmount'                     => null,
    'InvCustom'                     => null,
    'InvItem'                       => null,
    'InvItemAllowanceCharge'        => null,
    'InvItemAmount'                 => null,
    'InvRecurring'                  => null,
    'InvSentLog'                    => null,
    'InvTaxRate'                    => null,
    'ItemLookup'                    => null,
    'Merchant'                      => null,
    'Payment'                       => null,
    'PaymentCustom'                 => null,
    'PaymentMethod'                 => null,
    'PaymentPeppol'                 => null,
    'PostalAddress'                 => null,
    'Product'                       => null,
    'ProductCustom'                 => null,
    'ProductImage'                  => null,
    'ProductProperty'               => null,
    'Profile'                       => null,
    'Project'                       => null,
    'Qa'                            => null,
    'Quote'                         => null,
    'QuoteAllowanceCharge'          => null,
    'QuoteAmount'                   => null,
    'QuoteCustom'                   => null,
    'QuoteItem'                     => null,
    'QuoteItemAllowanceCharge'      => null,
    'QuoteItemAmount'               => null,
    'QuoteTaxRate'                  => null,
    'SalesOrder' => [
        'class'               => SalesOrder::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [
            'src/Infrastructure/Persistence/SalesOrderItem/SalesOrderItem.php',
            'src/Infrastructure/Persistence/SalesOrderItemAllowanceCharge/SalesOrderItemAllowanceCharge.php',
            'src/Command/Invoice/SalesOrderTruncate3Command.php',
            'src/Invoice/Helpers/PdfHelper.php',
            'src/Invoice/Quote/Trait/QuoteToSo.php',
            'src/Invoice/SalesOrder/SalesOrderController.php',
            'src/Invoice/SalesOrder/SalesOrderForm.php',
            'src/Invoice/SalesOrder/SalesOrderRepository.php',
            'src/Invoice/SalesOrder/SalesOrderService.php',
            'src/Invoice/SalesOrderItem/SalesOrderItemController.php',
            'resources/views/invoice/salesorder/guest.php',
            'resources/views/invoice/salesorder/index.php',
        ],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'SalesOrderAllowanceCharge' => [
        'class'               => SalesOrderAllowanceCharge::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [
            'src/Invoice/Helpers/NumberHelper.php',
            'src/Invoice/Quote/Trait/QuoteToSo.php',
            'src/Invoice/SalesOrder/SalesOrderController.php',
            'src/Invoice/SalesOrderAllowanceCharge/SalesOrderAllowanceChargeForm.php',
            'src/Invoice/SalesOrderAllowanceCharge/SalesOrderAllowanceChargeRepository.php',
            'src/Invoice/SalesOrderAllowanceCharge/SalesOrderAllowanceChargeService.php',
        ],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'SalesOrderAmount' => [
        'class'               => SalesOrderAmount::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [
            'src/Command/Invoice/SalesOrderTruncate3Command.php',
            'src/Infrastructure/Persistence/SalesOrder/SalesOrder.php',
            'src/Invoice/Helpers/NumberHelper.php',
            'src/Invoice/Quote/Trait/QuoteToSo.php',
            'src/Invoice/SalesOrder/SalesOrderController.php',
            'src/Invoice/SalesOrder/SalesOrderService.php',
            'src/Invoice/SalesOrderAmount/SalesOrderAmountForm.php',
            'src/Invoice/SalesOrderAmount/SalesOrderAmountRepository.php',
            'src/Invoice/SalesOrderAmount/SalesOrderAmountService.php',
            'resources/views/invoice/salesorder/guest.php',
            'resources/views/invoice/salesorder/index.php',
            'resources/views/invoice/salesorder/partial_item_table.php',
            'resources/views/invoice/salesorder/partial_item_table_with_peppol.php',
            'resources/views/invoice/salesorder/view.php',
            'resources/views/invoice/template/salesorder/pdf/salesorder.php',
            'resources/views/invoice/template/salesorder/public/SalesOrder_Web.php',
        ],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'SalesOrderCustom'              => null,
    'SalesOrderItem' => [
        'class'               => SalesOrderItem::class,
        'req_id'              => true,
        'var_annotations'     => true,
        'callers'             => [
            'src/Infrastructure/Persistence/SalesOrderItemAllowanceCharge/SalesOrderItemAllowanceCharge.php',
            'src/Command/Invoice/SalesOrderTruncate3Command.php',
            'src/Invoice/Helpers/NumberHelper.php',
            'src/Invoice/Helpers/PdfHelper.php',
            'src/Invoice/SalesOrder/SalesOrderController.php',
            'src/Invoice/SalesOrder/SalesOrderService.php',
            'src/Invoice/SalesOrderAmount/SalesOrderAmountService.php',
            'src/Invoice/SalesOrderItem/SalesOrderItemController.php',
            'src/Invoice/SalesOrderItem/SalesOrderItemForm.php',
            'src/Invoice/SalesOrderItem/SalesOrderItemRepository.php',
            'src/Invoice/SalesOrderItem/SalesOrderItemService.php',
        ],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'SalesOrderItemAmount'          => null,
    'SalesOrderTaxRate'             => null,
    'Setting'                       => null,
    'Task' => [
        'class'               => Task::class,
        'req_id'              => true,
        'var_annotations'     => false,
        'callers'             => [
            'src/Command/Invoice/NonUserRelatedTruncate4Command.php',
            'src/Infrastructure/Persistence/SalesOrderItem/SalesOrderItem.php',
            'src/Invoice/Entity/InvItem.php',
            'src/Invoice/Entity/QuoteItem.php',
            'src/Invoice/InvItem/InvItemService.php',
            'src/Invoice/Report/ReportController.php',
            'src/Invoice/Task/TaskController.php',
            'src/Invoice/Task/TaskForm.php',
            'src/Invoice/Task/TaskRepository.php',
            'src/Invoice/Task/TaskService.php',
            'resources/views/invoice/dashboard/index.php',
            'resources/views/invoice/inv/partial_item_table.php',
            'resources/views/invoice/invitem/_item_form_task.php',
            'resources/views/invoice/quote/partial_item_table.php',
            'resources/views/invoice/quoteitem/_item_edit_form_task.php',
            'resources/views/invoice/quoteitem/_item_form_task.php',
            'resources/views/invoice/salesorder/partial_item_table.php',
            'resources/views/invoice/task/index.php',
            'resources/views/invoice/task/partial_task_table_modal.php',
        ],
        'callers_updated'     => true,
        'null_guards_removed' => true,
        'view_get_id_updated' => true,
        'group_use'           => true,
        'psalm'               => true,
        'entity_removed'      => true,
    ],
    'Unit'                          => null,
    'UnitPeppol'                    => null,
    'Upload'                        => null,
    'UserClient'                    => null,
    'UserInv'                       => null,
];
