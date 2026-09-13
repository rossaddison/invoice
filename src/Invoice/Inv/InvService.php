<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\AppConstants;
// Entities
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAuditLog\InvAuditLog;
use App\Infrastructure\Persistence\User\User;
// Repositories
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\InvAuditLog\InvAuditFieldDiffer;
use App\Invoice\InvAuditLog\InvAuditLogRepository;
use App\Invoice\Setting\SettingRepository as SR;
// Helpers
use App\Invoice\Helpers\DateHelper;
// Ancillary
use App\User\UserService;
use Cycle\Database\DatabaseManager;
use Yiisoft\Security\Random;
use Yiisoft\Translator\TranslatorInterface as Translator;
use DateTimeImmutable;

final readonly class InvService
{
    public function __construct(
        private InvRepository $repository,
        private Translator $translator,
        private CR $cR,
        private GR $gR,
        private DatabaseManager $dbal,
        private UserService $userService,
        private InvAuditLogRepository $auditLogRepository,
        private InvAuditFieldDiffer $auditFieldDiffer,
    ) {
    }

    public function withTransaction(callable $fn): void
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $this->dbal->database()->transaction($fn);
    }

    public function saveInv(
        User $user,
        Inv $model,
        array $array,
        SR $s,
        GR $gR
    ): Inv {
        // Snapshot the audit-worthy fields' CURRENT (pre-save) values before
        // anything below mutates them -- only meaningful for an existing
        // invoice; hasIdentity() is false for a brand new one, and every
        // "before" getter here (reqClientId() etc.) requires an already-
        // persisted record with real, non-null values.
        $isUpdate = $model->hasIdentity();
        $before = $isUpdate ? $this->snapshotAuditFields($model) : null;
        $beforePassword = $isUpdate ? $model->getPassword() : null;

        $this->persist($model, $array, $user);
        /**
         * Give a legitimate invoice number to an invoice that currently:
         * 1. Exists
         * 2. Has no invoice number
         * 3. Has a status of 'sent'
         */
        if (($model->hasIdentity()) && (strlen($model->getNumber() ?? '') == 0)
                && ($array['status_id'] == 2)) {
            $model->setNumber(
                (string) $gR->generateNumber(
                    (int) $array['group_id'],
                    true
                )
            );
        }

        /**
         * The following fields are not set on the form but are calculated
         *  automatically
         */

        $datetime_created = new DateTimeImmutable();

        /**
         * @var string $array['date_created']
         */
        $date_created = $array['date_created'] ??
                (new DateTimeImmutable('now'))->format('Y-m-d');
        $model->setDateCreated($date_created);

        $datetime_supplied = new DateTimeImmutable();
        /**
         * @var string $array['date_supplied']
         */
        $date_supplied = $array['date_supplied'] ??
                (new DateTimeImmutable('now'))->format('Y-m-d');
        $model->setDateSupplied($datetime_supplied::createFromFormat(
            'Y-m-d',
            $date_supplied
        ) ?: new DateTimeImmutable('1901/01/01'));


        $date_tax_point_raw = (string) ($array['date_tax_point'] ?? '');
        if ($date_tax_point_raw !== '') {
            $model->setDateTaxPoint(
                DateTimeImmutable::createFromFormat('Y-m-d', $date_tax_point_raw)
                    ?: new DateTimeImmutable('1901/01/01')
            );
        } else {
            $datetimeimmutable_tax_point = $this->setTaxPoint(
                $model,
                $datetime_supplied::createFromFormat('Y-m-d', $date_supplied) ?:
                    new DateTimeImmutable('1901/01/01'),
                $datetime_created::createFromFormat('Y-m-d', $date_created) ?:
                    new DateTimeImmutable('1901/01/01'),
            );
            if (null !== $datetimeimmutable_tax_point) {
                $model->setDateTaxPoint($datetimeimmutable_tax_point);
            }
        }

        // Once a GoCardless payment has been scheduled, date_due must stop
        // moving so it can't drift away from the direct_debit_date the
        // customer was actually told about.
        if (null === $model->getDirectDebitDate()) {
            $model->setDateDue($s);
        }

        $model->setUrlKey(Random::string(32));
        $model->setStandInCode($s->getSetting('stand_in_code'));

        $this->setOptionalArrayFields($model, $array);
        if (!$model->hasIdentity()) {
            $this->initNewInvFields($model, $s, $gR, $user, $array);
        }
        $this->repository->save($model);
        $this->recordAuditLog($model, $before, $beforePassword);
        return $model;
    }

    /**
     * The audit-worthy fields' current values, keyed the same way on both
     * the "before" and "after" snapshot so InvAuditFieldDiffer::diff() can
     * compare them directly. Deliberately excludes url_key (regenerated on
     * every single save via Random::string(32) above, regardless of
     * whether anything else changed -- diffing it would just be noise on
     * every row) and password (its own VALUE is never recorded in the
     * audit trail at all -- see recordAuditLog()'s own handling of it via
     * $beforePassword/getPassword() instead of this snapshot, so a
     * shareable public-invoice-link password can't leak into a log other
     * staff can read).
     *
     * @return array<string, bool|int|float|string|null>
     */
    private function snapshotAuditFields(Inv $model): array
    {
        return [
            'client_id' => $model->reqClientId(),
            'group_id' => $model->reqGroupId(),
            'status_id' => $model->reqStatusId(),
            'so_id' => $model->getSoId(),
            'quote_id' => $model->getQuoteId(),
            'contract_id' => $model->getContractId(),
            'delivery_id' => $model->getDeliveryId(),
            'delivery_location_id' => $model->getDeliveryLocationId(),
            'postal_address_id' => $model->getPostalAddressId(),
            'discount_amount' => $model->getDiscountAmount(),
            'payment_method' => $model->getPaymentMethod(),
            'creditinvoice_parent_id' => $model->getCreditinvoiceParentId(),
            'terms' => $model->getTerms(),
            'note' => $model->getNote(),
            'document_description' => $model->getDocumentDescription(),
            'client_po_number' => $model->getClientPoNumber(),
            'client_po_person' => $model->getClientPoPerson(),
            'date_created' => $model->getDateCreated()->format('Y-m-d'),
            'date_supplied' => $model->getDateSupplied()->format('Y-m-d'),
            'date_tax_point' => $model->getDateTaxPoint()->format('Y-m-d'),
            'date_due' => $model->getDateDue()->format('Y-m-d'),
            'number' => $model->getNumber(),
        ];
    }

    /**
     * Writes one InvAuditLog row per saveInv() call that either creates an
     * invoice, or actually changes one of the fields snapshotAuditFields()
     * covers -- a save that touches nothing audit-worthy (e.g. only
     * url_key's own unconditional regeneration) writes nothing at all.
     *
     * The acting user is resolved from UserService::getUser() -- the
     * actual signed-in staff member making THIS request -- not from
     * saveInv()'s own $user parameter, which is the invoice's assigned
     * client-portal owner (see InvController::activeUser()), a different
     * concept entirely. Null when there is no interactively signed-in user
     * (e.g. the recurring-invoice cron), recorded as such rather than
     * guessed.
     *
     * @param array<string, bool|int|float|string|null>|null $before Null
     *     for a brand new invoice -- see saveInv()'s own $isUpdate.
     */
    private function recordAuditLog(
        Inv $model,
        ?array $before,
        ?string $beforePassword,
    ): void {
        if (null === $before) {
            $this->writeAuditLog($model, 'created', null);
            return;
        }

        $after = $this->snapshotAuditFields($model);
        $diff = $this->auditFieldDiffer->diff($before, $after);
        if ($model->getPassword() !== $beforePassword) {
            $diff['password'] = ['old' => '(redacted)', 'new' => '(redacted)'];
        }
        if ($diff === []) {
            return;
        }
        $changedFields = json_encode($diff, JSON_THROW_ON_ERROR);
        $this->writeAuditLog($model, 'updated', $changedFields);
    }

    private function writeAuditLog(
        Inv $model,
        string $action,
        ?string $changedFields,
    ): void {
        $auditUser = $this->userService->getUser();
        $log = new InvAuditLog(
            inv_id: $model->reqId(),
            user_id: $auditUser?->reqId(),
            action: $action,
            changed_fields: $changedFields,
        );
        $log->setInv($model);
        null !== $auditUser and $log->setUser($auditUser);
        $this->auditLogRepository->save($log);
    }

    private function setOptionalArrayFields(Inv $model, array $array): void
    {
        $this->applyInvReferenceIds($model, $array);
        $this->applyInvLocationIds($model, $array);
        $this->applyInvFinancialFields($model, $array);
        $this->applyInvTextFields($model, $array);
    }

    private function applyInvReferenceIds(Inv $model, array $array): void
    {
        isset($array['client_id']) ? $model->setClientId((int) $array['client_id']) : '';
        isset($array['group_id']) ? $model->setGroupId((int) $array['group_id']) : '';
        isset($array['so_id']) ? $model->setSoId((int) $array['so_id']) : '';
        isset($array['quote_id']) ? $model->setQuoteId((int) $array['quote_id']) : '';
        if (isset($array['status_id'])) {
            $targetStatusId = (int) $array['status_id'];
            // A HomeCare worker's do_not_send flag (see Trait\Guest::setDoNotSend())
            // blocks every transition to "sent" on an existing invoice, including
            // this generic form-driven one used by inv/edit's status dropdown.
            $blocked = $model->hasIdentity() && $targetStatusId === 2 && $model->blocksSending();
            if (!$blocked) {
                $model->setStatusId($targetStatusId);
            }
        }
        isset($array['contract_id']) ? $model->setContractId((int) $array['contract_id']) : '';
    }

    private function applyInvLocationIds(Inv $model, array $array): void
    {
        isset($array['delivery_id']) ? $model->setDeliveryId((int) $array['delivery_id']) : '';
        isset($array['delivery_location_id']) ?
            $model->setDeliveryLocationId((int) $array['delivery_location_id']) : '';
        isset($array['postal_address_id']) ?
            $model->setPostalAddressId((int) $array['postal_address_id']) : '';
    }

    private function applyInvFinancialFields(Inv $model, array $array): void
    {
        isset($array['discount_amount']) ?
            $model->setDiscountAmount((float) $array['discount_amount']) : '';
        isset($array['payment_method']) ?
            $model->setPaymentMethod((int) $array['payment_method']) : '';
        if (isset($array['creditinvoice_parent_id'])) {
            $model->setCreditinvoiceParentId((int) $array['creditinvoice_parent_id'] ?: 0);
        }
    }

    private function applyInvTextFields(Inv $model, array $array): void
    {
        isset($array['password']) ? $model->setPassword((string) $array['password']) : '';
        isset($array['terms']) ?
            $model->setTerms((string) $array['terms']) :
            $this->translator->translate('payment.term.general');
        isset($array['note']) ? $model->setNote((string) $array['note']) : '';
        isset($array['document_description']) ?
            $model->setDocumentDescription((string) $array['document_description']) : '';
        isset($array['client_po_number']) ?
            $model->setClientPoNumber((string) $array['client_po_number']) : '';
        isset($array['client_po_person']) ?
            $model->setClientPoPerson((string) $array['client_po_person']) : '';
    }

    private function initNewInvFields(Inv $model, SR $s, GR $gR, User $user, array $array): void
    {
        if ($s->getSetting('mark_invoices_sent_copy') === '1') {
            $model->setStatusId(2);
            $model->setIsReadOnly(true);
        } else {
            $model->setStatusId(1);
            $model->setIsReadOnly(false);
        }
        if ($s->getSetting('generate_invoice_number_for_draft') === '1') {
            $model->setNumber((string) $gR->generateNumber((int) $array['group_id'], true));
        } else {
            $model->setNumber('');
        }
        $model->setUserId($user->reqId());
        $model->setTimeCreated((new DateTimeImmutable('now'))->format('H:i:s'));
        $model->setPaymentMethod((int) $s->getSetting('invoice_default_payment_method') ?: 4);
        if (!isset($array['discount_amount'])) {
            $model->setDiscountAmount(0.00);
        }
    }

    private function persist(Inv $model, array $array, User $user): void
    {
        $client = 'client_id';
        if (isset($array[$client])) {
            $model->setClient($this->cR->repoClientquery((int) $array[$client]));
        }
        $group = 'group_id';
        if (isset($array[$group])) {
            $model->setGroup(
                $this->gR->repoGroupQuery((int) $array[$group])
            );
        }
        // Inv::$user is a BelongsTo(nullable: false) relation -- Cycle's
        // writer needs the actual related object, not just the user_id
        // scalar InvService::initNewInvFields() sets below. Previously this
        // was only set when $array['user_id'] happened to be present, which
        // several real callers (e.g. SalesOrderController::soToInvoiceConfirm())
        // never populate -- Cycle then silently produced no INSERT at all
        // for the entity (no exception), leaving $model->reqId() to throw
        // "Inv not persisted" the moment the caller tried to read the new
        // id back. saveInv() always receives a concrete $user already, so
        // just use it directly instead of relying on the array key.
        $model->setUser($user);
    }

    public function copyInv(User $user, Inv $model, array $array, SR $s): Inv
    {
        /**
         * Follows Inv construct sequence to make sure no fields are missing.
         */
        $model->setClientId((int) $array['client_id']);
        $model->setGroupId((int) $array['group_id']);
        $model->setSoId((int) $array['so_id']);
        $model->setQuoteId((int) $array['quote_id']);
        $model->setUserId($user->reqId());
        $model->setStatusId((int) $array['status_id']);
        $model->setIsReadOnly((bool) $array['is_read_only']);
        $model->setPassword((string) $array['password']);
        $model->setDateDue($s);
        $model->setDateSupplied(
            $array['date_supplied'] instanceof DateTimeImmutable ?
                $array['date_supplied'] :
                new DateTimeImmutable('now')
        );
        $model->setDateTaxPoint(
            $array['date_tax_point'] instanceof DateTimeImmutable ?
                $array['date_tax_point'] :
                new DateTimeImmutable('now')
        );
        $model->setTimeCreated((string) $array['time_created']);
        $model->setStandInCode((string) $array['stand_in_code']);
        $model->setNumber((string) $array['number']);
        $model->setDiscountAmount(
            (float) $array['discount_amount']
        );
        $model->setTerms(
            (string) $array['terms'] ?:
                $this->translator->translate(
                    'payment.term.general'
                )
        );
        $model->setNote((string) $array['note']);
        $model->setDocumentDescription(
            (string) $array['document_description']
        );
        $model->setUrlKey((string) $array['url_key']);
        $model->setPaymentMethod(
            (int) $array['payment_method'] ?: 4
        );
        $model->setCreditinvoiceParentId(
            (int) $array['creditinvoice_parent_id'] ?: 0
        );
        $model->setDeliveryId((int) $array['delivery_id']);
        $model->setDeliveryLocationId(
            (int) $array['delivery_location_id']
        );
        $model->setPostalAddressId(
            (int) $array['postal_address_id']
        );
        $model->setContractId((int) $array['contract_id']);
        $model->setClientPoNumber((string) ($array['client_po_number'] ?? ''));
        $model->setClientPoPerson((string) ($array['client_po_person'] ?? ''));
        $this->repository->save($model);
        // copyInv()'s only real caller (MultipleCopy::copyInvToClient())
        // always passes a brand new Inv() -- see this method's own
        // "Follows Inv construct sequence" docblock above -- so this is
        // always a creation, never an update; no before/after diff needed.
        $this->writeAuditLog($model, 'created', null);
        return $model;
    }

    /**
     * Related logic: see
     * https://www.gov.uk/hmrc-internal-manuals/
     * vat-time-of-supply/vattos3600
     * @param Inv $inv
     * @param DateTimeImmutable|null $date_supplied
     * @param DateTimeImmutable|null $date_created
     * @return DateTimeImmutable|null
     */
    public function setTaxPoint(
        Inv $inv,
        ?DateTimeImmutable $date_supplied,
        ?DateTimeImmutable $date_created
    ): ?DateTimeImmutable {
        // Terminoligy: 'Date created' is used
        // interchangeably with 'Date issued'
        if (null !== $inv->getClient()?->getClientVatId()) {
            if ($date_created > $date_supplied
                && null !== $date_created
                && null !== $date_supplied) {
                $diff = $date_supplied->diff($date_created)->format('%R%a');
                // if more than 14 days use supply date; otherwise use issue/created date
                return ((int) $diff > 14) ? $date_supplied : $date_created;
            }
            if ($date_created < $date_supplied || $date_created === $date_supplied) {
                // normally set the tax point to the date_created
                return $date_created;
            }
        }
        // If the client is not VAT registered, the tax point is the date supplied;
        // if VAT-registered but fell through (one date is null), return null.
        return (null === $inv->getClient()?->getClientVatId()) ? $date_supplied : null;
    }

    /**
     * @param User $user
     * @param Inv $model
     * @param array $details
     * @param SR $s
     */
    public function saveInvFromRecurring(User $user, Inv $model, array $details, SR $s): void
    {
        // Same before/after audit snapshot as saveInv() -- see its own
        // docblock on $isUpdate for why this must happen before anything
        // below mutates the model. Currently dead in production (no real
        // caller resolves to this method instead of saveInv() -- the
        // actual recurring-invoice cron, InvRecurringCronService::process(),
        // already calls saveInv() directly with a fresh Inv()), but this
        // is still public API and Testo-tested, so it gets the same
        // coverage as every other write path.
        $isUpdate = $model->hasIdentity();
        $before = $isUpdate ? $this->snapshotAuditFields($model) : null;
        $beforePassword = $isUpdate ? $model->getPassword() : null;

        $datehelper = new DateHelper($s);
        $datetime = $datehelper->getOrSetWithStyle($details['date_created'] ?? new \DateTime());
        $datetimeimmutable = new DateTimeImmutable($datetime instanceof \DateTime ? $datetime->format(AppConstants::DATETIME_FORMAT) : 'now');
        $model->setDateCreated($datetimeimmutable->format('Y-m-d'));

        $datetime_supplied = $datehelper->getOrSetWithStyle($details['date_supplied'] ?? new \DateTime());
        $datetimeimmutable_supplied = new DateTimeImmutable($datetime_supplied instanceof \DateTime ? $datetime_supplied->format(AppConstants::DATETIME_FORMAT) : 'now');
        $model->setDateSupplied($datetimeimmutable_supplied);

        $datetime_tax_point = $datehelper->getOrSetWithStyle($details['date_tax_point'] ?? new \DateTime());
        $datetimeimmutable_tax_point = new DateTimeImmutable($datetime_tax_point instanceof \DateTime ? $datetime_tax_point->format(AppConstants::DATETIME_FORMAT) : 'now');
        $model->setDateTaxPoint($datetimeimmutable_tax_point);

        $model->setDateDue($s);
        $model->setClientId((int) $details['client_id']);
        $model->setGroupId((int) $details['group_id']);
        $model->setStatusId((int) $details['status_id']);
        $model->setDiscountAmount((float) $details['discount_amount']);
        $model->setUrlKey((string) $details['url_key']);
        $model->setPassword((string) $details['password']);
        $model->setPaymentMethod((int) $details['payment_method']);
        $model->setTerms((string) $details['terms']);
        $model->setCreditinvoiceParentId((int) $details['creditinvoice_parent_id'] ?: 0);
        $model->setDeliveryId((int) $details['delivery_id'] ?: 0);
        $model->setDeliveryLocationId((int) $details['delivery_location_id'] ?: 0);
        $model->setPostalAddressId((int) $details['postal_address_id'] ?: 0);
        $model->setContractId((int) $details['contract_id'] ?: 0);
        if (!$model->hasIdentity()) {
            $model->setStatusId(1);
            $model->setNumber((string) $details['number']);
            $random = new Random();
            $model->setUser($user);
            $model->setUrlKey($random::string(32));
            $model->setDateCreated((new DateTimeImmutable('now'))->format('Y-m-d'));
            // VAT or cash basis tax system fields: ignore
            $model->setDateSupplied(new DateTimeImmutable('now'));
            $model->setDateTaxPoint(new DateTimeImmutable('now'));
            $model->setTimeCreated((new DateTimeImmutable('now'))->format('H:i:s'));
            $model->setPaymentMethod(0);
            $model->setDateDue($s);
            $model->setDiscountAmount(0.00);
        }
        $this->repository->save($model);
        $this->recordAuditLog($model, $before, $beforePassword);
    }

    public function deleteInv(Inv $inv): void
    {
        $this->repository->delete($inv);
        $this->writeAuditLog($inv, 'deleted', null);
    }

    public function restoreInv(Inv $inv): void
    {
        $inv->restore();
        $this->repository->save($inv);
        $this->writeAuditLog($inv, 'restored', null);
    }
}
