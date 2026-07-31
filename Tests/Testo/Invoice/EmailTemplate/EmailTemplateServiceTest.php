<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\EmailTemplate;

use App\Infrastructure\Persistence\EmailTemplate\EmailTemplate;
use App\Invoice\EmailTemplate\EmailTemplateRepository;
use App\Invoice\EmailTemplate\EmailTemplateService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Covers EmailTemplateService: saveEmailTemplate's field assignment and
 * deleteEmailTemplate.
 */
#[Test]
final class EmailTemplateServiceTest
{
    private function makeService(?EmailTemplateRepository $repository = null): EmailTemplateService
    {
        $repository = $repository ?? m::mock(EmailTemplateRepository::class);
        return new EmailTemplateService($repository);
    }

    public function saveEmailTemplateSetsAllFieldsAndSaves(): void
    {
        $model = new EmailTemplate();
        $array = [
            'email_template_title' => 'Invoice Sent',
            'email_template_type' => 'invoice',
            'email_template_body' => 'Please find your invoice attached.',
            'email_template_subject' => 'Your invoice',
            'email_template_from_name' => 'Acme Billing',
            'email_template_from_email' => 'billing@acme.example',
            'email_template_cc' => 'cc@acme.example',
            'email_template_bcc' => 'bcc@acme.example',
            'email_template_pdf_template' => 'default',
        ];

        /** @var EmailTemplateRepository&m\MockInterface $repository */
        $repository = m::mock(EmailTemplateRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveEmailTemplate($model, $array);

        Assert::same('Invoice Sent', $model->getEmailTemplateTitle());
        Assert::same('invoice', $model->getEmailTemplateType());
        Assert::same('Please find your invoice attached.', $model->getEmailTemplateBody());
        Assert::same('Your invoice', $model->getEmailTemplateSubject());
        Assert::same('Acme Billing', $model->getEmailTemplateFromName());
        Assert::same('billing@acme.example', $model->getEmailTemplateFromEmail());
        Assert::same('cc@acme.example', $model->getEmailTemplateCc());
        Assert::same('bcc@acme.example', $model->getEmailTemplateBcc());
        Assert::same('default', $model->getEmailTemplatePdfTemplate());
    }

    public function saveEmailTemplateSkipsFieldsWhenMissing(): void
    {
        $model = new EmailTemplate();

        /** @var EmailTemplateRepository&m\MockInterface $repository */
        $repository = m::mock(EmailTemplateRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('save');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->saveEmailTemplate($model, []);

        Assert::same('', $model->getEmailTemplateTitle());
        Assert::same('', $model->getEmailTemplateBody());
    }

    public function deleteEmailTemplateCallsRepositoryDelete(): void
    {
        $model = new EmailTemplate();

        /** @var EmailTemplateRepository&m\MockInterface $repository */
        $repository = m::mock(EmailTemplateRepository::class);
        /** @var \Mockery\Expectation $e */
        $e = $repository->expects('delete');
        $e->once()->with($model);

        $service = $this->makeService($repository);
        $service->deleteEmailTemplate($model);
    }
}
