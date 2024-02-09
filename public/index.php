<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once "fetchTodosFunc.php";

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


$app->get('/', function (Request $request, Response $response)  use ($pdo) {
    $view = Twig::fromRequest($request);

    if (isset($_SESSION["error"])) {
        $errorMessage = $_SESSION["error"];
        unset($_SESSION["error"]);
        return $view->render($response, 'todoLayout.twig', [
            'errorMessage' => $errorMessage,
            'allTodo' => fetchTodos($pdo)
        ]);
    }

    if (isset($_SESSION['success'])) {
        $successMessage = $_SESSION['success'];
        unset($_SESSION["success"]);
        return $view->render($response, 'todoLayout.twig', [
            'successMessage' => $successMessage,
            'allTodo' => fetchTodos($pdo)
        ]);
    }

    $query = $pdo->prepare("select * from todo");
    $query->execute();
    $todos = $query->fetchAll(PDO::FETCH_ASSOC);

    return $view->render($response, 'todoLayout.twig', [
        'allTodo' => $todos
    ]);
})->setName('profile');


$app->post('/submit', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();
    $task = $data['name'];

    // messages
    if (mb_strlen($task) < 3 || mb_strlen($task) > 20) {
        $_SESSION["error"] = "Todo should be between 3 and 20 characters long.";
    } else {
        $queryMaxId = $pdo->prepare("select max(ID) from todo" );
        $queryMaxId->execute();
        $maxId = $queryMaxId->fetchColumn();
        $order = $maxId + 1;

        $query = $pdo->prepare("insert into todo (Task, ID) values (?, ?)");
        $query->execute([$task, $order]);

        $_SESSION["success"] = "Todo added successfully.";
    }
    return $response->withHeader('Location', '/')->withStatus(302);
});



$app->post('/delete', function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();
    $task_id = $data['task_id'];

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

$app->get('/sort', function (Request $request, Response $response) use ($pdo) {
    $sortCriteria = $request->getQueryParams()['sortTodo'] ?? 'unset';

    $query = match ($sortCriteria) {
        'alphabetic' => $pdo->prepare("select * from todo order by Task"),
        'nonAlphabetic' => $pdo->prepare("select * from todo order by Task desc"),
        default => $pdo->prepare("select * from todo"),
    };
    $query->execute();
    $view = Twig::fromRequest($request);

    return $view->render($response, 'todoLayout.twig', [
        'allTodo' => $query->fetchAll()
    ]);

});


$app->run();
