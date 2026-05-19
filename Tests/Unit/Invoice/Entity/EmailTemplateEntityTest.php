<?php

declare(strict_types=1);

namespace Tests\Unit\Invoice\Entity;

use App\Infrastructure\Persistence\EmailTemplate\EmailTemplate;
use PHPUnit\Framework\TestCase;

class EmailTemplateEntityTest extends TestCase
{
    public function testHasIdentityReturnsFalseByDefault(): void
    {
        $et = new EmailTemplate();
        $this->assertFalse($et->hasIdentity());
    }

    public function testReqEmailTemplateIdThrowsWhenNotPersisted(): void
    {
        $et = new EmailTemplate();
        $this->expectException(\LogicException::class);
        $et->reqEmailTemplateId();
    }

    public function testConstructorDefaults(): void
    {
        $et = new EmailTemplate();
        $this->assertSame('', $et->getEmailTemplateTitle());
        $this->assertSame('', $et->getEmailTemplateType());
        $this->assertSame('', $et->getEmailTemplateBody());
        $this->assertSame('', $et->getEmailTemplateSubject());
        $this->assertSame('', $et->getEmailTemplateFromName());
        $this->assertSame('', $et->getEmailTemplateFromEmail());
        $this->assertSame('', $et->getEmailTemplateCc());
        $this->assertSame('', $et->getEmailTemplateBcc());
        $this->assertSame('', $et->getEmailTemplatePdfTemplate());
    }

    public function testSetAndGetTitle(): void
    {
        $et = new EmailTemplate();
        $et->setEmailTemplateTitle('Invoice Notification');
        $this->assertSame('Invoice Notification', $et->getEmailTemplateTitle());
    }

    public function testSetAndGetType(): void
    {
        $et = new EmailTemplate();
        $et->setEmailTemplateType('invoice');
        $this->assertSame('invoice', $et->getEmailTemplateType());
    }

    public function testSetAndGetBody(): void
    {
        $et = new EmailTemplate();
        $et->setEmailTemplateBody('Dear {client_name}, please find attached...');
        $this->assertSame('Dear {client_name}, please find attached...', $et->getEmailTemplateBody());
    }

    public function testSetAndGetSubject(): void
    {
        $et = new EmailTemplate();
        $et->setEmailTemplateSubject('Invoice #{invoice_number}');
        $this->assertSame('Invoice #{invoice_number}', $et->getEmailTemplateSubject());
    }

    public function testSetAndGetFromName(): void
    {
        $et = new EmailTemplate();
        $et->setEmailTemplateFromName('Accounts');
        $this->assertSame('Accounts', $et->getEmailTemplateFromName());
    }

    public function testSetAndGetFromEmail(): void
    {
        $et = new EmailTemplate();
        $et->setEmailTemplateFromEmail('accounts@example.com');
        $this->assertSame('accounts@example.com', $et->getEmailTemplateFromEmail());
    }

    public function testSetAndGetCcAndBcc(): void
    {
        $et = new EmailTemplate();
        $et->setEmailTemplateCc('cc@example.com');
        $et->setEmailTemplateBcc('bcc@example.com');
        $this->assertSame('cc@example.com', $et->getEmailTemplateCc());
        $this->assertSame('bcc@example.com', $et->getEmailTemplateBcc());
    }

    public function testSetAndGetPdfTemplate(): void
    {
        $et = new EmailTemplate();
        $et->setEmailTemplatePdfTemplate('invoice_a4');
        $this->assertSame('invoice_a4', $et->getEmailTemplatePdfTemplate());
    }
}
