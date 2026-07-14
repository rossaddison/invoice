<?php

declare(strict_types=1);

namespace App\Command\Router;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Yii\Console\ExitCode;

final class ListCommand extends Command
{
    protected static string $defaultName = 'router/list';

    public function __construct(private readonly RouteCollectionInterface $routeCollection)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('List all registered routes')
            ->setHelp('This command displays a list of registered routes.')
            ->addOption(
                'controller',
                null,
                InputOption::VALUE_OPTIONAL,
                'Add a Controller column showing the ControllerClass::method handling each route. '
                    . 'Pass a value (e.g. --controller=Inv) to also filter to routes whose controller '
                    . 'class name starts with it (case-insensitive).',
                false,
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // false  => option not passed at all
        // null   => --controller passed with no value
        // string => --controller=<filter> passed
        /** @var string|false|null $controllerOption */
        $controllerOption = $input->getOption('controller');
        $showController = $controllerOption !== false;
        $filter = is_string($controllerOption) && $controllerOption !== '' ? $controllerOption : null;

        $table = new Table($output);
        $routes = $this->routeCollection->getRoutes();
        uasort(
            $routes,
            static fn (Route $a, Route $b) => ($a->getData('host') <=> $b->getData('host')) ?: ($a->getData('name') <=> $b->getData('name')),
        );

        if ($filter !== null) {
            $routes = array_filter(
                $routes,
                fn (Route $route): bool => stripos($this->controllerShortName($route), $filter) === 0,
            );
        }
        $routes = array_values($routes);

        $headers = ['Host', 'Methods', 'Name', 'Pattern', 'Defaults'];
        if ($showController) {
            $headers[] = 'Controller';
        }
        $table->setHeaders($headers);

        $lastIndex = count($routes) - 1;
        foreach ($routes as $index => $route) {
            $row = [
                $route->getData('host'),
                implode(',', $route->getData('methods')),
                $route->getData('name'),
                $route->getData('pattern'),
                implode(',', $route->getData('defaults')),
            ];
            if ($showController) {
                $row[] = $this->controllerLabel($route);
            }
            $table->addRow($row);
            if ($index < $lastIndex) {
                $table->addRow(new TableSeparator());
            }
        }

        $table->setColumnMaxWidth(2, 15);
        $table->setColumnMaxWidth(3, 15);
        $table->render();

        return ExitCode::OK;
    }

    /**
     * The controller action is always the last middleware definition appended
     * via Route::action() — see Yiisoft\Router\Route::prependMiddleware(),
     * which requires action() to already have been called.
     */
    private function controllerLabel(Route $route): string
    {
        $shortName = $this->controllerShortName($route);
        if ($shortName === '') {
            return '';
        }

        $action = $this->actionDefinition($route);
        return isset($action[1]) && is_string($action[1])
            ? $shortName . '::' . $action[1]
            : $shortName;
    }

    /**
     * Just the controller's short class name (no method), used both for
     * display and for --controller=<filter> matching.
     */
    private function controllerShortName(Route $route): string
    {
        $action = $this->actionDefinition($route);
        if (!isset($action[0]) || !is_string($action[0])) {
            return '';
        }

        $class = $action[0];
        $shortName = strrchr($class, '\\');
        return $shortName !== false ? substr($shortName, 1) : $class;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function actionDefinition(Route $route): ?array
    {
        $middlewares = $route->getData('enabledMiddlewares');
        if ($middlewares === []) {
            return null;
        }

        $action = end($middlewares);
        return is_array($action) ? $action : null;
    }
}
