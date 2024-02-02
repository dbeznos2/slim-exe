<?php
session_start();
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;


$dsn = "sqlite:../tododata.db";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
$pdo = new PDO($dsn, null,null, $options);


// relative chemin depuis le fichier actuel
require  '../vendor/autoload.php';

$app = AppFactory::create();

$twig = Twig::create(__DIR__ . '/../templates', ['cache' => false]);

$app->add(TwigMiddleware::create($app, $twig));

$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/todo/{id}', function ($request, $response)  use ($pdo) {

    $query = $pdo->prepare("select * from todo");
    $query->execute();
    $row = $query->fetchAll(PDO::FETCH_ASSOC);

    $view = Twig::fromRequest($request);
    return $view->render($response, 'home.twig', [
        'allTodo' => $row
    ]);
})->setName('profile');


$app->get('/', function ($request, $response)  use ($pdo) {

    $query = $pdo->prepare("select * from todo");
    $query->execute();
    $row = $query->fetchAll(PDO::FETCH_ASSOC);

    $view = Twig::fromRequest($request);
    return $view->render($response, 'home.twig', [
        'allTodo' => $row
    ]);
})->setName('profile');

// check if there is a root that I want in url
$app->run();
