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
    private EntityWriter $entityWriter;
    private Translator $translator;

    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     * @param Translator $translator
     */
    public function __construct(Select $select, EntityWriter $entityWriter, Translator $translator)
    {
        $this->entityWriter = $entityWriter;
        $this->translator = $translator;
        parent::__construct($select);
    }
    
    public function filterInvNumber(string $invNumber): EntityReader
    {
        $select = $this->select();
        $query = $select->where(['number' => ltrim(rtrim($invNumber))]);        
        return $this->prepareDataReader($query); 
    }

    public function filterInvAmountTotal(string $invAmountTotal): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('invAmount') 
                 ->where('invAmount.total' , 'like', $invAmountTotal.'%');
        return $this->prepareDataReader($query); 
    }
    
     public function filterInvNumberAndInvAmountTotal(string $invNumber, float $invAmountTotal): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('invAmount')
                 ->where(['number' => $invNumber])
                 ->andWhere(['invAmount.total' => $invAmountTotal]);
        return $this->prepareDataReader($query); 
    }       
    
    /**
     * 
     * @param int $status_id
     * @return EntityReader
     */
    public function findAllWithStatus(int $status_id) : EntityReader
    {
        if (($status_id) > 0) {
        $query = $this->select()
                ->load(['client','group','user'])
                ->where(['status_id' => $status_id]);  
         return $this->prepareDataReader($query);
       } else {
         return $this->findAllPreloaded();  
       }       
    }
    
    public function filterClient(string $client_full_name): EntityReader
    {
        $query = $this->select()
                       ->load(['client'])
                       ->where(['client.client_full_name' => $client_full_name]);
        return $this->prepareDataReader($query); 
    }
    
    public function filterClientGroup(string $clientGroup): EntityReader
    {
        $select = $this->select()
                       ->load(['client']);
        $query = $select->where(['client.client_group' => ltrim(rtrim($clientGroup))]);
        return $this->prepareDataReader($query); 
    }
    
    public function filterDateCreatedLike(string $format, string $dateCreated) : EntityReader
    {
        $select = $this->select();
        $dateTimeImmutable = \DateTimeImmutable::createFromFormat($format, $dateCreated);
        $query = $select->where('date_created', 
                                'like', 
                                ($dateTimeImmutable instanceof \DateTimeImmutable ? 
                                $dateTimeImmutable->format('Y-m').'%' : ''));
        return $this->prepareDataReader($query);
    }    
    
    /**
     * 
     * @param int $client_id
     * @return EntityReader
     */
    public function findAllWithClient(int $client_id) : EntityReader
    {
        $query = $this->select()
                ->where(['client_id' => $client_id]);  
        return $this->prepareDataReader($query);
    }
    
    /**
     * @param int $contract_id
     * @return EntityReader
     */
    public function findAllWithContract(int $contract_id) : EntityReader
    {
        $query = $this->select()
                      ->where(['contract_id' => $contract_id]);  
        return $this->prepareDataReader($query);
    }
    
    /**
     * @param int $delivery_location_id
     * @return EntityReader
     */
    public function findAllWithDeliveryLocation(int $delivery_location_id) : EntityReader
    {
        $query = $this->select()
                      ->where(['delivery_location_id' => $delivery_location_id]);  
        return $this->prepareDataReader($query);
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
        // Provide the latest invoice at the top of the list and order additionally according to status
        return Sort::only(['id', 'status'])->withOrder(['id' => 'desc', 'status' => 'asc']);
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
     * 
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'desc'])
        );
    }
    
    /**
     * 
     * @param string $id
     * @return int
     */
    public function repoCount(string $id) : int {
        $count = $this->select()
                ->where(['id' => $id]) 
                ->count();
        return $count;
    }
    
    /**
     * 
     * @return int
     */
    public function repoCountAll() : int {
        $count = $this->select() 
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param int $invoice_id
     * @param int $status_id
     * @return Inv|null
     */
    public function repoInvStatusquery(int $invoice_id, int $status_id) : Inv|null {
        $query = $this->select()
                      ->where(['id' => $invoice_id])
                      ->where(['status_id'=>$status_id]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $id
     * @return Inv|null
     */
    public function repoInvUnLoadedquery(string $id): Inv|null {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }
    
    public function repoInvLoadInvAmountquery(string $id): Inv|null {
        $query = $this->select()
                      ->load('invamount')  
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }
    
    /**
     * 
     * @param string $id
     * @return Inv|null
     */
    public function repoInvLoadedquery(string $id): Inv|null {
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
    public function repoUrl_key_guest_loaded(string $url_key) : Inv|null {
        $query = $this->select()
                       ->load('client') 
                       ->where(['url_key' => $url_key])
                       ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4])]]);
        return  $query->fetchOne() ?: null;        
    }
    
    /**
     * 
     * @param string $url_key
     * @return int
     */
    public function repoUrl_key_guest_count(string $url_key) : int {
        $count = $this->select()
                      ->where(['url_key' => $url_key])
                      ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                      ->count();
        return  $count;        
    }
    
    /**
     * @psalm-return Select<TEntity>
     */
    public function repoClient_guest_count(int $inv_id, array $user_client = []) : Select {
        $count = $this->select()
                      ->where(['id' => $inv_id])
                      // sent = 2, viewed = 3, paid = 4
                      ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                      ->andWhere(['client_id'=>['in'=> new Parameter($user_client)]]);
        return  $count;        
    }
    
    /**
     * @psalm-return EntityReader
     * @param int $status_id
     * @param array $user_client
     */
    public function repoGuest_Clients_Post_Draft
            (int $status_id, array $user_client = []) : EntityReader {
        // sent = 2, viewed = 3, paid = 4, overdue = 5, unpaid = 6, reminder sent = 7, 
        // 7 day letter before action = 8, started a legal claim = 9
        // judgement obtained = 10, enforcement officer attending address = 11, credit note = 12, written off = 13
        if ($status_id > 0) {
            $query = $this->select()
                    ->where(['status_id'=>$status_id])
                    ->where(['client_id'=>['in'=> new Parameter($user_client)]])                      
                    ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]]);
            return $this->prepareDataReader($query);
       } else
       // Get all the invoices
       {
            $query = $this->select()                    
                    ->where(['client_id'=>['in'=> new Parameter($user_client)]])                      
                    ->andWhere(['status_id'=>['in'=> new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]]);
            return $this->prepareDataReader($query);
       }
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function open() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([2,3])]]);
        return $this->prepareDataReader($query);    
    }
    
     public function open_count() : int {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        // 2,3 => There is still a balance available => Not paid
        $count = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([2,3])]])
                      ->count();
        return $count;    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function guest_visible() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function is_draft() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([1])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function is_sent() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([2])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function is_viewed() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([3])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function is_paid() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([4])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @psalm-return EntityReader
     */
    public function is_overdue() : EntityReader {
        // 1 draft, 2 sent, 3 viewed, 4 paid
        $query = $this->select()
                      ->where(['status_id'=>['in'=> new Parameter([5])]]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * 
     * @param int $client_id
     * @return EntityReader
     */
    public function by_client(int $client_id) : EntityReader {
        $query = $this->select()
                      ->where(['client_id'=> $client_id]);
        return $this->prepareDataReader($query);    
    }
    
    /**
     * @param $client_id
     * @param $status_id
     *
     * @psalm-return EntityReader
     */
    public function by_client_inv_status(int $client_id, int $status_id): EntityReader
    {
        $query = $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id]);
        return $this->prepareDataReader($query);
    }
    
    /**
     * 
     * @param int $client_id
     * @param int $status_id
     * @return int
     */
    public function by_client_inv_status_count(int $client_id, int $status_id): int
    {
        $count = $this->select()
                      ->where(['client_id' => $client_id])
                      ->andWhere(['status_id' => $status_id])
                      ->count();        
        return $count; 
    }
    
    /**
     * @param Translator $translator
     * @return array
     */
    public function getStatuses(Translator $translator): array
    {
        return array(
            '0' => array(
                'label' => $translator->translate('i.all'),
                'class' => 'default',
                'href' => 0,
                'emoji' => '🌎 '
            ),
            '1' => array(
                'label' => $translator->translate('i.draft'),
                'class' => 'default',
                'href' => 1,
                'emoji' => '🗋 '
            ),
            '2' => array(
                'label' => $translator->translate('i.sent'),
                'class' => 'info',
                'href' => 2,
                'emoji' => '📨 '
            ),
            '3' => array(
                'label' => $translator->translate('i.viewed'),
                'class' => 'info',
                'href' => 3,
                'emoji' => '👀 '
            ),
            '4' => array(
                'label' => $translator->translate('i.paid'),
                'class' => 'success',
                'href' => 4,
                'emoji' => '😀 '
            ),
            '5' => array(
                'label' => $translator->translate('i.overdue'),
                'class' => 'warning',
                'href' => 5,
                'emoji' => '🏦 '
            ),
            '6' => array(
                'label' => $translator->translate('i.unpaid'),
                'class' => 'danger',
                'href' => 6,
                'emoji' => '💸 '
            ),
            '7' => array(
                'label' => $translator->translate('i.reminder'),
                'class' => 'info',
                'href' => 7,
                'emoji' => '🔔 '
            ),
            '8' => array(
                'label' => $translator->translate('i.letter'),
                'class' => 'danger',
                'href' => 8,
                'emoji' => '🗎 '
            ),
            '9' => array(
                'label' => $translator->translate('i.claim'),
                'class' => 'info',
                'href' => 9,
                'emoji' => '🛄 '
            ),
            '10' => array(
                'label' => $translator->translate('i.judgement'),
                'class' => 'success',
                'href' => 10,
                'emoji' => '🙌 '
            ),
            '11' => array(
                'label' => $translator->translate('i.enforcement'),
                'class' => 'primary',
                'href' => 11,
                'emoji' => '👮 '
            ),            
            '12' => array(
                'label' => $translator->translate('i.credit_invoice_for_invoice'),
                'class' => 'default',
                'href' => 12,
                'emoji' => '🛑️ '
            ),
            '13' => array(
                'label' => $translator->translate('i.loss'),
                'class' => 'danger',
                'href' => 13,
                'emoji' => '❎ '
            ),
        );       
    }
    
    /**
     * 
     * @param string $key
     * @return string
     */
    public function getSpecificStatusArrayLabel(string $key) : string
    {
        $statuses_array = $this->getStatuses($this->translator);
        /**
         * @var array $statuses_array[$key]
         * @var string $statuses_array[$key]['label']
         */
        return $statuses_array[$key]['label'];
    }
    
    /**
     * @param string $invoice_date_created
     * @param SR $sR
     * @return string
     */
    public function get_date_due(string $invoice_date_created, SR $sR) : string
    {
        $invoice_date_due = new \DateTime($invoice_date_created);
        $invoice_date_due->add(new \DateInterval('P' . $sR->get_setting('invoices_due_after') . 'D'));
        return $invoice_date_due->format('Y-m-d');
    }
    
    /**
     * @return string
     */
    public function get_url_key()
    {
        $random = new Random();
        return $random::string(32);
    }
    
    /**
     * @param string $group_id
     * @return mixed
     */
    public function get_inv_number(string $group_id, GR $gR) : mixed
    {   
        return $gR->generate_number((int) $group_id);
    }
    
    // total = item_subtotal + item_tax_total + tax_total
    // total => sales including item tax and tax
    /**
     * 
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function with_total(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getTotal() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    // sales without item tax and tax => item_subtotal
    /**
     * 
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function with_item_subtotal(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery((int)$invoice->getId());            
            if (null!==$invoice_amount) {
               $sum += $invoice_amount->getItem_subtotal() ?: 0.00;
            }   
        }
        return $sum;
    }
    
    // total = item_subtotal + item_tax_total + tax_total
    // total => sales including item tax and tax
    /**
     * 
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function with_total_from_to(int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {  
            $invoice_amount = $iaR->repoInvquery((int)$invoice->getId());            
            if (null!==$invoice_amount) {
               $sum += $invoice_amount->getTotal() ?? 0.00;
            }   
        }
        return $sum;
    }
    
    // sales without item tax and tax => item_subtotal
    /**
     * 
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function with_item_subtotal_from_to(int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery((int)$invoice->getId());            
            if (null!==$invoice_amount) { 
              $sum += $invoice_amount->getItem_subtotal();
            } 
        }
        return $sum;
    }
    
    // First tax: Item tax total 
    /**
     * 
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function with_item_tax_total_from_to(int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = $iaR->repoInvquery((int)$invoice->getId());            
            if (null!==$invoice_amount) {
              $sum += $invoice_amount->getItem_tax_total();
            } 
        }
        return $sum;
    }
    
    // Second tax: Total tax total 
    public function with_tax_total_from_to(int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getTax_total() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    /**
     * 
     * @param int $client_id
     * @param string $from
     * @param string $to
     * @param IAR $iaR
     * @return float
     */
    public function with_paid_from_to(int $client_id, string $from, string $to, IAR $iaR): float
    {
        $invoices = $this->repoClientLoadedFromToDate($client_id, $from, $to);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null);            
            $sum += (null!==$invoice_amount ? $invoice_amount->getPaid() ?? 0.00 : 0.00);            
        }
        return $sum;
    }
    
    /**
     * 
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function with_total_paid(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null); 
            $sum += (null!==$invoice_amount ? $invoice_amount->getPaid() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    /**
     * @param int $client_id
     * @param IAR $iaR
     * @return int
     */
    public function with_total_balance_invoices(int $client_id, IAR $iaR): int
    {
        $invoices = $this->findAllWithClient($client_id);
        $num_invoices = 0;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) {
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null); 
            $num_invoices += (null!==$invoice_amount && null!==$invoice_amount->getBalance() ? 1 : 0);
        }
        return $num_invoices;
    }

    /**
     * 
     * @param int $client_id
     * @param IAR $iaR
     * @return float
     */
    public function with_total_balance(int $client_id, IAR $iaR): float
    {
        $invoices = $this->findAllWithClient($client_id);
        $sum = 0.00;
        /**
         * @var Inv $invoice
         */
        foreach ($invoices as $invoice) { 
            $invoice_amount = ($iaR->repoInvAmountCount((int)$invoice->getId())> 0 ? $iaR->repoInvquery((int)$invoice->getId()) : null); 
            $sum += (null!==$invoice_amount ? $invoice_amount->getBalance() ?? 0.00 : 0.00);
        }
        return $sum;
    }
    
    /**
     * 
     * @param int|null $client_id
     * @return int
     */
    public function repoCountByClient(int|null $client_id) : int {
        $count = $this->select()
                      ->where(['client_id'=>$client_id])  
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param int $client_id
     * @param string $from_date
     * @param string $to_date
     * @return int
     */
    public function repoCountClientLoadedFromToDate(int $client_id, string $from_date, string $to_date) : int {
        $count = $this->select()
                      ->load('client')
                      ->where(['client_id'=>$client_id])
                      ->andWhere('date_created','>=',$from_date)
                      ->andWhere('date_created','<=',$to_date)
                      ->count();
        return $count;
    }
    
    /**
     * 
     * @param int $client_id
     * @param string $from_date
     * @param string $to_date
     * @return EntityReader
     */
    public function repoClientLoadedFromToDate(int $client_id, string $from_date, string $to_date) : EntityReader {
        $query = $this->select()
                      ->load('client')  
                      ->where(['client_id'=>$client_id])
                      ->andWhere('date_created','>=',$from_date)
                      ->andWhere('date_created','<=',$to_date);
        return $this->prepareDataReader($query);
    }
    
    /**
     * 
     * @param int|null $client_id
     * @return EntityReader
     */
    public function repoClient(int|null $client_id) : EntityReader { 
        $query = $this->select()
                      ->where(['client_id' => $client_id]); 
        return $this->prepareDataReader($query);
    }
    
    /**
     * @param int|null $product_id
     * @return int
     */
    public function repoCountByProduct(int|null $product_id) : int {
        $count = $this->select()
                      ->distinct()
                      ->with('items')
                      ->where('items.product_id',$product_id)  
                      ->count();
        return $count;
    }
    
       
    /**
     * @param int $product_id
     * @param string $from_date
     * @param string $to_date
     * @return EntityReader
     */
    public function repoProductWithInvItemsFromToDate(int $product_id, string $from_date, string $to_date) : EntityReader {
        $query = $this->select()
                      ->distinct()
                      ->with('items')  
                      ->where('items.product_id',$product_id)
                      ->andWhere('date_created','>=',$from_date)
                      ->andWhere('date_created','<=',$to_date);
        return $this->prepareDataReader($query);
    }
    
    /**
     * @param int $product_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function with_item_subtotal_from_to_using_product(int $product_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoProductWithInvItemsFromToDate($product_id, $from, $to);
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
              if ($item->getProduct_id() == (string)$product_id) {
                $inv_item_amount = $iiaR->repoInvItemAmountquery((string)$item->getId());
                if (null!==$inv_item_amount) {
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
    public function with_item_tax_total_from_to_using_product(int $product_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoProductWithInvItemsFromToDate($product_id, $from, $to);
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
              if ($item->getProduct_id() == (string)$product_id) {
                $inv_item_amount = $iiaR->repoInvItemAmountquery((string)$item->getId());
                if (null!==$inv_item_amount) {
                  $sum += ($inv_item_amount->getTax_total() ?? 0.00);
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
    public function with_item_total_from_to_using_product(int $product_id, string $from, string $to, IIAR $iiaR): float
    {
        $invoices = $this->repoProductWithInvItemsFromToDate($product_id, $from, $to);
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
              if ($item->getProduct_id() == (string)$product_id) {
                $inv_item_amount = $iiaR->repoInvItemAmountquery((string)$item->getId());
                if (null!==$inv_item_amount) {
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
    public function repoCountByTask(int|null $task_id) : int {
        $count = $this->select()
                      ->distinct()
                      ->with('items')
                      ->where('items.task_id',$task_id)  
                      ->count();
        return $count;
    }
    
       
    /**
     * @param int $task_id
     * @param string $from_date
     * @param string $to_date
     * @return EntityReader
     */
    public function repoTaskWithInvItemsFromToDate(int $task_id, string $from_date, string $to_date) : EntityReader {
        $query = $this->select()
                      ->distinct()
                      ->with('items')  
                      ->where('items.task_id',$task_id)
                      ->andWhere('date_created','>=',$from_date)
                      ->andWhere('date_created','<=',$to_date);
        return $this->prepareDataReader($query);
    }
    
    /**
     * @param int $task_id
     * @param string $from
     * @param string $to
     * @param IIAR $iiaR
     * @return float
     */
    public function with_item_subtotal_from_to_using_task(int $task_id, string $from, string $to, IIAR $iiaR): float
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
              if ($item->getTask_id() == (string)$task_id) {
                $inv_item_amount = $iiaR->repoInvItemAmountquery((string)$item->getId());
                if (null!==$inv_item_amount) {
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
    public function with_item_tax_total_from_to_using_task(int $task_id, string $from, string $to, IIAR $iiaR): float
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
              if ($item->getTask_id() == (string)$task_id) {
                $inv_item_amount = $iiaR->repoInvItemAmountquery((string)$item->getId());
                if (null!==$inv_item_amount) {
                  $sum += ($inv_item_amount->getTax_total() ?? 0.00);
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
    public function with_item_total_from_to_using_task(int $task_id, string $from, string $to, IIAR $iiaR): float
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
              if ($item->getTask_id() == (string)$task_id) {
                $inv_item_amount = $iiaR->repoInvItemAmountquery((string)$item->getId());
                if (null!==$inv_item_amount) {
                  $sum += ($inv_item_amount->getTotal() ?? 0.00);
                }  
              }
            }
        }
        return $sum;
    }
}