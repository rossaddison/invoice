<?php

declare(strict_types=1);

namespace App\Invoice\Qa;

use App\Infrastructure\Persistence\Qa\Qa;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;

final class QaForm extends FormModel
{
    #[Integer(min: 0, max: 1)]
    private ?int $active = 0;

    #[Length(min: 0, max: 191, skipOnEmpty: true)]
    private ?string $question = '';

    #[Length(min: 0, max: 191, skipOnEmpty: true)]
    private ?string $answer = '';

    private ?string $sort_order = null;

    public static function show(Qa $qa): self
    {
        $form = new self();
        $form->active = $qa->getActive();
        $form->question = $qa->getQuestion();
        $form->answer = $qa->getAnswer();
        $form->sort_order = (string) $qa->getSortOrder();
        return $form;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function getSortOrder(): ?string
    {
        return $this->sort_order;
    }

    /**
     * @return string
     *
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
