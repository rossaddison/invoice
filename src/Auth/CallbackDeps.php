<?php

declare(strict_types=1);

namespace App\Auth;

use App\Invoice\UserInv\UserInvRepository;
use App\User\UserRepository;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Translator\TranslatorInterface;

final class CallbackDeps
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly TranslatorInterface $translator,
        public readonly TokenRepository $tR,
        public readonly UserInvRepository $uiR,
        public readonly UserRepository $uR,
    ) {
    }
}
