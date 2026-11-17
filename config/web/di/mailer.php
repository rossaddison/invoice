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
 *
 * MAIL_TO_FILE=0 (also false, no, off) in .env keeps the real transport even
 * under YII_ENV=dev: FileMailer saves only the headers and the plain-text
 * part, and this app's emails are HTML only, so a saved .eml never holds the
 * confirm link and a deployment that is still YII_ENV=dev cannot otherwise
 * test the confirmation email through a real inbox. Unset or any other value
 * leaves the behaviour above exactly as it was.
 */
$mailToFile = ($_ENV['YII_ENV'] ?? '') === 'dev'
    && !in_array(strtolower(trim($_ENV['MAIL_TO_FILE'] ?? '')), ['0', 'false', 'no', 'off'], true);

return $mailToFile ? [MailerInterface::class => FileMailer::class] : [];
