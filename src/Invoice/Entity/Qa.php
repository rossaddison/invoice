<?php

declare(strict_types=1);

namespace App\Invoice\Entity;

use App\Invoice\Qa\QaRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

#[Entity(repository: QaRepository::class)]
class Qa
{
    public function __construct(
       #[Column(type: 'primary')]
       private ?int $id = null,
       #[Column(type: 'text', nullable: true)]
       private ?string $question = '',
       #[Column(type: 'text', nullable: true)]
       private ?string $answer = '',
       #[Column(type: 'integer(2)', nullable: true, default: 0)]
       private ?int $sort_order = null,
       #[Column(type: 'tinyInteger(1)', nullable: false, default: 0)]
       private ?int $active = 0,
    ){}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): void
    {
        $this->question = $question;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): void
    {
        $this->answer = $answer;
    }

    public function getSortOrder(): ?int
    {
        return $this->sort_order;
    }

    public function setSortOrder(int $sort_order): void
    {
        $this->sort_order = $sort_order;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function setActive(int $active): void
    {
        $this->active = $active;
    }
}
