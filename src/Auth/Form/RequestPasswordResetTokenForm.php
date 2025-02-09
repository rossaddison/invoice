<?php

declare(strict_types=1);

namespace App\Auth\Form;

use App\User\UserRepository;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\PropertyTranslator\ArrayPropertyTranslator;
use Yiisoft\Validator\PropertyTranslatorInterface;
use Yiisoft\Validator\PropertyTranslatorProviderInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

final class RequestPasswordResetTokenForm extends FormModel implements RulesProviderInterface, PropertyTranslatorProviderInterface
{
    public string $email = '';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * @return string[]
     *
     * @psalm-return array{email: string}
     */
    public function getAttributeLabels(): array
    {
        return [
            'email' => $this->translator->translate('i.email'),
        ];
    }

    /**
     * @return string
     *
     * @psalm-return 'RequestPasswordResetToken'
     */
    public function getFormName(): string
    {
        return 'RequestPasswordResetToken';
    }

    /**
     * @return PropertyTranslatorInterface|null
     */
    public function getPropertyTranslator(): ?PropertyTranslatorInterface
    {
        return new ArrayPropertyTranslator($this->getPropertyLabels());
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return (\Closure|Email|Required)[][]
     *
     * @psalm-return array{email: list{Required, Email, \Closure(mixed):Result}}
     */
    public function getRules(): array
    {
        return [
            'email' => [
                new Required(),
                new Email(),
                function (mixed $value): Result {
                    $result = new Result();
                    if ($this->userRepository->findByEmail((string)$value) === null) {
                        $result->addError($this->translator->translate('validator.user.exist.not'));
                    }
                    return $result;
                },
            ],
        ];
    }
}
