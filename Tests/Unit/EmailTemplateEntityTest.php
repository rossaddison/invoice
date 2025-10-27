<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Invoice\Entity\EmailTemplate;
use Codeception\Test\Unit;

final class EmailTemplateEntityTest extends Unit
{
    public function testConstructorWithDefaults(): void
    {
        $emailTemplate = new EmailTemplate();
        
        $this->assertNull($emailTemplate->getEmail_template_id());
        $this->assertSame('', $emailTemplate->getEmail_template_title());
        $this->assertSame('', $emailTemplate->getEmail_template_type());
        $this->assertSame('', $emailTemplate->getEmail_template_body());
        $this->assertSame('', $emailTemplate->getEmail_template_subject());
        $this->assertSame('', $emailTemplate->getEmail_template_from_name());
        $this->assertSame('', $emailTemplate->getEmail_template_from_email());
        $this->assertSame('', $emailTemplate->getEmail_template_cc());
        $this->assertSame('', $emailTemplate->getEmail_template_bcc());
        $this->assertSame('', $emailTemplate->getEmail_template_pdf_template());
    }

    public function testConstructorWithAllParameters(): void
    {
        $emailTemplate = new EmailTemplate(
            'Invoice Template',
            'invoice',
            '<p>Your invoice is ready</p>',
            'Invoice #123 from Company',
            'Billing Department',
            'billing@company.com',
            'manager@company.com',
            'archive@company.com',
            'invoice_template.pdf'
        );
        
        $this->assertNull($emailTemplate->getEmail_template_id());
        $this->assertSame('Invoice Template', $emailTemplate->getEmail_template_title());
        $this->assertSame('invoice', $emailTemplate->getEmail_template_type());
        $this->assertSame('<p>Your invoice is ready</p>', $emailTemplate->getEmail_template_body());
        $this->assertSame('Invoice #123 from Company', $emailTemplate->getEmail_template_subject());
        $this->assertSame('Billing Department', $emailTemplate->getEmail_template_from_name());
        $this->assertSame('billing@company.com', $emailTemplate->getEmail_template_from_email());
        $this->assertSame('manager@company.com', $emailTemplate->getEmail_template_cc());
        $this->assertSame('archive@company.com', $emailTemplate->getEmail_template_bcc());
        $this->assertSame('invoice_template.pdf', $emailTemplate->getEmail_template_pdf_template());
    }

