<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv\Service;

use App\Invoice\Inv\Service\PlaywrightPdfRenderService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Aliases\Aliases;

/**
 * Covers isBuilt()/scriptPath() only — render() shells out to a real Node
 * process and is exercised via `php yii invoice/render-pdf` and the
 * inv/pdfPlaywright button instead, not a unit test.
 */
#[Test]
final class PlaywrightPdfRenderServiceTest
{
    private function makeService(string $root): PlaywrightPdfRenderService
    {
        /** @var Aliases&m\MockInterface $aliases */
        $aliases = m::mock(Aliases::class);
        $e = $aliases->shouldReceive('get');
        $e->with('@root')->andReturn($root);
        return new PlaywrightPdfRenderService($aliases);
    }

    public function scriptPathIsRootSlashPlaywrightSlashRenderInvoiceJs(): void
    {
        $service = $this->makeService('/some/root');

        Assert::same('/some/root/playwright/render-invoice.js', $service->scriptPath());
    }

    public function isBuiltReturnsFalseWhenScriptDoesNotExist(): void
    {
        $service = $this->makeService(sys_get_temp_dir() . '/does-not-exist-' . uniqid());

        Assert::false($service->isBuilt());
    }

    public function isBuiltReturnsTrueWhenScriptExists(): void
    {
        $root = sys_get_temp_dir() . '/playwright-pdf-render-service-test-' . uniqid();
        mkdir($root . '/playwright', recursive: true);
        file_put_contents($root . '/playwright/render-invoice.js', '// stub');

        try {
            $service = $this->makeService($root);

            Assert::true($service->isBuilt());
        } finally {
            unlink($root . '/playwright/render-invoice.js');
            rmdir($root . '/playwright');
            rmdir($root);
        }
    }
}
