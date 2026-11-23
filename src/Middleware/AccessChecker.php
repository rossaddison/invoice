<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\SessionExpiredNotice;
use App\Service\WebControllerService;
use App\User\UserService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;

final class AccessChecker implements MiddlewareInterface
{
    private ?string $permission = null;

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private UserService $userService,
        private LoggerInterface $logger,
        private WebControllerService $webService,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->permission === null) {
            throw new InvalidArgumentException('Permission not set.');
        }

        if (!$this->userService->hasPermission($this->permission)) {
            // The user's own id (UserService::getUser()), not
            // CurrentUser::getId() — that returns the identity table's own
            // row id, which is a different, misleading number to log here.
            $user = $this->userService->getUser();
            $this->logger->log(
                LogLevel::WARNING,
                'Access denied: userId=' . ($user?->reqId() ?? 'guest')
                    . ' permission=' . $this->permission,
            );
            // Nobody is signed in: an expired session looks exactly like this. Send a page the visitor opened to the
            // login page, with the "session expired" notice, instead of a blank 403 they cannot act on.
            if (null === $user && $this->isPageRequest($request)) {
                return $this->webService->getRedirectResponse('auth/login', [], SessionExpiredNotice::queryParameters());
            }
            return $this->responseFactory->createResponse(Status::FORBIDDEN);
        }

        return $handler->handle($request);
    }

    public function withPermission(string $permission): self
    {
        $new = clone $this;
        $new->permission = $permission;

        return $new;
    }

    /**
     * A page the visitor navigated to (GET/HEAD), not a form post or a background request: a redirect to the login
     * page would be swapped into the page by htmx or handed to a script by XHR, which is worse than the 403.
     */
    private function isPageRequest(ServerRequestInterface $request): bool
    {
        return in_array($request->getMethod(), [Method::GET, Method::HEAD], true)
            && !$request->hasHeader('HX-Request')
            && strtolower($request->getHeaderLine('X-Requested-With')) !== 'xmlhttprequest';
    }
}
