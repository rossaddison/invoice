<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\EmailTemplate;
use Codeception\Test\Unit;

final class EmailTemplateEntityTest extends Unit
{
    public string $invoiceTemplate = 'Invoice Template';
    
    public string $quoteTemplate = 'Quote Template';
    
    public string $testExampleCom = 'test@example.com';
    
    public string $ccTestCom = 'cc@test.com';
    
    public string $bccTestCom = 'bcc@test.com';
    
    public function testConstructorWithDefaults(): void
    {
        $emailTemplate = new EmailTemplate();
        
        $this->assertNull($emailTemplate->getEmailTemplateId());
        $this->assertSame('', $emailTemplate->getEmailTemplateTitle());
        $this->assertSame('', $emailTemplate->getEmailTemplateType());
        $this->assertSame('', $emailTemplate->getEmailTemplateBody());
        $this->assertSame('', $emailTemplate->getEmailTemplateSubject());
        $this->assertSame('', $emailTemplate->getEmailTemplateFromName());
        $this->assertSame('', $emailTemplate->getEmailTemplateFromEmail());
        $this->assertSame('', $emailTemplate->getEmailTemplateCc());
        $this->assertSame('', $emailTemplate->getEmailTemplateBcc());
        $this->assertSame('', $emailTemplate->getEmailTemplatePdfTemplate());
    }

    public function testConstructorWithAllParameters(): void
    {
        $emailTemplate = new EmailTemplate(
            $this->invoiceTemplate,
            'invoice',
            '<p>Your invoice is ready</p>',
            'Invoice #123 from Company',
            'Billing Department',
            'billing@company.com',
            'manager@company.com',
            'archive@company.com',
            'invoice_template.pdf'
        );
        
        $this->assertNull($emailTemplate->getEmailTemplateId());
        $this->assertSame($this->invoiceTemplate, $emailTemplate->getEmailTemplateTitle());
        $this->assertSame('invoice', $emailTemplate->getEmailTemplateType());
        $this->assertSame('<p>Your invoice is ready</p>', $emailTemplate->getEmailTemplateBody());
        $this->assertSame('Invoice #123 from Company', $emailTemplate->getEmailTemplateSubject());
        $this->assertSame('Billing Department', $emailTemplate->getEmailTemplateFromName());
        $this->assertSame('billing@company.com', $emailTemplate->getEmailTemplateFromEmail());
        $this->assertSame('manager@company.com', $emailTemplate->getEmailTemplateCc());
        $this->assertSame('archive@company.com', $emailTemplate->getEmailTemplateBcc());
        $this->assertSame('invoice_template.pdf', $emailTemplate->getEmailTemplatePdfTemplate());
    }

