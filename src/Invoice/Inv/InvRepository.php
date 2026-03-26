<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Invoice\Entity\Inv;
use App\Invoice\Entity\InvItem;
use App\Invoice\Group\GroupRepository as GR;
use App\Invoice\Setting\SettingRepository as SR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use Cycle\ORM\Select;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Translator\TranslatorInterface as Translator;
use Yiisoft\Security\Random;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of Inv
 * @extends Select\Repository<TEntity>
 */
final class InvRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     * @param Translator $translator
     */
    public function __construct(
        Select $select,
        private readonly EntityWriter $entityWriter,
        private readonly Translator $translator
    )
    {
        parent::__construct($select->load('client')->where(
                ['client.client_active' => 1]));
    }

    public function filterInvNumber(string $invNumber): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['number' => ltrim(rtrim($invNumber))]);
        return $this->prepareDataReader($query);
    }

    public function filterCreditInvNumber(string $creditInvNumber): EntityReader
    {
        $select = $this->select();
        $trimmed = ltrim(rtrim($creditInvNumber));
        $parentInvs = $this->select()
                           ->where('number', 'like', $trimmed . '%')
                           ->fetchAll();
        $parentIds = [];
        /** @var Inv $parentInv */
        foreach ($parentInvs as $parentInv) {
            $parentIds[] = (string) $parentInv->getId();
        }
        $query = $parentIds === []
            ? $select->where(['id' => '0'])
            : $select->where(['creditinvoice_parent_id' =>
                ['in' => new Parameter($parentIds)]]);
        return $this->prepareDataReader($query);
    }

    public function filterFamilyName(string $invFamilyName): EntityReader
    {
        $select = $this->select();
        $query = $select
                /**
                 * Related logic: Entity Inv
                 *  #[HasMany(target: InvItem::class)]
                 *  private readonly ArrayCollection $items;
                 *  The load('items') below derives from $items above
                 *  Also see: Entity InvItem .. private ?Product $product = null;
                 *  Also see: Entity Product .. private ?Family $family = null;
                 *  Also see: Entity Family .. public ?string $family_name = '',
                 */
                ->load('items')
                ->where(['items.product.family.family_name' => $invFamilyName]);
        return $this->prepareDataReader($query);
    }

    public function filterInvAmountTotal(string $invAmountTotal): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('invAmount')
                 ->where('invAmount.total', 'like', $invAmountTotal . '%');
        return $this->prepareDataReader($query);
    }

    public function filterInvAmountPaid(string $invAmountPaid): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('invAmount')
                 ->where('invAmount.paid', 'like', $invAmountPaid . '%');
        return $this->prepareDataReader($query);
    }

    public function filterInvAmountBalance(string $invAmountBalance): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('invAmount')
                 ->where('invAmount.balance', 'like', $invAmountBalance . '%');
        return $this->prepareDataReader($query);
    }

    public function filterInvNumberAndInvAmountTotal(
            string $invNumber, float $invAmountTotal): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('invAmount')
                 ->where(['number' => $invNumber])
                 ->andWhere(['invAmount.total' => $invAmountTotal]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $status_id
     * @return EntityReader
     */
    public function findAllWithStatus(int $status_id): EntityReader
    {
        if ($status_id > 0) {
            $query = $this->select()
                    ->load(['client','group','user'])
                    ->where(['status_id' => $status_id]);
            return $this->prepareDataReader($query);
        }
        return $this->findAllPreloaded();
    }
    
    public function filterGuestClient(string $fullName): EntityReader
    {
        $nameParts = explode(' ', $fullName);
        $firstName = $nameParts[0];
        $secondName = $nameParts[1];
        $query = $this->select()
                       ->load(['client'])
                       ->where(['client.client_name' => $firstName])
                       ->where(['client.client_surname' => $secondName])
                       ->andWhere(['status_id' => ['in' =>
                            new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]]);
        return $this->prepareDataReader($query);
    }

    public function filterClient(string $fullName): EntityReader
    {
        $nameParts = explode(' ', $fullName);
        $firstName = $nameParts[0];
        $secondName = $nameParts[1];
        $query = $this->select()
                       ->load(['client'])
                       ->where(['client.client_name' => $firstName])
                       ->where(['client.client_surname' => $secondName]);
        return $this->prepareDataReader($query);
    }

    public function filterClientGroup(string $clientGroup): EntityReader
    {
        $select = $this->select()
                       ->load(['client']);
        $query = $select->where([
            'client.client_group' => ltrim(rtrim($clientGroup))]);
        return $this->prepareDataReader($query);
    }

    public function filterClientAddress1(string $clientAddress1): EntityReader
    {
        $select = $this->select()
                       ->load(['client']);
        $query = $select->where('client.client_address_1', 'like',
            ltrim(rtrim($clientAddress1)) . '%');
        return $this->prepareDataReader($query);
    }

    public function filterDateCreatedLike(string $format, string $dateCreated):
        EntityReader
    {
        $select = $this->select();
        $dateTimeImmutable =
                \DateTimeImmutable::createFromFormat($format, $dateCreated);
        $query = $select->where(
            'date_created',
            'like',
            $dateTimeImmutable instanceof \DateTimeImmutable
                                ? $dateTimeImmutable->format('Y-m') . '%' : '',
        );
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $client_id
     * @return EntityReader
     */
    public function findAllWithClient(int $client_id): EntityReader
    {
        $query = $this->select()
                ->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $contract_id
     * @return EntityReader
     */
    public function findAllWithContract(int $contract_id): EntityReader
    {
        $query = $this->select()
                      ->where(['contract_id' => $contract_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $delivery_location_id
     * @return EntityReader
     */
    public function findAllWithDeliveryLocation(int $delivery_location_id):
        EntityReader
    {
        $query = $this->select()
                      ->where(['delivery_location_id' => $delivery_location_id]);
        return $this->prepareDataReader($query);
    }
    
    /**
     * @param int $user_id
     * @param int $client_id
     * @return int
     */
    public function countAllWithUserClient(int $user_id, int $client_id): int
    {
        return $this->select()
                ->load(['user', 'client'])
                ->where(['user.id' => $user_id])
                ->andWhere(['client.id' => $client_id])
                ->count();
    }

    /**
     * Get invoices without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                ->load(['client','group','user']);
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }

    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        // Provide the latest invoice at the top of the list and order
        //  additionally according to status
        return Sort::only(['id', 'status'])->withOrder([
            'id' => 'desc', 'status' => 'asc']);
    }

    public function save(array|Inv|null $inv): void
    {
        $this->entityWriter->write([$inv]);
    }

    public function delete(array|Inv|null $inv): void
    {
        $this->entityWriter->delete([$inv]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'desc']),
        );
    }

    /**
     * @param string $id
     * @return int
     */
    public function repoCount(string $id): int
    {
        return $this->select()
                ->where(['id' => $id])
                ->count();
    }

    /**
     * @return int
     */
    public function repoCountAll(): int
    {
        return $this->select()
                    ->count();
    }

    /**
     * @param int $invoice_id
     * @param int $status_id
     * @return Inv|null
     */
    public function repoInvStatusquery(int $invoice_id, int $status_id): ?Inv
    {
        $query = $this->select()
                      ->where(['id' => $invoice_id])
                      ->where(['status_id' => $status_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $id
     * @return Inv|null
     */
    public function repoInvUnLoadedquery(string $id): ?Inv
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    public function repoInvLoadInvAmountquery(string $id): ?Inv
    {
        $query = $this->select()
                      ->load('invAmount')
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $id
     * @return Inv|null
     */
    public function repoInvLoadedquery(string $id): ?Inv
    {
        $query = $this->select()
                      ->load(['client','group','user'])
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @return Inv|null
     *
     * @psalm-return TEntity|null
     */
    public function repoUrlKeyGuestLoaded(string $url_key): ?Inv
    {
        $query = $this->select()
                       ->load('client')
                       ->where(['url_key' => $url_key])
                       ->andWhere(
                               ['status_id' => ['in' => new Parameter([2,3,4])]]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $url_key
     * @return int
     */
    public function repoUrlKeyGuestCount(string $url_key): int
    {
        return $this->select()
                      ->where(['url_key' => $url_key])
                      ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                      ->count();
    }

    /**
     * @psalm-return Select<TEntity>
     */
    public function repoClientGuestCount(int $inv_id, array $user_client = []):
        Select
    {
        return $this->select()
                      ->where(['id' => $inv_id])
                      // sent = 2, viewed = 3, paid = 4
                      ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                      ->andWhere(['client_id' => ['in' => new Parameter(
                              $user_client)]]);
    }

    /**
     * @psalm-return EntityReader
     * @param int $status_id
     * @param array $user_client
     */
    public function repoGuestClientsPostDraft(int $status_id, array $user_client = []): EntityReader
    {
    // sent = 2, viewed = 3, paid = 4, overdue = 5, unpaid = 6, reminder sent = 7,
    // 7 day letter before action = 8, started a legal claim = 9
    // judgement obtained = 10, enforcement officer attending address = 11,
    // credit note = 12, written off = 13
        if ($status_id > 0) {
            $query = $this->select()
                    ->where(['status_id' => $status_id])
                    ->where(['client_id' => ['in' => new Parameter($user_client)]])
                    ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]]);
            return $this->prepareDataReader($query);
        }   // Get all the invoices
        $query = $this->select()
                     ->where(['client_id' => ['in' => new Parameter($user_client)]])
                     ->andWhere(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function open(): EntityReader
    {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3])]]);
        return $this->prepareDataReader($query);
    }

    public function openCount(): int
    {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        // 2,3 => There is still a balance available => Not paid
        return $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3])]])
                      ->count();
    }

    /**
     * @psalm-return EntityReader
     */
    public function guestVisible(): EntityReader
    {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function isDraft(): EntityReader
    {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([1])]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function isSent(): EntityReader
    {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([2])]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function isViewed(): EntityReader
    {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([3])]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function isPaid(): EntityReader
    {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([4])]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function isOverdue(): EntityReader
    {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id' => ['in' => new Parameter([5])]]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $client_id
     * @return EntityReader
     */
    public function byClient(int $client_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param $client_id
     * @param $status_id
     *
     * @psalm-return EntityReader
     */
    public function byClientInvStatus(int $client_id, int $status_id):
        EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $client_id
     * @param int $status_id
     * @return int
     */
    public function byClientInvStatusCount(int $client_id, int $status_id): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id])
                      ->count();
    }

    /**
     * @param Translator $translator
     * @return array
     */
    public function getStatuses(Translator $translator): array
    {
        return [
            '0' => [
                'label' => $translator->translate('all'),
                'class' => 'default',
                'href' => 0,
                'emoji' => '🌎 ',
            ],
            '1' => [
                'label' => $translator->translate('draft'),
                'class' => 'default',
                'href' => 1,
                'emoji' => '🗋 ',
            ],
            '2' => [
                'label' => $translator->translate('sent'),
                'class' => 'info',
                'href' => 2,
                'emoji' => '📨 ',
            ],
            '3' => [
                'label' => $translator->translate('viewed'),
                'class' => 'info',
                'href' => 3,
                'emoji' => '👀 ',
            ],
            '4' => [
                'label' => $translator->translate('paid'),
                'class' => 'success',
                'href' => 4,
                'emoji' => '😀 ',
            ],
            '5' => [
                'label' => $translator->translate('overdue'),
                'class' => 'warning',
                'href' => 5,
                'emoji' => '🏦 ',
            ],
            '6' => [
                'label' => $translator->translate('unpaid'),
                'class' => 'danger',
                'href' => 6,
                'emoji' => '💸 ',
            ],
            '7' => [
                'label' => $translator->translate('reminder'),
                'class' => 'info',
                'href' => 7,
                'emoji' => '🔔 ',
            ],
            '8' => [
                'label' => $translator->translate('letter'),
                'class' => 'danger',
                'href' => 8,
                'emoji' => '🗎 ',
            ],
            '9' => [
                'label' => $translator->translate('claim'),
                'class' => 'info',
                'href' => 9,
                'emoji' => '🛄 ',
            ],
            '10' => [
                'label' => $translator->translate('judgement'),
                'class' => 'success',
                'href' => 10,
                'emoji' => '🙌 ',
            ],
            '11' => [
                'label' => $translator->translate('enforcement'),
                'class' => 'primary',
                'href' => 11,
                'emoji' => '👮 ',
            ],
            '12' => [
                'label' => $translator->translate('credit.invoice.for.invoice'),
                'class' => 'default',
                'href' => 12,
                'emoji' => '🛑️ ',
            ],
            '13' => [
                'label' => $translator->translate('loss'),
                'class' => 'danger',
                'href' => 13,
                'emoji' => '❎ ',
            ],
        ];
    }

    /**
     * @param string $key
     * @return string
     */
    public function getSpecificStatusArrayLabel(string $key): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['label']
         */
        return $statuses_array[$key]['label'];
    }

    /**
     * @param int $status
     * @return string
     */
    public function getSpecificStatusArrayClass(int $status): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$status]
         * @var string $statuses_array[$status]['class']
         */
        return $statuses_array[$status]['class'];
    }

    /**
     * @param int $status
     * @return string
     */
    public function getSpecificStatusArrayEmoji(int $status): string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$status]
         * @var string $statuses_array[$status]['emoji']
         */
        return $statuses_array[$status]['emoji'];
    }

    /**
     * @param string $invoice_date_created
     * @param SR $sR
     * @return string
     */
    public function getDateDue(string $invoice_date_created, SR $sR): string
    {
        $invoice_date_due = new \DateTime($invoice_date_created);
        $invoice_date_due->add(new \DateInterval('P'
                . $sR->getSetting('invoices_due_after') . 'D'));
        return $invoice_date_due->format('Y-m-d');
    }

    /**
     * @return string
     */
    public function getUrlKey()
    {
        $random = new Random();
        return $random::string(32);
    }

    /**
     * @param string $group_id
     * @return mixed
     */
    public function getInvNumber(string $group_id, GR $gR): mixed
    {
        return $gR->generateNumber((int) $group_id);
    }

    // total = item_subtotal + item_tax_total + tax_total
    // total => sales including item tax and tax
    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function withTotal(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount =
                    ($iaR->repoInvAmountCount((int) $invoice->getId()) > 0
                    ? $iaR->repoInvquery((int) $invoice->getId()) : null);
            $sum += (null !== $invoice_amount ? $invoice_amount->getTotal()
                    ?? 0.00 : 0.00);
        }
        return $sum;
    }

    // sales without item tax and tax => item_subtotal
    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function withItemSubtotal(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery((int) $invoice->getId());
            if (null !== $invoice_amount) {
                $sum += $invoice_amount->getItemSubtotal() ?: 0.00;
            }
        }
        return $sum;
    }

    // total = item_subtotal + item_tax_total + tax_total
    // total => sales including item tax and tax
    /**
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function withTotalFromTo(
            int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery((int) $invoice->getId());
            if (null !== $invoice_amount) {
                $sum += $invoice_amount->getTotal() ?? 0.00;
            }
        }
        return $sum;
    }

    // sales without item tax and tax => item_subtotal
    /**
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function withItemSubtotalFromTo(
            int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery((int) $invoice->getId());
            if (null !== $invoice_amount) {
                $sum += $invoice_amount->getItemSubtotal();
            }
        }
        return $sum;
    }

    // First tax: Item tax total
    /**
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function withItemTaxTotalFromTo(
            int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery((int) $invoice->getId());
            if (null !== $invoice_amount) {
                $sum += $invoice_amount->getItemTaxTotal();
            }
        }
        return $sum;
    }

    // Second tax: Total tax total
    public function withTaxTotalFromTo(
            int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount =
                    ($iaR->repoInvAmountCount((int) $invoice->getId()) > 0 ?
                    $iaR->repoInvquery((int) $invoice->getId()) : null);
            $sum += (null !== $invoice_amount ? $invoice_amount->getTaxTotal()
                                                                ?? 0.00 : 0.00);
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function withPaidFromTo(
                    int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount =
                    ($iaR->repoInvAmountCount((int) $invoice->getId()) > 0 ?
                    $iaR->repoInvquery((int) $invoice->getId()) : null);
            $sum += (null !== $invoice_amount ? $invoice_amount->getPaid() ??
                    0.00 : 0.00);
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function withTotalPaid(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = (
                    $iaR->repoInvAmountCount((int) $invoice->getId()) > 0 ?
                    $iaR->repoInvquery((int) $invoice->getId()) : null);
            $sum += (null !== $invoice_amount ? $invoice_amount->getPaid() ??
                    0.00 : 0.00);
        }
        return $sum;
    }

    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return int
     */
    public function withTotalBalanceInvoices(int $client_id, IAR $iaR): int
    {
        $invoices = $this->findAllWithClient($client_id);
        $num_invoices = 0;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount =
                    ($iaR->repoInvAmountCount((int) $invoice->getId()) > 0 ?
                    $iaR->repoInvquery((int) $invoice->getId()) : null);
            $num_invoices += (null !== $invoice_amount
                    && null !== $invoice_amount->getBalance() ? 1 : 0);
        }
        return $num_invoices;
    }

    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function withTotalBalance(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = (
                    $iaR->repoInvAmountCount((int) $invoice->getId()) > 0 ?
                    $iaR->repoInvquery((int) $invoice->getId()) : null);
            $sum += (null !== $invoice_amount ? $invoice_amount->getBalance()
                                                                ?? 0.00 : 0.00);
        }
        return $sum;
    }

    /**
     * @param int|null $client_id
     * @return int
     */
    public function repoCountByClient(?int $client_id): int
    {
        return $this->select()
                      ->where(['client_id' => $client_id])
                      ->count();
    }

    /**
     * @param int $client_id
     * @param string $from_date
     * @param string $to_date
     * @return int
     */
    public function repoCountClientLoadedFromToDate(
            int $client_id, string $from_date, string $to_date): int
    {
        return $this->select()
                      ->load('client')
                      ->where(['client_id' => $client_id])
                      ->andWhere('date_created', '>=', $from_date)
                      ->andWhere('date_created', '<=', $to_date)
                      ->count();
    }

    /**
     * @param int $client_id
     * @param string $from_date
     * @param string $to_date
     * @return EntityReader
     */
    public function repoClientLoadedFromToDate(
            int $client_id, string $from_date, string $to_date): EntityReader
    {
        $query = $this->select()
                      ->load('client')
                      ->where(['client_id' => $client_id])
                      ->andWhere('date_created', '>=', $from_date)
                      ->andWhere('date_created', '<=', $to_date);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int|null $client_id
     * @return EntityReader
     */
    public function repoClient(?int $client_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int|null $product_id
     * @return int
     */
    public function repoCountByProduct(?int $product_id): int
    {
        return $this->select()
                      ->distinct()
                      ->with('items')
                      ->where('items.product_id', $product_id)
                      ->count();
    }

    /**
     * @param int $product_id
     * @param string $from_date
     * @param string $to_date
     * @return EntityReader
     */
    public function repoProductWithInvItemsFromToDate(
            int $product_id, string $from_date, string $to_date): EntityReader
    {
        $query = $this->select()
                      ->distinct()
                      ->with('items')
                      ->where('items.product_id', $product_id)
                      ->andWhere('date_created', '>=', $from_date)
                      ->andWhere('date_created', '<=', $to_date);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $product_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemSubtotalFromToUsingProduct(
            int $product_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices =
            $this->repoProductWithInvItemsFromToDate($product_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getProductId() == (string) $product_id) {
                    $inv_item_amount = $iiaR->repoInvItemAmountquery(
                                                        (string) $item->getId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getSubtotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }

    // First tax: Item tax total
    /**
     * @param int $product_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemTaxTotalFromToUsingProduct(int $product_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoProductWithInvItemsFromToDate(
                                                        $product_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getProductId() == (string) $product_id) {
                    $inv_item_amount =
                            $iiaR->repoInvItemAmountquery((string) $item->getId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getTaxTotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }

    /**
     * Refer to Entity/InvItemAmount
     * item_subtotal + item_tax_total = item_total
     *
     * @param int $product_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemTotalFromToUsingProduct(
                int $product_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices =
                $this->repoProductWithInvItemsFromToDate($product_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getProductId() == (string) $product_id) {
                    $inv_item_amount =
                        $iiaR->repoInvItemAmountquery((string) $item->getId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getTotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }

    /**
     * @param int|null $task_id
     * @return int
     */
    public function repoCountByTask(?int $task_id): int
    {
        return $this->select()
                      ->distinct()
                      ->with('items')
                      ->where('items.task_id', $task_id)
                      ->count();
    }

    /**
     * @param int $task_id
     * @param string $from_date
     * @param string $to_date
     * @return EntityReader
     */
    public function repoTaskWithInvItemsFromToDate(
                int $task_id, string $from_date, string $to_date): EntityReader
    {
        $query = $this->select()
                      ->distinct()
                      ->with('items')
                      ->where('items.task_id', $task_id)
                      ->andWhere('date_created', '>=', $from_date)
                      ->andWhere('date_created', '<=', $to_date);
        return $this->prepareDataReader($query);
    }

    /**
     * @param int $task_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemSubtotalFromToUsingTask(
                    int $task_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoTaskWithInvItemsFromToDate($task_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getTaskId() == (string) $task_id) {
                    $inv_item_amount = $iiaR->repoInvItemAmountquery(
                                                        (string) $item->getId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getSubtotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }

    // First tax: Item tax total
    /**
     * @param int $task_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemTaxTotalFromToUsingTask(
                    int $task_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoTaskWithInvItemsFromToDate($task_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getTaskId() == (string) $task_id) {
                    $inv_item_amount = $iiaR->repoInvItemAmountquery(
                                                        (string) $item->getId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getTaxTotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }

    /**
     * Refer to Entity/InvItemAmount
     * item_subtotal + item_tax_total = item_total
     *
     * @param int $task_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function withItemTotalFromToUsingTask(
                    int $task_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoTaskWithInvItemsFromToDate($task_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $items = $invoice->getItems();
            /**
             * @var InvItem $item
             */
            foreach ($items as $item) {
                if ($item->getTaskId() == (string) $task_id) {
                    $inv_item_amount = $iiaR->repoInvItemAmountquery(
                                                        (string) $item->getId());
                    if (null !== $inv_item_amount) {
                        $sum += ($inv_item_amount->getTotal() ?? 0.00);
                    }
                }
            }
        }
        return $sum;
    }
}
