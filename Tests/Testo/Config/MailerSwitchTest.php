<?php

declare(strict_types=1);

namespace Tests\Testo\Config;

use Testo\Assert;
use Testo\Test;
use Yiisoft\Mailer\FileMailer;
use Yiisoft\Mailer\MailerInterface;

/**
 * config/web/di/mailer.php: under YII_ENV=dev emails are written to files, unless MAIL_TO_FILE says
 * otherwise. A saved .eml never holds this app's HTML confirm link, so a deployment still on dev needs
 * a way to send real mail; nothing changes unless that switch is set.
 */
#[Test]
final class MailerSwitchTest
{
    /**
     * @return array<string, string> the DI definitions the config file returns
     */
    private function definitionsFor(?string $yiiEnv, ?string $mailToFile): array
    {
        $before = [$_ENV['YII_ENV'] ?? null, $_ENV['MAIL_TO_FILE'] ?? null];
        $this->set('YII_ENV', $yiiEnv);
        $this->set('MAIL_TO_FILE', $mailToFile);
        try {
            /** @var array<string, string> $definitions */
            $definitions = require dirname(__DIR__, 3) . '/config/web/di/mailer.php';
        } finally {
            $this->set('YII_ENV', $before[0]);
            $this->set('MAIL_TO_FILE', $before[1]);
        }
        return $definitions;
    }

    /** @param non-empty-string $name */
    private function set(string $name, ?string $value): void
    {
        if (null === $value) {
            unset($_ENV[$name]);
            return;
        }
        $_ENV[$name] = $value;
    }

    public function devWritesEmailsToFilesByDefault(): void
    {
        Assert::same($this->definitionsFor('dev', null), [MailerInterface::class => FileMailer::class]);
        Assert::same($this->definitionsFor('dev', ''), [MailerInterface::class => FileMailer::class]);
    }

    public function devWithAnyOtherValueStillWritesToFiles(): void
    {
        foreach (['1', 'true', 'yes', 'on', 'maybe'] as $value) {
            Assert::same($this->definitionsFor('dev', $value), [MailerInterface::class => FileMailer::class], $value);
        }
    }

    public function devSendsRealMailWhenSwitchedOff(): void
    {
        foreach (['0', 'false', 'no', 'off', 'OFF', ' False '] as $value) {
            Assert::same($this->definitionsFor('dev', $value), [], $value);
        }
    }

    public function otherEnvironmentsNeverUseTheFileMailer(): void
    {
        foreach ([null, 'prod', 'test'] as $env) {
            foreach ([null, '0', '1'] as $value) {
                Assert::same($this->definitionsFor($env, $value), [], (string) $env . '/' . (string) $value);
            }
        }
    }
}