    public function testIdGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        
        $this->assertNull($emailTemplate->getEmail_template_id());
    }

    public function testTitleSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_title('Quote Template');
        
        $this->assertSame('Quote Template', $emailTemplate->getEmail_template_title());
    }

    public function testTypeSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_type('quote');
        
        $this->assertSame('quote', $emailTemplate->getEmail_template_type());
    }

    public function testBodySetterAndGetter(): void
    {
        $htmlBody = '<html><body><h1>Thank you!</h1><p>Your quote is attached.</p></body></html>';
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_body($htmlBody);
        
        $this->assertSame($htmlBody, $emailTemplate->getEmail_template_body());
    }

    public function testSubjectSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_subject('Your Quote Request');
        
        $this->assertSame('Your Quote Request', $emailTemplate->getEmail_template_subject());
    }

    public function testFromNameSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_from_name('Sales Team');
        
        $this->assertSame('Sales Team', $emailTemplate->getEmail_template_from_name());
    }

    public function testFromEmailSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_from_email('sales@company.com');
        
        $this->assertSame('sales@company.com', $emailTemplate->getEmail_template_from_email());
    }

    public function testCcSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_cc('supervisor@company.com');
        
        $this->assertSame('supervisor@company.com', $emailTemplate->getEmail_template_cc());
    }

    public function testBccSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_bcc('records@company.com');
        
        $this->assertSame('records@company.com', $emailTemplate->getEmail_template_bcc());
    }

    public function testPdfTemplateSetterAndGetter(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_pdf_template('quote_template.pdf');
        
        $this->assertSame('quote_template.pdf', $emailTemplate->getEmail_template_pdf_template());
    }

    public function testCommonEmailTemplateTypes(): void
    {
        $invoiceTemplate = new EmailTemplate('Invoice Template', 'invoice', 'Invoice body', 'Invoice subject', 'Billing', 'billing@test.com', '', '', 'invoice.pdf');
        $this->assertSame('invoice', $invoiceTemplate->getEmail_template_type());
        $this->assertSame('Invoice Template', $invoiceTemplate->getEmail_template_title());

        $quoteTemplate = new EmailTemplate('Quote Template', 'quote', 'Quote body', 'Quote subject', 'Sales', 'sales@test.com', '', '', 'quote.pdf');
        $this->assertSame('quote', $quoteTemplate->getEmail_template_type());
        $this->assertSame('Quote Template', $quoteTemplate->getEmail_template_title());

        $reminderTemplate = new EmailTemplate('Reminder Template', 'reminder', 'Reminder body', 'Payment reminder', 'Accounts', 'accounts@test.com', '', '', '');
        $this->assertSame('reminder', $reminderTemplate->getEmail_template_type());
        $this->assertSame('Reminder Template', $reminderTemplate->getEmail_template_title());
    }

    public function testLongEmailTemplateContent(): void
    {
        $longBody = str_repeat('<p>This is a very long email template body with lots of content. </p>', 20);
        $longSubject = 'Very Long Email Subject That Could Potentially Exceed Normal Database Limits And Still Be Valid';
        
        $emailTemplate = new EmailTemplate('Long Template', 'long', $longBody, $longSubject, 'Sender', 'test@example.com', '', '', '');
        
        $this->assertSame($longBody, $emailTemplate->getEmail_template_body());
        $this->assertSame($longSubject, $emailTemplate->getEmail_template_subject());
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
        $emailTemplate->setEmail_template_body($htmlContent);
        
        $this->assertSame($htmlContent, $emailTemplate->getEmail_template_body());
    }

    public function testMultipleEmailAddresses(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_cc('manager@company.com, supervisor@company.com');
        $emailTemplate->setEmail_template_bcc('archive@company.com, backup@company.com');
        
        $this->assertSame('manager@company.com, supervisor@company.com', $emailTemplate->getEmail_template_cc());
        $this->assertSame('archive@company.com, backup@company.com', $emailTemplate->getEmail_template_bcc());
    }

    public function testSpecialCharactersInContent(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_subject('Invoice #12345 - €1,234.56 (20% VAT)');
        $emailTemplate->setEmail_template_from_name('Müller & Associates');
        
        $this->assertSame('Invoice #12345 - €1,234.56 (20% VAT)', $emailTemplate->getEmail_template_subject());
        $this->assertSame('Müller & Associates', $emailTemplate->getEmail_template_from_name());
    }

    public function testUnicodeInContent(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_title('Förmula Tëst 测试');
        $emailTemplate->setEmail_template_subject('Ñoñó España 日本語');
        
        $this->assertSame('Förmula Tëst 测试', $emailTemplate->getEmail_template_title());
        $this->assertSame('Ñoñó España 日本語', $emailTemplate->getEmail_template_subject());
    }

    public function testChainedSetterCalls(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_title('Chained Template');
        $emailTemplate->setEmail_template_type('chained');
        $emailTemplate->setEmail_template_body('<p>Chained body</p>');
        $emailTemplate->setEmail_template_subject('Chained Subject');
        $emailTemplate->setEmail_template_from_name('Chained Sender');
        $emailTemplate->setEmail_template_from_email('chained@test.com');
        $emailTemplate->setEmail_template_cc('cc@test.com');
        $emailTemplate->setEmail_template_bcc('bcc@test.com');
        $emailTemplate->setEmail_template_pdf_template('chained.pdf');
        
        $this->assertSame('Chained Template', $emailTemplate->getEmail_template_title());
        $this->assertSame('chained', $emailTemplate->getEmail_template_type());
        $this->assertSame('<p>Chained body</p>', $emailTemplate->getEmail_template_body());
        $this->assertSame('Chained Subject', $emailTemplate->getEmail_template_subject());
        $this->assertSame('Chained Sender', $emailTemplate->getEmail_template_from_name());
        $this->assertSame('chained@test.com', $emailTemplate->getEmail_template_from_email());
        $this->assertSame('cc@test.com', $emailTemplate->getEmail_template_cc());
        $this->assertSame('bcc@test.com', $emailTemplate->getEmail_template_bcc());
        $this->assertSame('chained.pdf', $emailTemplate->getEmail_template_pdf_template());
    }

    public function testEmptyFieldHandling(): void
    {
        $emailTemplate = new EmailTemplate('', '', '', '', '', '', '', '', '');
        
        $this->assertSame('', $emailTemplate->getEmail_template_title());
        $this->assertSame('', $emailTemplate->getEmail_template_type());
        $this->assertSame('', $emailTemplate->getEmail_template_body());
        $this->assertSame('', $emailTemplate->getEmail_template_subject());
        $this->assertSame('', $emailTemplate->getEmail_template_from_name());
        $this->assertSame('', $emailTemplate->getEmail_template_from_email());
        $this->assertSame('', $emailTemplate->getEmail_template_cc());
        $this->assertSame('', $emailTemplate->getEmail_template_bcc());
        $this->assertSame('', $emailTemplate->getEmail_template_pdf_template());
    }

    public function testNullFieldHandling(): void
    {
        $emailTemplate = new EmailTemplate(null, null, '', null, null, null, null, null, null);
        
        $this->assertNull($emailTemplate->getEmail_template_title());
        $this->assertNull($emailTemplate->getEmail_template_type());
        $this->assertSame('', $emailTemplate->getEmail_template_body());
        $this->assertNull($emailTemplate->getEmail_template_subject());
        $this->assertNull($emailTemplate->getEmail_template_from_name());
        $this->assertNull($emailTemplate->getEmail_template_from_email());
        $this->assertNull($emailTemplate->getEmail_template_cc());
        $this->assertNull($emailTemplate->getEmail_template_bcc());
        $this->assertNull($emailTemplate->getEmail_template_pdf_template());
    }

    public function testCompleteEmailTemplateSetup(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setEmail_template_title('Complete Setup Template');
        $emailTemplate->setEmail_template_type('complete');
        $emailTemplate->setEmail_template_body('<h1>Complete Setup</h1><p>This is a complete email template setup.</p>');
        $emailTemplate->setEmail_template_subject('Complete Setup Subject');
        $emailTemplate->setEmail_template_from_name('Complete Sender');
        $emailTemplate->setEmail_template_from_email('complete@setup.com');
        $emailTemplate->setEmail_template_cc('cc@setup.com');
        $emailTemplate->setEmail_template_bcc('bcc@setup.com');
        $emailTemplate->setEmail_template_pdf_template('complete_setup.pdf');
        
        $this->assertSame('Complete Setup Template', $emailTemplate->getEmail_template_title());
        $this->assertSame('complete', $emailTemplate->getEmail_template_type());
        $this->assertSame('<h1>Complete Setup</h1><p>This is a complete email template setup.</p>', $emailTemplate->getEmail_template_body());
        $this->assertSame('Complete Setup Subject', $emailTemplate->getEmail_template_subject());
        $this->assertSame('Complete Sender', $emailTemplate->getEmail_template_from_name());
        $this->assertSame('complete@setup.com', $emailTemplate->getEmail_template_from_email());
        $this->assertSame('cc@setup.com', $emailTemplate->getEmail_template_cc());
        $this->assertSame('bcc@setup.com', $emailTemplate->getEmail_template_bcc());
        $this->assertSame('complete_setup.pdf', $emailTemplate->getEmail_template_pdf_template());
    }

    public function testPdfTemplateExtensions(): void
    {
        $pdfTemplate = new EmailTemplate('PDF Template', 'pdf', 'Body', 'Subject', 'Sender', 'test@example.com', '', '', 'template.pdf');
        $this->assertSame('template.pdf', $pdfTemplate->getEmail_template_pdf_template());

        $docxTemplate = new EmailTemplate('DOCX Template', 'docx', 'Body', 'Subject', 'Sender', 'test@example.com', '', '', 'template.docx');
        $this->assertSame('template.docx', $docxTemplate->getEmail_template_pdf_template());
    }

    public function testEmailAddressFormats(): void
    {
        $emailTemplate = new EmailTemplate();
        
        // Standard email
        $emailTemplate->setEmail_template_from_email('user@domain.com');
        $this->assertSame('user@domain.com', $emailTemplate->getEmail_template_from_email());
        
        // Email with subdomain
        $emailTemplate->setEmail_template_from_email('admin@mail.company.co.uk');
        $this->assertSame('admin@mail.company.co.uk', $emailTemplate->getEmail_template_from_email());
        
        // Email with plus sign
        $emailTemplate->setEmail_template_from_email('user+tag@example.com');
        $this->assertSame('user+tag@example.com', $emailTemplate->getEmail_template_from_email());
    }

    public function testReturnTypeConsistency(): void
    {
        $emailTemplate = new EmailTemplate('Test', 'test', 'Body', 'Subject', 'Name', 'email@test.com', 'cc@test.com', 'bcc@test.com', 'template.pdf');
        
        // Test that nullable fields can return null or string
        $this->assertTrue(is_string($emailTemplate->getEmail_template_title()) || is_null($emailTemplate->getEmail_template_title()));
        $this->assertTrue(is_string($emailTemplate->getEmail_template_type()) || is_null($emailTemplate->getEmail_template_type()));
        $this->assertIsString($emailTemplate->getEmail_template_body()); // Non-nullable
        $this->assertTrue(is_string($emailTemplate->getEmail_template_subject()) || is_null($emailTemplate->getEmail_template_subject()));
        $this->assertTrue(is_string($emailTemplate->getEmail_template_from_name()) || is_null($emailTemplate->getEmail_template_from_name()));
        $this->assertTrue(is_string($emailTemplate->getEmail_template_from_email()) || is_null($emailTemplate->getEmail_template_from_email()));
        $this->assertTrue(is_string($emailTemplate->getEmail_template_cc()) || is_null($emailTemplate->getEmail_template_cc()));
        $this->assertTrue(is_string($emailTemplate->getEmail_template_bcc()) || is_null($emailTemplate->getEmail_template_bcc()));
        $this->assertTrue(is_string($emailTemplate->getEmail_template_pdf_template()) || is_null($emailTemplate->getEmail_template_pdf_template()));
    }
}