<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Identity\Identity;
use App\Invoice\Setting\SettingRepository;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\ORMInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\Cookies\CookieEncryptor;
use Yiisoft\Cookies\CookieMiddleware;
use Yiisoft\Cookies\CookieSigner;
use Yiisoft\Definitions\Reference;
use Yiisoft\Session\SessionInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\User\Login\Cookie\CookieLogin;

/**
 * @var array $params
 * @var array $params['yiisoft/cookies']
 * @var string $params['yiisoft/cookies']['secretKey']
 */
$secretKey = $params['yiisoft/cookies']['secretKey'];
return [
    // Previously unconfigured -- the vendor default ($duration = null) makes
    // the "autoLogin" cookie a session cookie that expires when the browser
    // closes, i.e. indistinguishable from not checking "Remember Me" at all.
    // Duration is user-configurable: Settings tab "General" -> "Remember Me
    // Duration (Days)" (SettingRepository::rememberMeDurationDays(), 30-day
    // default). Resolved fresh per container build rather than cached at
    // request start, so a saved settings change takes effect on the very
    // next login without a redeploy.
    CookieLogin::class => static fn (
        SettingRepository $settingRepository
    ): CookieLogin => new CookieLogin(
        new DateInterval('P' . $settingRepository->rememberMeDurationDays() . 'D')
    ),
    IdentityRepositoryInterface::class => static function (ContainerInterface $container): RepositoryInterface {
        /** @var ORMInterface $orm */
        $orm = $container->get(ORMInterface::class);
        $repository = $orm->getRepository(Identity::class);
        assert($repository instanceof \Cycle\ORM\RepositoryInterface);
        return $repository;
    },
    CookieMiddleware::class => static fn (
        CookieLogin $cookieLogin,
        LoggerInterface $logger
    ) => new CookieMiddleware(
        $logger,
        new CookieEncryptor($secretKey),
        new CookieSigner($secretKey),
        [$cookieLogin->getCookieName() => CookieMiddleware::SIGN],
    ),
        CurrentUser::class => [
            'withSession()' => [Reference::to(SessionInterface::class)],
            'withAccessChecker()' => [Reference::to(AccessCheckerInterface::class)],
            'reset' => function (CurrentUser $currentUser) {
                $currentUser->clear();
            },
        ],
];
