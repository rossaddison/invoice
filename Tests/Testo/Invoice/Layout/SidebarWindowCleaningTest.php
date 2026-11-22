<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Layout;

use Testo\Assert;
use Testo\Test;

/**
 * The staff sidebar (resources/views/invoice/layout/sidebar.php) follows the business type like the top menu does: on
 * a window-cleaning (home services) install there are no Quotes and no Tasks, so neither is offered. A standard
 * install keeps both, Tasks only while projects are enabled.
 */
#[Test]
final class SidebarWindowCleaningTest
{
    /**
     * @param array<string, string> $settings
     */
    private function sidebar(bool $windowCleaning, array $settings = []): string
    {
        $settings['business_type'] = $windowCleaning ? 'window_cleaning' : 'standard';
        $s = new class ($settings) {
            /** @param array<string, string> $settings */
            public function __construct(private readonly array $settings)
            {
            }

            public function getSetting(string $key): string
            {
                return $this->settings[$key] ?? '';
            }
        };
        $currentRoute = new class {
            public function getName(): string
            {
                return 'client/index';
            }
        };
        $translator = new class {
            public function translate(string $id): string
            {
                return '[' . $id . ']';
            }
        };
        $urlGenerator = new class {
            public function generate(string $name): string
            {
                return '/to/' . $name;
            }
        };

        return $this->renderSidebar([
            's' => $s,
            'currentRoute' => $currentRoute,
            'translator' => $translator,
            'urlGenerator' => $urlGenerator,
        ]);
    }

    /**
     * Takes the variables as a plain array, so the view is analysed against what the layout gives it and not against
     * the literal built in sidebar().
     *
     * @param array<string, mixed> $variables
     */
    private function renderSidebar(array $variables): string
    {
        ob_start();
        (static function () use ($variables): void {
            extract($variables);
            require __DIR__ . '/../../../../resources/views/invoice/layout/sidebar.php';
        })();
        return (string) ob_get_clean();
    }

    public function aStandardInstallOffersQuotes(): void
    {
        $html = $this->sidebar(false);

        Assert::true(str_contains($html, 'href="/to/quote/index"'));
        Assert::true(str_contains($html, 'href="/to/client/index"'));
        Assert::true(str_contains($html, 'href="/to/inv/index"'));
    }

    public function aStandardInstallOffersTasksOnlyWhileProjectsAreEnabled(): void
    {
        Assert::true(str_contains($this->sidebar(false, ['projects_enabled' => '1']), 'href="/to/task/index"'));
        Assert::false(str_contains($this->sidebar(false, ['projects_enabled' => '0']), 'href="/to/task/index"'));
    }

    public function aWindowCleaningInstallOffersNeitherQuotesNorTasks(): void
    {
        $html = $this->sidebar(true, ['projects_enabled' => '1']);

        Assert::false(str_contains($html, '/to/quote/index'));
        Assert::false(str_contains($html, '/to/task/index'));
    }

    public function aWindowCleaningInstallKeepsTheRestOfTheSidebar(): void
    {
        $html = $this->sidebar(true, ['projects_enabled' => '1']);

        foreach (['client/index', 'inv/index', 'inv/trash', 'payment/index', 'product/index', 'setting/tabIndex'] as $route) {
            Assert::true(str_contains($html, 'href="/to/' . $route . '"'), $route . ' should still be offered');
        }
    }
}
