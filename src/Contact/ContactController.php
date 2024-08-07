<?php

declare(strict_types=1);

namespace App\Contact;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Http\Header;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\ViewRenderer;

final class ContactController
{
    public function __construct(
        private ContactMailer $mailer,
        private ResponseFactoryInterface $responseFactory,
        private UrlGeneratorInterface $url,
        private ViewRenderer $viewRenderer
    ) {
        $this->viewRenderer = $viewRenderer
            ->withControllerName('contact')
            ->withViewPath(__DIR__ . '/views');
    }

    public function fill(
        FormHydrator $formHydrator,
        ServerRequestInterface $request
    ) : ResponseInterface {
        $body = $request->getParsedBody();
        $form = new ContactForm();
        if (($request->getMethod() === Method::POST) 
           && $formHydrator->populate($form, (array) $body) 
           && $form->isValid()) {
            $this->mailer->send($form, $request);
            return $this->responseFactory
                ->createResponse(Status::FOUND)
                ->withHeader(Header::LOCATION, $this->url->generate('contact/fill'));
        }

        return $this->viewRenderer->render('form', ['form' => $form]);
    }
}
