<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class PaymentInformationForm extends FormModel
{
    private string $gateway_driver = '';
    private string $creditcard_number = '';
    private string $creditcard_expiry_month = '';
    private string $creditcard_expiry_year = '';
    private string $creditcard_cvv = '';

    /**
     * Note there is no construct here for PaymentInformation i.e. No Entity called PaymentInformation,
     * because the above sensitive data is not to touch the server from the user
     */

    /**
     * @return string
     *
     * @psalm-return 'PaymentInformationForm'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'PaymentInformationForm';
    }

    /**
     * @return Required[][]
     *
     * @psalm-return array{gateway_driver: list{Required}, creditcard_number: list{Required}, creditcard_expiry_month: list{Required}, creditcard_expiry_year: list{Required}, creditcard_cvv: list{Required}}
     */
    public function getRules(): array
    {
        return [
            'gateway_driver' => [new Required()],
            'creditcard_number' => [new Required()],
            'creditcard_expiry_month' => [new Required()],
            'creditcard_expiry_year' => [new Required()],
            'creditcard_cvv' => [new Required()],
        ];
    }
}
