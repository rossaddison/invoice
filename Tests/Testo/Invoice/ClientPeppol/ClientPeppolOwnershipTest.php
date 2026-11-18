<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\ClientPeppol;

use App\Auth\Permissions;
use App\Infrastructure\Persistence\ClientPeppol\ClientPeppol;
use App\Infrastructure\Persistence\User\User;
use App\Invoice\ClientPeppol\ClientPeppolController;
use App\Invoice\ClientPeppol\ClientPeppolRepository;
use App\Invoice\ClientPeppol\ClientPeppolService;
use App\Invoice\Setting\SettingRepository;
use App\Invoice\UserClient\UserClientRepository;
use App\Service\WebControllerService;
use App\User\UserService;
use GuzzleHttp\Psr7\HttpFactory;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * clientpeppol/delete/{client_id} (and add and edit) need only edit.client.peppol, which observers hold, and
 * took the client id from the address. An observer could delete, add or change the Peppol settings, and so
 * the e-invoice delivery address, of any client. Staff (edit.inv) may use any client; anyone else only the
 * clients assigned to them.
 */
#[Test]
final class ClientPeppolOwnershipTest
{
    /** @return array{0: ClientPeppolController, 1: ClientPeppolService&m\MockInterface} */
    private function controller(bool $staff, int $userId): array
    {
        $service = TypedMock::of(ClientPeppolService::class);

        $webService = TypedMock::of(WebControllerService::class);
        $webService->shouldReceive('getNotFoundResponse')->andReturn(new HttpFactory()->createResponse(404));
        $webService->shouldReceive('getRedirectResponse')->andReturn(new HttpFactory()->createResponse(302));

        $user = TypedMock::of(User::class);
        $user->shouldReceive('reqId')->andReturn($userId);
        $userService = TypedMock::of(UserService::class);
        $userService->shouldReceive('getUser')->andReturn($user);
        $userService->shouldReceive('hasPermission')->andReturnUsing(
            static fn (string $permission): bool => $staff || Permissions::EDIT_INV !== $permission,
        );

        $webViewRenderer = TypedMock::of(WebViewRenderer::class);
        $webViewRenderer->shouldReceive('withControllerName')->andReturnSelf();
        $webViewRenderer->shouldReceive('withLayout')->andReturnSelf();
        $session = TypedMock::of(SessionInterface::class);
        $session->shouldReceive('getId')->andReturn('test-session-id');
        $session->shouldReceive('get')->with('tfa_verified')->andReturn(true);
        $translator = TypedMock::of(TranslatorInterface::class);
        $translator->shouldReceive('translate')->andReturnUsing(static fn (string $key): string => $key);
        $flash = TypedMock::of(Flash::class);
        $flash->shouldIgnoreMissing();

        $controller = new ClientPeppolController(
            $service,
            TypedMock::of(DataResponseFactoryInterface::class),
            $session,
            TypedMock::of(SettingRepository::class),
            $translator,
            $userService,
            $webViewRenderer,
            $webService,
            $flash,
        );

        return [$controller, $service];
    }

    private function route(int $clientId): CurrentRoute
    {
        $route = TypedMock::of(CurrentRoute::class);
        $route->shouldReceive('getArgument')->with('client_id')->andReturn((string) $clientId);
        return $route;
    }

    private function peppolRow(int $clientId): ClientPeppol
    {
        $row = new ClientPeppol();
        $row->setClientId($clientId);
        return $row;
    }

    private function peppolRepository(int $clientId): ClientPeppolRepository
    {
        $repo = TypedMock::of(ClientPeppolRepository::class);
        $repo->shouldReceive('repoClientPeppolLoadedquery')->with($clientId)->andReturn($this->peppolRow($clientId));
        return $repo;
    }

    private function assignments(int $userId, int $clientId, bool $assigned): UserClientRepository
    {
        $ucR = TypedMock::of(UserClientRepository::class);
        $ucR->shouldReceive('repoUserClientqueryCount')->with($userId, $clientId)->andReturn($assigned ? 1 : 0);
        return $ucR;
    }

    public function anObserverCannotDeleteAnotherClientsPeppolRow(): void
    {
        [$controller, $service] = $this->controller(false, 7);
        $service->shouldReceive('deleteClientPeppol')->never();

        $response = $controller->delete($this->route(99), $this->peppolRepository(99), $this->assignments(7, 99, false));

        Assert::same(404, $response->getStatusCode());
    }

    public function anObserverCanDeleteTheirOwnClientsPeppolRow(): void
    {
        [$controller, $service] = $this->controller(false, 7);
        $service->shouldReceive('deleteClientPeppol')->once();

        $response = $controller->delete($this->route(5), $this->peppolRepository(5), $this->assignments(7, 5, true));

        Assert::same(302, $response->getStatusCode());
    }

    public function staffCanDeleteAnyClientsPeppolRow(): void
    {
        [$controller, $service] = $this->controller(true, 1);
        $service->shouldReceive('deleteClientPeppol')->once();
        $ucR = TypedMock::of(UserClientRepository::class);
        $ucR->shouldReceive('repoUserClientqueryCount')->never();

        $response = $controller->delete($this->route(99), $this->peppolRepository(99), $ucR);

        Assert::same(302, $response->getStatusCode());
    }

    public function anObserverCannotOpenEditForAnotherClient(): void
    {
        [$controller, $service] = $this->controller(false, 7);
        $service->shouldReceive('saveClientPeppol')->never();
        $request = new \GuzzleHttp\Psr7\ServerRequest('POST', '/', [], null, '1.1', []);

        $response = $controller->edit(
            $request,
            $this->route(99),
            TypedMock::of(\Yiisoft\FormModel\FormHydrator::class),
            $this->peppolRepository(99),
            $this->assignments(7, 99, false),
        );

        Assert::same(404, $response->getStatusCode());
    }

    public function anObserverCannotAddForAnotherClient(): void
    {
        [$controller, $service] = $this->controller(false, 7);
        $service->shouldReceive('saveClientPeppol')->never();
        $request = new \GuzzleHttp\Psr7\ServerRequest('POST', '/', [], null, '1.1', []);

        $response = $controller->add(
            $this->route(99),
            $request,
            TypedMock::of(\Yiisoft\FormModel\FormHydrator::class),
            TypedMock::of(\App\Invoice\Client\ClientRepository::class),
            $this->assignments(7, 99, false),
        );

        Assert::same(404, $response->getStatusCode());
    }
}
