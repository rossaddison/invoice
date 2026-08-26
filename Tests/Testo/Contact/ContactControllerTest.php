<?php

declare(strict_types=1);

namespace Tests\Testo\Contact;

use App\Contact\ContactController;
use App\Contact\ContactMailer;
use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use Mockery as m;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Covers ContactController::interest()'s no_front_contact_interest_page
 * gate -- previously had a default value in InvoiceInstallTrait but no
 * settings checkbox and no route check at all (see that setting's own
 * comment), unlike every other no_front_X_page setting. Wired up
 * 2026-08-26 alongside the rest of that class of fix, see
 * Tests\Testo\Controller\SiteControllerTest and
 * Tests\Testo\Middleware\WebshopAvailabilityMiddlewareTest.
 *
 * @see ContactController
 */
#[Test]
final class ContactControllerTest
{
    private function webViewRenderer(): WebViewRenderer
    {
        /** @var WebViewRenderer&m\MockInterface $renderer */
        $renderer = m::mock(WebViewRenderer::class);
        $renderer->shouldReceive('withControllerName')->andReturnSelf();
        $renderer->shouldReceive('withViewPath')->andReturnSelf();
        $renderer->shouldReceive('render')->andReturnUsing(
            static fn (): ResponseInterface => new Psr17Factory()->createResponse(Status::OK),
        );
        return $renderer;
    }

    /** Never expected to be called -- proves the gate short-circuits before touching the mailer. */
    private function throwingMailer(): ContactMailer
    {
        /** @var ContactMailer&m\MockInterface $mailer */
        $mailer = m::mock(ContactMailer::class);
        $mailer->shouldNotReceive('send');
        return $mailer;
    }

    private function settingRepository(string $value): sR
    {
        /** @var sR&m\MockInterface $s */
        $s = m::mock(sR::class);
        $s->shouldReceive('getSetting')->with('no_front_contact_interest_page')->andReturn($value);
        return $s;
    }

    private function webService(): WebControllerService
    {
        $psr17 = new Psr17Factory();
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        return new WebControllerService($psr17, $psr17, $urlGenerator);
    }

    /** Never expected to be called -- proves the gate short-circuits before reading the request at all. */
    private function throwingFormHydrator(): FormHydrator
    {
        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);
        $formHydrator->shouldNotReceive('populateFromPostAndValidate');
        return $formHydrator;
    }

    private function controller(): ContactController
    {
        /** @var UrlGeneratorInterface&m\MockInterface $urlGenerator */
        $urlGenerator = m::mock(UrlGeneratorInterface::class);
        return new ContactController(
            $this->throwingMailer(),
            new Psr17Factory(),
            $urlGenerator,
            $this->webViewRenderer(),
        );
    }

    public function returns404AndNeverTouchesTheFormOrMailerWhenTheSettingIsOn(): void
    {
        $controller = $this->controller();
        $request = new Psr17Factory()->createServerRequest('GET', 'https://example.test/interest');

        $result = $controller->interest(
            $this->throwingFormHydrator(),
            $request,
            $this->settingRepository('1'),
            $this->webService(),
        );

        Assert::same($result->getStatusCode(), Status::NOT_FOUND);
    }

    public function rendersTheFormWhenTheSettingIsOff(): void
    {
        $controller = $this->controller();
        $request = new Psr17Factory()->createServerRequest('GET', 'https://example.test/interest');

        /** @var FormHydrator&m\MockInterface $formHydrator */
        $formHydrator = m::mock(FormHydrator::class);
        $formHydrator->shouldReceive('populateFromPostAndValidate')->once()->andReturn(false);

        $result = $controller->interest(
            $formHydrator,
            $request,
            $this->settingRepository('0'),
            $this->webService(),
        );

        Assert::same($result->getStatusCode(), Status::OK);
    }
}
