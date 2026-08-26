<?php

declare(strict_types=1);

namespace App\Contact;

use App\Invoice\Setting\SettingRepository as sR;
use App\Service\WebControllerService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Header;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class ContactController
{
    public function __construct(
        private readonly ContactMailer $mailer,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly UrlGeneratorInterface $url,
        private WebViewRenderer $webViewRenderer,
    ) {
        $this->webViewRenderer = $webViewRenderer
            ->withControllerName('contact')
            ->withViewPath(__DIR__ . '/views');
    }

    /**
     * Gated by no_front_contact_interest_page (Settings > Front Page) --
     * previously had a default value in InvoiceInstallTrait but no
     * settings checkbox and no route check at all, unlike every other
     * no_front_X_page setting (found + wired up 2026-08-26, see
     * SiteController::contact()'s own comment for the sibling dead
     * setting removed the same day). Checked before the GET/POST branch
     * below so a disabled form 404s the submission path too, not just the
     * display -- same App\Service\WebControllerService::getNotFoundResponse()
     * convention SiteController's own gated actions use.
     *
     * @param FormHydrator $formHydrator
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function interest(
        FormHydrator $formHydrator,
        ServerRequestInterface $request,
        sR $sR,
        WebControllerService $webService,
    ): ResponseInterface {
        if ($sR->getSetting('no_front_contact_interest_page') == '1') {
            return $webService->getNotFoundResponse();
        }

        $form = new ContactForm();
        if (!$formHydrator->populateFromPostAndValidate($form, $request)) {
            // Only meaningful on the initial GET — a failed POST re-renders
            // with $form already carrying the submitted (invalid) values,
            // which take priority over the deep-link prefill.
            if ($request->getMethod() === 'GET') {
                $query = $request->getQueryParams();
                if (isset($query['subject']) || isset($query['body'])) {
                    $form->prefill(
                        (string) ($query['subject'] ?? ''),
                        (string) ($query['body'] ?? ''),
                    );
                }
            }
            return $this->webViewRenderer->render('form', ['form' => $form]);
        }

        $this->mailer->send($form);

        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader(Header::LOCATION, $this->url->generate('contact/interest'));
    }
}
