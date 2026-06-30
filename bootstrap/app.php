<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Public Path (split deployment: web root parent, Laravel app in src/)
|--------------------------------------------------------------------------
|
| When PUBLIC_PATH is set, or uploads exist at the parent of APP_BASE_PATH,
| bind path.public so uploads and assets resolve to the real web root.
|
*/

$publicPath = $_ENV['PUBLIC_PATH'] ?? null;

if (empty($publicPath)) {
    $basePath = $app->basePath();
    $parentPath = dirname($basePath);

    if ($parentPath !== $basePath
        && is_dir($parentPath.DIRECTORY_SEPARATOR.'uploads')
        && (file_exists($parentPath.DIRECTORY_SEPARATOR.'index.php')
            || file_exists($parentPath.DIRECTORY_SEPARATOR.'.htaccess'))
    ) {
        $publicPath = $parentPath;
    }
}

if (! empty($publicPath)) {
    $publicPath = rtrim($publicPath, '/\\');

    $app->bind('path.public', function () use ($publicPath) {
        return $publicPath;
    });
}

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
