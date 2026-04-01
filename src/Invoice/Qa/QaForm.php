<?php

declare(strict_types=1);

namespace App\Invoice\Qa;

use App\Invoice\Entity\Qa;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;

final class QaForm extends FormModel
{
    private ?int $id = null;

    #[Integer(min: 0, max: 1)]
    private ?int $active = 0;

    #[Length(min: 0, max: 191, skipOnEmpty: true)]
    private ?string $question = '';

    #[Length(min: 0, max: 191, skipOnEmpty: true)]
    private ?string $answer = '';

    private ?string $sort_order = null;
    
    public function __construct(Qa $qa)
    {
        $this->id = $qa->getId();
        $this->active = $qa->getActive();
        $this->question = $qa->getQuestion();
        $this->answer = $qa->getAnswer();
        $this->sort_order = (string) $qa->getSortOrder();
    }

    public function getId(): ?int
    {
        return $this->id;
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
