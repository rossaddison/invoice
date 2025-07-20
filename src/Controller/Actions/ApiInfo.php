<?php

declare(strict_types=1);

namespace App\Controller\Actions;

use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

/**
 * @OA\Info(title="Yii demo API", version="2.0")
 */
final readonly class ApiInfo implements MiddlewareInterface
{
    public function __construct(private DataResponseFactoryInterface $responseFactory) {}

    /**
     * @OA\Get(
     *     path="/api/info/v2",
     *
     *     @OA\Response(response="200", description="Get api version")
     * )
     */
    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->responseFactory->createResponse(['version' => '2.0', 'author' => 'yiisoft']);
    }
}
