<?php

declare(strict_types=1);

use Yiisoft\Mailer\FileMailer;
use Yiisoft\Mailer\MailerInterface;

/**
 * Local dev convenience: signup/HomeCare-signup confirmation emails (and
 * every other email this app sends) get written to runtime/mail/*.eml
 * instead of actually being sent, so testing the confirm-link flow never
 * needs a real inbox. FileMailer itself is already registered by
 * yiisoft/mailer (see vendor/yiisoft/mailer/config/di.php, path taken from
 * $params['yiisoft/mailer']['fileMailer']['path'] = '@runtime/mail') — this
 * just points MailerInterface at it.
 *
 * Strictly opt-in to YII_ENV=dev so production (and test) keep the real
 * yiisoft/mailer-symfony ESMTP transport untouched.
 */
return ($_ENV['YII_ENV'] ?? '') === 'dev'
    ? [MailerInterface::class => FileMailer::class]
    : [];
