<?php

declare(strict_types=1);

namespace App\Invoice\Inv\Trait;

use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Cycle\Reader\EntityReader;

trait InvFilterTrait
{
    public function filterInvNumber(string $invNumber): EntityReader
    {
        $select = $this->select();
        $query = $select
            ->where(['number' => ltrim(rtrim($invNumber))])
            ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function filterCreditInvNumber(string $creditInvNumber): EntityReader
    {
        $select = $this->select();
        $trimmed = ltrim(rtrim($creditInvNumber));
        $parentInvs = $this->select()
                           ->where('number', 'like', $trimmed . '%')
                           ->where('deleted_at', null)
                           ->fetchAll();
        $parentIds = [];
        foreach ($parentInvs as $parentInv) {
            $parentIds[] = (string) $parentInv->reqId();
        }
        $query = $parentIds === []
            ? $select->where(['id' => '0'])->where('deleted_at', null)
            : $select->where(['creditinvoice_parent_id' =>
                ['in' => new Parameter($parentIds)]])->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function filterFamilyName(string $invFamilyName): EntityReader
    {
        $select = $this->select();
        $query = $select
                ->load('items')
                ->where(['items.product.family.family_name' => $invFamilyName])
                ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function filterInvAmountTotal(float $invAmountTotal): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('invAmount')
                 ->where('invAmount.total', 'like', $invAmountTotal . '%')
                 ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function filterInvAmountPaid(float $invAmountPaid): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('invAmount')
                 ->where('invAmount.paid', 'like', $invAmountPaid . '%')
                 ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function filterInvAmountBalance(float $invAmountBalance): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('invAmount')
                 ->where('invAmount.balance', 'like', $invAmountBalance . '%')
                 ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function filterInvNumberAndInvAmountTotal(
            string $invNumber, float $invAmountTotal): EntityReader
    {
        $select = $this->select();
        $query = $select
                 ->load('invAmount')
                 ->where(['number' => $invNumber])
                 ->andWhere(['invAmount.total' => $invAmountTotal])
                 ->where('deleted_at', null);
        return $this->prepareDataReader($query);
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
                            new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                       ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function filterGuestClientIdNotDraft(int $clientId): EntityReader
    {
        $query = $this->select()
                       ->load(['client'])
                       ->where(['client.id' => $clientId])
                       ->andWhere(['status_id' => ['in' =>
                            new Parameter([2,3,4,5,6,7,8,9,10,11,12,13])]])
                       ->where('deleted_at', null);
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
                       ->where(['client.client_surname' => $secondName])
                       ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function filterClientGroup(string $clientGroup): EntityReader
    {
        $select = $this->select()
                       ->load(['client']);
        $query = $select->where([
            'client.client_group' => ltrim(rtrim($clientGroup))])
            ->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }

    public function filterClientAddress1(string $clientAddress1): EntityReader
    {
        $select = $this->select()
                       ->load(['client']);
        $query = $select->where('client.client_address_1', 'like',
            ltrim(rtrim($clientAddress1)) . '%')
            ->where('deleted_at', null);
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
        )->where('deleted_at', null);
        return $this->prepareDataReader($query);
    }
}
