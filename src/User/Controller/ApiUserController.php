<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\User;
use App\User\UserRepository;
use OpenApi\Annotations as OA;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;

/**
 * @OA\Tag(
 *     name="user",
 *     description="User"
 * )
 */
final readonly class ApiUserController
{
    public function __construct(private DataResponseFactoryInterface $responseFactory)
    {
    }

    /**
     * @OA\Get (
     *     path="/api/user",
     *     tags={"user"},
     *
     * @OA\Response (response="200", description="Get users list")
     * )
     */
    public function index(UserRepository $userRepository): \Psr\Http\Message\ResponseInterface
    {
        $users = $userRepository->getReader();

        $items = [];
        /** @var User $user */
        foreach ($users as $user) {
            $items[] = ['login' => $user->getLogin(), 'created_at' => $user
                ->getCreatedAt()
                ->format('H:i:s d.m.Y'), ];
        }

        return $this->responseFactory->createResponse($items);
    }

    /**
     * @OA\Get (
     *     path="/api/user/{login}",
     *     tags={"user"},
     *
     * @OA\Parameter (
     *
     * @OA\Schema (type="string"),
     *     in="path",
     *     name="login",
     *     parameter="login"
     *     ),
     *
     * @OA\Response (response="200", description="Get user info")
     * )
     */
    public function profile(UserRepository $userRepository, CurrentRoute $currentRoute): \Psr\Http\Message\ResponseInterface
    {
        $login = $currentRoute->getArgument('login');
        if (null !== $login) {
            $user = $userRepository->findByLogin($login);
            if ($user === null) {
                return $this->responseFactory->createResponse('Page not found', 404);
            }

            return $this->responseFactory->createResponse(
                ['login' => $user->getLogin(), 'created_at' => $user
                    ->getCreatedAt()
                    ->format('H:i:s d.m.Y'), ],
            );
        }
        return $this->responseFactory->createResponse('Page not found', 404);
    }
}
