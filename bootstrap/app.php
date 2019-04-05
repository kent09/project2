<?php

require_once __DIR__.'/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__.'/../')
);

$app->withFacades();

$app->withEloquent();

$app->configure('cors');

$app->configure('app');

$app->configure('paypal');

$app->configure('services');

$app->configure('swagger-lume');

$app->configure('mail');

$app->configure('queue');

$app->configure('hashids');

$app->configure('signatures');

$app->withFacades([
        Laravel\Socialite\Facades\Socialite::class => 'Socialite',
        Intervention\Image\Image::class => 'Image',
    ]);
/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

#custom
$app->singleton(\App\Contracts\Task\TaskInterface::class, \App\Repository\Task\TaskRepository::class);
$app->singleton(\App\Contracts\Bank\BankInterface::class, \App\Repository\Bank\BankRepository::class);
$app->singleton(\App\Contracts\Manager\BankInterface::class, \App\Repository\Manager\BankRepository::class);
$app->singleton(\App\Contracts\Profile\ProfileInterface::class, \App\Repository\Profile\ProfileRepository::class);
$app->singleton(\App\Contracts\AuthInterface::class, \App\Repository\AuthRepository::class);
$app->singleton(\App\Contracts\Manager\User\UserInterface::class, \App\Repository\Manager\User\UserRepository::class);
$app->singleton(\App\Contracts\Wizard\WizardInterface::class, \App\Repository\Wizard\WizardRepository::class);
$app->singleton(\App\Contracts\LeaderBoardInterface::class, \App\Repository\LeaderBoardRepository::class);
$app->singleton(\App\Contracts\NotificationInterface::class, \App\Repository\NotificationRepository::class);
$app->singleton(\App\Contracts\Manager\SocialInterface::class, \App\Repository\Manager\SocialRepository::class);
$app->singleton(\App\Contracts\User\UserInterface::class, \App\Repository\User\UserRepository::class);
$app->singleton(\App\Contracts\AnnouncementInterface::class, \App\Repository\AnnouncementRepository::class);
$app->singleton(\App\Contracts\VoteInterface::class, \App\Repository\VoteRepository::class);
$app->singleton(\App\Contracts\Manager\ReferralInterface::class, \App\Repository\Manager\ReferralRepository::class);
$app->singleton(\App\Contracts\ChatInterface::class, \App\Repository\ChatRepository::class);
$app->singleton(\App\Contracts\BlogInterface::class, \App\Repository\BlogRepository::class);
$app->singleton(\App\Contracts\Membership\MembershipInterface::class, \App\Repository\Membership\MembershipRepository::class);
$app->singleton(\App\Contracts\Bot\BotInterface::class, \App\Repository\Bot\BotRepository::class);
$app->singleton(\App\Contracts\Bank\Membership\MembershipInterface::class, \App\Repository\Bank\Membership\MembershipRepository::class);
#utility
$app->singleton('carbon', function() {
   return new \Carbon\Carbon;
});

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//    App\Http\Middleware\ExampleMiddleware::class
// ]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'jwt' => \App\Http\Middleware\JWTTokenMiddleware::class,
    'wizard' => \App\Http\Middleware\WizardMiddleware::class,
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    'cors' => \Barryvdh\Cors\HandleCors::class,
    'permissions' => \App\Http\Middleware\Permissions::class,
    'roles' => \App\Http\Middleware\Roles::class,
]);
$app->middleware([
    // ...
    \Barryvdh\Cors\HandleCors::class,
]);
/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(Flipbox\LumenGenerator\LumenGeneratorServiceProvider::class);
$app->register(\Laravel\Socialite\SocialiteServiceProvider::class);
$app->register(\SwaggerLume\ServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);
$app->register(Intervention\Image\ImageServiceProvider::class);
$app->register(Jenssegers\Agent\AgentServiceProvider::class);
$app->register(LaravelHashids\Providers\LumenServiceProvider::class);
$app->register(SMSkin\LumenMake\LumenMakeServiceProvider::class);
$app->register(SMSkin\LumenMake\Providers\FormRequestServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Intervention\Image\ImageServiceProvider::class);
$app->register(Barryvdh\Cors\ServiceProvider::class);
#custom provider
/*add as singleton not provider for faster booting*/

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
