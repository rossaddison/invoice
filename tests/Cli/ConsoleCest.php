<?php

declare(strict_types=1);

namespace Tests\Cli;

use Tests\Support\CliTester;
use Yiisoft\Yii\Console\ExitCode;
use function dirname;

final class ConsoleCest
{
    public function testCommandYii(CliTester $I): void
    {
        $command = dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command);
        $I->seeInShellOutput('Yii Console');
    }

    public function testCommandInvoiceItems(CliTester $I): void
    {
        $command = dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' invoice/items');
        $I->seeResultCodeIs(ExitCode::OK);
    }

    public function testCommandListCommand(CliTester $I): void
    {
        $command = dirname(__DIR__, 2) . '/yii';
        $I->runShellCommand($command . ' list');
        $I->seeResultCodeIs(ExitCode::OK);
    }
}
