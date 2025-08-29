<?php
use Slim\Factory\AppFactory;
use DI\Container;
use App\Middleware\AuthMiddleware;
use App\Controllers\AccountController;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->add(new AuthMiddleware());
$app->get('/api/balance', [AccountController::class, 'getBalance']);
$app->get('/api/transactions', [AccountController::class, 'getTransactions']);
$app->run();
