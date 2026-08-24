<?php

declare(strict_types=1);

namespace App\Contact;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Input\Http\Attribute\Parameter\UploadedFiles;
use Yiisoft\Validator\PropertyTranslator\ArrayPropertyTranslator;
use Yiisoft\Validator\PropertyTranslatorInterface;
use Yiisoft\Validator\PropertyTranslatorProviderInterface;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

final class ContactForm extends FormModel implements RulesProviderInterface, PropertyTranslatorProviderInterface
{
    private string $name = '';
    private string $email = '';
    private string $subject = '';
    private string $body = '';

    #[UploadedFiles('ContactForm.attachFiles')]
    private array $attachFiles = [];

    /**
     * Pre-fills subject/body for a deep link into this form — e.g. the
     * storefront's "Request a Trade Quote" button
     * (resources/views/shop/catalog/view.php), which sends the customer
     * here via GET with the product's trade terms already summarized, so
     * they only need to fill in name/email. Never called from POST
     * handling — App\Contact\ContactController::interest() only reads
     * these query params on the initial GET.
     */
    public function prefill(string $subject, string $body): void
    {
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * @return string[]
     *
     * @psalm-return array{name: 'Name', email: 'Email', subject: 'Subject', body: 'Body'}
     */
    #[\Override]
    public function getPropertyLabels(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'subject' => 'Subject',
            'body' => 'Body',
        ];
    }

    /**
     * @return string
     *
     * @psalm-return 'ContactForm'
     */
    #[\Override]
    public function getFormName(): string
    {
        return 'ContactForm';
    }

    /**
     * @return (Email|Required)[][]
     *
     * @psalm-return array{name: list{Required}, email: list{Required, Email}, subject: list{Required}, body: list{Required}}
     */
    #[\Override]
    public function getRules(): array
    {
        return [
            'name' => [new Required()],
            'email' => [new Required(), new Email()],
            'subject' => [new Required()],
            'body' => [new Required()],
        ];
    }

    #[\Override]
    public function getPropertyTranslator(): ?PropertyTranslatorInterface
    {
        return new ArrayPropertyTranslator($this->getPropertyLabels());
    }
}
