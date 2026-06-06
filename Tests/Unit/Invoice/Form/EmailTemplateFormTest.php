<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Form;

use App\Infrastructure\Persistence\EmailTemplate\EmailTemplate;
use App\Invoice\EmailTemplate\EmailTemplateForm;
use PHPUnit\Framework\TestCase;

class EmailTemplateFormTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $form = new EmailTemplateForm();

        $this->assertNull($form->getEmailTemplateTitle());
        $this->assertNull($form->getEmailTemplateType());
        $this->assertNull($form->getEmailTemplateBody());
        $this->assertNull($form->getEmailTemplateSubject());
        $this->assertNull($form->getEmailTemplateFromName());
        $this->assertNull($form->getEmailTemplateFromEmail());
        $this->assertNull($form->getEmailTemplateCc());
        $this->assertNull($form->getEmailTemplateBcc());
        $this->assertNull($form->getEmailTemplatePdfTemplate());
        $this->assertSame('', $form->getFormName());
    }

    public function testGetFormNameReturnsEmptyString(): void
    {
        $this->assertSame('', (new EmailTemplateForm())->getFormName());
    }

    public function testShowPopulatesAllFields(): void
    {
        $entity = new EmailTemplate();
        $entity->setEmailTemplateTitle('Invoice Sent');
        $entity->setEmailTemplateType('invoice');
        $entity->setEmailTemplateBody('Dear {{{client_name}}}, ...');
        $entity->setEmailTemplateSubject('Invoice #{{{invoice_number}}}');
        $entity->setEmailTemplateFromName('Accounts');
        $entity->setEmailTemplateFromEmail('accounts@example.com');
        $entity->setEmailTemplateCc('cc@example.com');
        $entity->setEmailTemplateBcc('bcc@example.com');
        $entity->setEmailTemplatePdfTemplate('invoice');

        $form = EmailTemplateForm::show($entity);

        $this->assertSame('Invoice Sent', $form->getEmailTemplateTitle());
        $this->assertSame('invoice', $form->getEmailTemplateType());
        $this->assertSame('Dear {{{client_name}}}, ...', $form->getEmailTemplateBody());
        $this->assertSame('Invoice #{{{invoice_number}}}', $form->getEmailTemplateSubject());
        $this->assertSame('Accounts', $form->getEmailTemplateFromName());
        $this->assertSame('accounts@example.com', $form->getEmailTemplateFromEmail());
        $this->assertSame('cc@example.com', $form->getEmailTemplateCc());
        $this->assertSame('bcc@example.com', $form->getEmailTemplateBcc());
        $this->assertSame('invoice', $form->getEmailTemplatePdfTemplate());
    }

    public function testShowWithUnsetFieldsDefaultToEmptyString(): void
    {
        $entity = new EmailTemplate();
        $entity->setEmailTemplateBody('Body only');

        $form = EmailTemplateForm::show($entity);

        // Entity defaults nullable fields to '' (not null), so show() copies ''
        $this->assertSame('', $form->getEmailTemplateTitle());
        $this->assertSame('', $form->getEmailTemplateCc());
        $this->assertSame('', $form->getEmailTemplateBcc());
        $this->assertSame('Body only', $form->getEmailTemplateBody());
    }

    public function testShowReturnsNewInstance(): void
    {
        $entity = new EmailTemplate();
        $entity->setEmailTemplateBody('Body');

        $this->assertNotSame(
            EmailTemplateForm::show($entity),
            EmailTemplateForm::show($entity)
        );
    }
}
