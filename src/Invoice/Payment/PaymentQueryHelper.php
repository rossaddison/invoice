<?php

declare(strict_types=1);

namespace App\Invoice\Payment;

use App\Infrastructure\Persistence\Payment\Payment;
use App\Invoice\Merchant\MerchantRepository;
use App\Invoice\PaymentCustom\PaymentCustomRepository;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\SortableDataInterface;
use Yiisoft\Router\CurrentRoute;

final class PaymentQueryHelper
{
    public static function payment(CurrentRoute $currentRoute,
                                    PaymentRepository $payR): ?Payment
    {
        $id = $currentRoute->getArgument('id');
        if (null !== $id) {
            return $payR->repoPaymentquery((int) $id);
        }
        return null;
    }

    /**
     * @return EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    public static function payments(PaymentRepository $payR): EntityReader
    {
        return $payR->findAllPreloaded();
    }

    /**
     * @param PaymentRepository $payR
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    public static function paymentsWithSort(PaymentRepository $payR,
                                    Sort $sort): SortableDataInterface
    {
        return $payR->findAllPreloaded()->withSort($sort);
    }

    /**
     * @param PaymentRepository $payR
     * @param array $client_id_array
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    public static function paymentsWithSortGuest(PaymentRepository $payR,
                    array $client_id_array, Sort $sort): SortableDataInterface
    {
        return $payR->findOneUserManyClientsPayments($client_id_array)
                                      ->withSort($sort);
    }

    /**
     * @return EntityReader
     *
     * @psalm-return \Yiisoft\Data\Cycle\Reader\EntityReader
     */
    public static function merchants(MerchantRepository $merchR): EntityReader
    {
        return $merchR->findAllPreloaded();
    }

    /**
     * @param MerchantRepository $merchR
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    public static function merchantWithSort(MerchantRepository $merchR,
                                    Sort $sort): SortableDataInterface
    {
        return $merchR->findAllPreloaded()->withSort($sort);
    }

    /**
     * @param MerchantRepository $merchR
     * @param array $client_id_array
     * @param Sort $sort
     * @return \Yiisoft\Data\Reader\SortableDataInterface
     */
    public static function merchantWithSortGuest(
        MerchantRepository $merchR,
            array $client_id_array, Sort $sort): SortableDataInterface
    {
        return $merchR->findOneUserManyClientsMerchantResponses($client_id_array)
                                                 ->withSort($sort);
    }

    /**
     * @param int $payment_id
     * @param PaymentCustomRepository $pcR
     * @return array
     */
    public static function paymentCustomValues(int $payment_id,
                                        PaymentCustomRepository $pcR): array
    {
        $custom_field_form_values = [];
        if ($pcR->repoPaymentCount($payment_id) > 0) {
            $payment_custom_fields = $pcR->repoFields($payment_id);
            /**
             * @var string $key
             * @var string $val
             */
            foreach ($payment_custom_fields as $key => $val) {
                $custom_field_form_values['custom[' . $key . ']'] = $val;
            }
        }
        return $custom_field_form_values;
    }
}
