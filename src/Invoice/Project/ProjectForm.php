<?php

declare(strict_types=1);

namespace App\Invoice\Project;

use App\Invoice\Entity\Project;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Rule\Required;

final class ProjectForm extends FormModel
{
    #[Required]
    private ?int $client_id = null;

    #[Required]
    private ?string $name = '';

    public function __construct(Project $project)
    {
        $this->client_id = (int) $project->getClient_id();
        $this->name      = $project->getName();
    }

    public function getClient_id(): ?int
    {
        return $this->client_id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @psalm-return ''
     */
    #[\Override]
    public function getFormName(): string
    {
        return '';
    }
}
