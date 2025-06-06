<?php


error_reporting(E_ERROR | E_PARSE);

define('LARAVEL_START', microtime(true));

require_once __DIR__ . '/../autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';

$app->register(new class($app) extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        config([
            'logging.channels.null' => [
                'driver' => 'monolog',
                'handler' => \Monolog\Handler\NullHandler::class,
            ],
            'logging.default' => 'null',
        ]);
    }
});

class LaravelVsCode
{
    public static function relativePath($path)
    {
        if (!str_contains($path, base_path())) {
            return (string) $path;
        }

        return ltrim(str_replace(base_path(), '', realpath($path)), DIRECTORY_SEPARATOR);
    }
}

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo '__VSCODE_LARAVEL_START_OUTPUT__';

function vsCodeGetRouterReflection(\Illuminate\Routing\Route $route)
{
    if ($route->getActionName() === 'Closure') {
        return new \ReflectionFunction($route->getAction()['uses']);
    }

    if (!str_contains($route->getActionName(), '@')) {
        return new \ReflectionClass($route->getActionName());
    }

    try {
        return new \ReflectionMethod($route->getControllerClass(), $route->getActionMethod());
    } catch (\Throwable $e) {
        $namespace = app(\Illuminate\Routing\UrlGenerator::class)->getRootControllerNamespace()
            ?? (app()->getNamespace() . 'Http\Controllers');

        return new \ReflectionMethod(
            $namespace . '\\' . ltrim($route->getControllerClass(), '\\'),
            $route->getActionMethod(),
        );
    }
}

echo collect(app('router')->getRoutes()->getRoutes())
    ->map(function (\Illuminate\Routing\Route $route) {
        try {
            $reflection = vsCodeGetRouterReflection($route);
        } catch (\Throwable $e) {
            $reflection = null;
        }

        return [
            'method' => collect($route->methods())->filter(function ($method) {
                return $method !== 'HEAD';
            })->implode('|'),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
            'parameters' => $route->parameterNames(),
            'filename' => $reflection ? $reflection->getFileName() : null,
            'line' => $reflection ? $reflection->getStartLine() : null,
        ];
    })
    ->toJson();

echo '__VSCODE_LARAVEL_END_OUTPUT__';

exit(0);
