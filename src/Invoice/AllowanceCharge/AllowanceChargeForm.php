<?php

declare(strict_types=1);

namespace App\Invoice\AllowanceCharge;

use App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Callback;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

final class AllowanceChargeForm extends FormModel implements RulesProviderInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Required]
    private ?bool $identifier = false;

    #[Required]
    private int $level = 0;

    /**
     * Related logic: see Not required because determined from value 'reason' in array using AllowanceChargeContoller
     */
    private ?string $reason_code = '';

    #[Required]
    private ?string $reason = '';

    #[Required]
    private ?int $multiplier_factor_numeric = null;

    #[Required]
    private ?int $amount = null;

    #[Required]
    private ?int $base_amount = null;

    #[Integer(min: 1)]
    private ?int $tax_rate_id = null;

    public static function show(AllowanceCharge $allowanceCharge, TranslatorInterface $translator) : self
    {
        $form = new self($translator);
        $form->identifier = $allowanceCharge->getIdentifier();
        $form->level = $allowanceCharge->getLevel();
        $form->reason_code = $allowanceCharge->getReasonCode();
        $form->reason = $allowanceCharge->getReason();
        $form->multiplier_factor_numeric = $allowanceCharge->getMultiplierFactorNumeric();
        $form->amount = $allowanceCharge->getAmount();
        $form->base_amount = $allowanceCharge->getBaseAmount();
        $form->tax_rate_id = $allowanceCharge->getTaxRateId();
        return $form;
    }

    #[\Override]
    public function getRules(): iterable
    {
        return [
            'amount' => [
                new Callback(
                    callback: function (): Result {
                        $result = new Result();
                        $mfn    = $this->multiplier_factor_numeric ?? 0;
                        $amount = $this->amount ?? 0;
                        if ($mfn > 0) {
                            $base = $this->base_amount ?? 0;
                            if ($base > 0) {
                                $expected = intdiv($mfn * $base, 100);
                                if ($amount !== $expected) {
                                    $result->addErrorWithoutPostProcessing(
                                        sprintf('%d × %d ÷ 100 = %d', $mfn, $base, $expected)
                                    );
                                }
                            }
                        } elseif ($amount <= 0) {
                            $result->addError(
                                $this->translator->translate(
                                'allowance.or.charge.amount.fixed.must.be.positive')
                            );
                        }
                        return $result;
                    },
                    skipOnEmpty: true,
                ),
            ],
            'base_amount' => [
                new Callback(
                    callback: function (): Result {
                        $result = new Result();
                        if (($this->multiplier_factor_numeric ?? 0) > 0
                            && ($this->base_amount ?? 0) <= 0
                        ) {
                            $result->addError(
                                $this->translator->translate(
                                'allowance.or.charge.base.amount.required.when.mfn.set')
                            );
                        }
                        return $result;
                    },
                    skipOnEmpty: true,
                ),
            ],
        ];
    }

    public function getIdentifier(): ?bool
    {
        return $this->identifier;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getReasonCode(): ?string
    {
        return $this->reason_code;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getMultiplierFactorNumeric(): ?int
    {
        return $this->multiplier_factor_numeric;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getBaseAmount(): ?int
    {
        return $this->base_amount;
    }

    public function getTaxRateId(): ?int
    {
        return $this->tax_rate_id;
    }

    /**
     * @return string
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