    public function testIdGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        
        $this->assertNull($emailTemplate->getEmailTemplateId());
    }

    public function testTitleSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateTitle($this->quoteTemplate);
        
        $this->assertSame($this->quoteTemplate, $emailTemplate->getEmailTemplateTitle());
    }

    public function testTypeSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateType('quote');
        
        $this->assertSame('quote', $emailTemplate->getEmailTemplateType());
    }

    public function testBodySetterAndGetter(): void
    {
        $htmlBody = '<html><body><h1>Thank you!</h1><p>Your quote is attached.</p></body></html>';
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateBody($htmlBody);
        
        $this->assertSame($htmlBody, $emailTemplate->getEmailTemplateBody());
    }

    public function testSubjectSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateSubject('Your Quote Request');
        
        $this->assertSame('Your Quote Request', $emailTemplate->getEmailTemplateSubject());
    }

    public function testFromNameSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateFromName('Sales Team');
        
        $this->assertSame('Sales Team', $emailTemplate->getEmailTemplateFromName());
    }

    public function testFromEmailSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateFromEmail('sales@company.com');
        
        $this->assertSame('sales@company.com', $emailTemplate->getEmailTemplateFromEmail());
    }

    public function testCcSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateCc('supervisor@company.com');
        
        $this->assertSame('supervisor@company.com', $emailTemplate->getEmailTemplateCc());
    }

    public function testBccSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateBcc('records@company.com');
        
        $this->assertSame('records@company.com', $emailTemplate->getEmailTemplateBcc());
    }

    public function testPdfTemplateSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplatePdfTemplate('quote_template.pdf');
        
        $this->assertSame('quote_template.pdf', $emailTemplate->getEmailTemplatePdfTemplate());
    }

    public function testCommonEmailTemplateTypes(): void
    {
        $invoiceTemplate = new EmailTemplate($this->invoiceTemplate, 'invoice', 'Invoice body', 'Invoice subject', 'Billing', 'billing@test.com', '', '', 'invoice.pdf');
        $this->assertSame('invoice', $invoiceTemplate->getEmailTemplateType());
        $this->assertSame($this->invoiceTemplate, $invoiceTemplate->getEmailTemplateTitle());

        $quoteTemplate = new EmailTemplate($this->quoteTemplate, 'quote', 'Quote body', 'Quote subject', 'Sales', 'sales@test.com', '', '', 'quote.pdf');
        $this->assertSame('quote', $quoteTemplate->getEmailTemplateType());
        $this->assertSame($this->quoteTemplate, $quoteTemplate->getEmailTemplateTitle());

        $reminderTemplate = new EmailTemplate('Reminder Template', 'reminder', 'Reminder body', 'Payment reminder', 'Accounts', 'accounts@test.com', '', '', '');
        $this->assertSame('reminder', $reminderTemplate->getEmailTemplateType());
        $this->assertSame('Reminder Template', $reminderTemplate->getEmailTemplateTitle());
    }

    public function testLongEmailTemplateContent(): void
    {
        $longBody = str_repeat('<p>This is a very long email template body with lots of content. </p>', 20);
        $longSubject = 'Very Long Email Subject That Could Potentially Exceed Normal Database Limits And Still Be Valid';
        
        $emailTemplate = new EmailTemplate('Long Template', 'long', $longBody, $longSubject, 'Sender', $this->testExampleCom, '', '', '');
        
        $this->assertSame($longBody, $emailTemplate->getEmailTemplateBody());
        $this->assertSame($longSubject, $emailTemplate->getEmailTemplateSubject());
    }

    public function testHtmlContentInBody(): void
    {
        $htmlContent = '
            <html>
                <head><title>Invoice</title></head>
                <body>
                    <h1>Thank you for your business!</h1>
                    <p>Please find your invoice attached.</p>
                    <table>
                        <tr><td>Item</td><td>Price</td></tr>
                        <tr><td>Service</td><td>$100.00</td></tr>
                    </table>
                </body>
            </html>
        ';
        
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateBody($htmlContent);
        
        $this->assertSame($htmlContent, $emailTemplate->getEmailTemplateBody());
    }

    public function testMultipleEmailAddresses(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateCc('manager@company.com, supervisor@company.com');
        $emailTemplate->setEmailTemplateBcc('archive@company.com, backup@company.com');
        
        $this->assertSame('manager@company.com, supervisor@company.com', $emailTemplate->getEmailTemplateCc());
        $this->assertSame('archive@company.com, backup@company.com', $emailTemplate->getEmailTemplateBcc());
    }

    public function testSpecialCharactersInContent(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateSubject('Invoice #12345 - €1,234.56 (20% VAT)');
        $emailTemplate->setEmailTemplateFromName('Müller & Associates');
        
        $this->assertSame('Invoice #12345 - €1,234.56 (20% VAT)', $emailTemplate->getEmailTemplateSubject());
        $this->assertSame('Müller & Associates', $emailTemplate->getEmailTemplateFromName());
    }

    public function testUnicodeInContent(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateTitle('Förmula Tëst 测试');
        $emailTemplate->setEmailTemplateSubject('Ñoñó España 日本語');
        
        $this->assertSame('Förmula Tëst 测试', $emailTemplate->getEmailTemplateTitle());
        $this->assertSame('Ñoñó España 日本語', $emailTemplate->getEmailTemplateSubject());
    }

    public function testChainedSetterCalls(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateTitle('Chained Template');
        $emailTemplate->setEmailTemplateType('chained');
        $emailTemplate->setEmailTemplateBody('<p>Chained body</p>');
        $emailTemplate->setEmailTemplateSubject('Chained Subject');
        $emailTemplate->setEmailTemplateFromName('Chained Sender');
        $emailTemplate->setEmailTemplateFromEmail('chained@test.com');
        $emailTemplate->setEmailTemplateCc($this->ccTestCom);
        $emailTemplate->setEmailTemplateBcc($this->bccTestCom);
        $emailTemplate->setEmailTemplatePdfTemplate('chained.pdf');
        
        $this->assertSame('Chained Template', $emailTemplate->getEmailTemplateTitle());
        $this->assertSame('chained', $emailTemplate->getEmailTemplateType());
        $this->assertSame('<p>Chained body</p>', $emailTemplate->getEmailTemplateBody());
        $this->assertSame('Chained Subject', $emailTemplate->getEmailTemplateSubject());
        $this->assertSame('Chained Sender', $emailTemplate->getEmailTemplateFromName());
        $this->assertSame('chained@test.com', $emailTemplate->getEmailTemplateFromEmail());
        $this->assertSame($this->ccTestCom, $emailTemplate->getEmailTemplateCc());
        $this->assertSame($this->bccTestCom, $emailTemplate->getEmailTemplateBcc());
        $this->assertSame('chained.pdf', $emailTemplate->getEmailTemplatePdfTemplate());
    }

    public function testEmptyFieldHandling(): void
    {
        $emailTemplate = new EmailTemplate('', '', '', '', '', '', '', '', '');
        
        $this->assertSame('', $emailTemplate->getEmailTemplateTitle());
        $this->assertSame('', $emailTemplate->getEmailTemplateType());
        $this->assertSame('', $emailTemplate->getEmailTemplateBody());
        $this->assertSame('', $emailTemplate->getEmailTemplateSubject());
        $this->assertSame('', $emailTemplate->getEmailTemplateFromName());
        $this->assertSame('', $emailTemplate->getEmailTemplateFromEmail());
        $this->assertSame('', $emailTemplate->getEmailTemplateCc());
        $this->assertSame('', $emailTemplate->getEmailTemplateBcc());
        $this->assertSame('', $emailTemplate->getEmailTemplatePdfTemplate());
    }

    public function testNullFieldHandling(): void
    {
        $emailTemplate = new EmailTemplate(null, null, '', null, null, null, null, null, null);
        
        $this->assertNull($emailTemplate->getEmailTemplateTitle());
        $this->assertNull($emailTemplate->getEmailTemplateType());
        $this->assertSame('', $emailTemplate->getEmailTemplateBody());
        $this->assertNull($emailTemplate->getEmailTemplateSubject());
        $this->assertNull($emailTemplate->getEmailTemplateFromName());
        $this->assertNull($emailTemplate->getEmailTemplateFromEmail());
        $this->assertNull($emailTemplate->getEmailTemplateCc());
        $this->assertNull($emailTemplate->getEmailTemplateBcc());
        $this->assertNull($emailTemplate->getEmailTemplatePdfTemplate());
    }

    public function testCompleteEmailTemplateSetup(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmailTemplateTitle('Complete Setup Template');
        $emailTemplate->setEmailTemplateType('complete');
        $emailTemplate->setEmailTemplateBody('<h1>Complete Setup</h1><p>This is a complete email template setup.</p>');
        $emailTemplate->setEmailTemplateSubject('Complete Setup Subject');
        $emailTemplate->setEmailTemplateFromName('Complete Sender');
        $emailTemplate->setEmailTemplateFromEmail('complete@setup.com');
        $emailTemplate->setEmailTemplateCc('cc@setup.com');
        $emailTemplate->setEmailTemplateBcc('bcc@setup.com');
        $emailTemplate->setEmailTemplatePdfTemplate('complete_setup.pdf');
        
        $this->assertSame('Complete Setup Template', $emailTemplate->getEmailTemplateTitle());
        $this->assertSame('complete', $emailTemplate->getEmailTemplateType());
        $this->assertSame('<h1>Complete Setup</h1><p>This is a complete email template setup.</p>', $emailTemplate->getEmailTemplateBody());
        $this->assertSame('Complete Setup Subject', $emailTemplate->getEmailTemplateSubject());
        $this->assertSame('Complete Sender', $emailTemplate->getEmailTemplateFromName());
        $this->assertSame('complete@setup.com', $emailTemplate->getEmailTemplateFromEmail());
        $this->assertSame('cc@setup.com', $emailTemplate->getEmailTemplateCc());
        $this->assertSame('bcc@setup.com', $emailTemplate->getEmailTemplateBcc());
        $this->assertSame('complete_setup.pdf', $emailTemplate->getEmailTemplatePdfTemplate());
    }

    public function testPdfTemplateExtensions(): void
    {
        $pdfTemplate = new EmailTemplate('PDF Template', 'pdf', 'Body', 'Subject', 'Sender', $this->testExampleCom, '', '', 'template.pdf');
        $this->assertSame('template.pdf', $pdfTemplate->getEmailTemplatePdfTemplate());

        $docxTemplate = new EmailTemplate('DOCX Template', 'docx', 'Body', 'Subject', 'Sender', $this->testExampleCom, '', '', 'template.docx');
        $this->assertSame('template.docx', $docxTemplate->getEmailTemplatePdfTemplate());
    }

    public function testEmailAddressFormats(): void
    {
        $emailTemplate = new EmailTemplate();
        
        // Standard email
        $emailTemplate->setEmailTemplateFromEmail('user@domain.com');
        $this->assertSame('user@domain.com', $emailTemplate->getEmailTemplateFromEmail());
        
        // Email with subdomain
        $emailTemplate->setEmailTemplateFromEmail('admin@mail.company.co.uk');
        $this->assertSame('admin@mail.company.co.uk', $emailTemplate->getEmailTemplateFromEmail());
        
        // Email with plus sign
        $emailTemplate->setEmailTemplateFromEmail('user+tag@example.com');
        $this->assertSame('user+tag@example.com', $emailTemplate->getEmailTemplateFromEmail());
    }

    public function testReturnTypeConsistency(): void
    {
        $emailTemplate = new EmailTemplate('Test', 'test', 'Body', 'Subject', 'Name', 'email@test.com', $this->ccTestCom, $this->bccTestCom, 'template.pdf');
        // Test that nullable fields can return null or string
        $this->assertTrue(is_string($emailTemplate->getEmailTemplateTitle()) || is_null($emailTemplate->getEmailTemplateTitle()));
        $this->assertTrue(is_string($emailTemplate->getEmailTemplateType()) || is_null($emailTemplate->getEmailTemplateType()));
        $this->assertIsString($emailTemplate->getEmailTemplateBody()); // Non-nullable
        $this->assertTrue(is_string($emailTemplate->getEmailTemplateSubject()) || is_null($emailTemplate->getEmailTemplateSubject()));
        $this->assertTrue(is_string($emailTemplate->getEmailTemplateFromName()) || is_null($emailTemplate->getEmailTemplateFromName()));
        $this->assertTrue(is_string($emailTemplate->getEmailTemplateFromEmail()) || is_null($emailTemplate->getEmailTemplateFromEmail()));
        $this->assertTrue(is_string($emailTemplate->getEmailTemplateCc()) || is_null($emailTemplate->getEmailTemplateCc()));
        $this->assertTrue(is_string($emailTemplate->getEmailTemplateBcc()) || is_null($emailTemplate->getEmailTemplateBcc()));
        $this->assertTrue(is_string($emailTemplate->getEmailTemplatePdfTemplate()) || is_null($emailTemplate->getEmailTemplatePdfTemplate()));
    }
}
