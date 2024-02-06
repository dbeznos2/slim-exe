<?php
require_once __DIR__ . "/../vendor/autoload.php";

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


// display page
$app->get('/', function (Request $request, Response $response)  use ($pdo) {

    $query = $pdo->prepare("select * from todo");
    $query->execute();
    $todos = $query->fetchAll(PDO::FETCH_ASSOC);

    $view = Twig::fromRequest($request);
    return $view->render($response, 'todoLayout.twig', [
        'allTodo' => $todos
    ]);
})->setName('profile');

//adding input to db
$app->post('/submit', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();
    $task = $data['name'];

    $queryMaxId = $pdo->prepare("select max(ID) from todo" );

    $queryMaxId->execute();

    $maxId = $queryMaxId->fetchColumn();

    $order = $maxId + 1;

    $query = $pdo->prepare("insert into todo (Task, ID) values (?, ?)");

    $query->execute([$task, $order]);

    return $response->withHeader('Location', '/')->withStatus(302);
});

$app->post('/delete', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();
    $task_id = $data['task_id'];

    //delete the task with the specified ID
    $query = $pdo->prepare("delete from todo where ID = ?");
    $query->execute([$task_id]);

    return $response->withHeader('Location', '/')->withStatus(302);
});


$app->post('/edit', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();

    $taskId = $data['task_id'];
    $editedTask = $data['edited_task'];

    $query = $pdo->prepare("update todo set Task = :edited_task where ID = :task_id");
    $query->execute(['edited_task' => $editedTask, 'task_id' => $taskId]);

    return $response->withHeader('Location', '/')->withStatus(302);
});

$app->run();
