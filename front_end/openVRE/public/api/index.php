<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ ."/../../config/bootstrap.php";
require __DIR__ . "/launchTool.php";



$app = AppFactory::create();
$app->setBasePath("/api");
$app->addErrorMiddleware(true, true, true);


$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Welcome to the OpenVRE API!\n");
    return $response;
});


$app->get('/tools/', function (Request $request, Response $response, $args) {
    if(!checkLoggedIn()){
		return $response->withStatus(401);
    }

    $tools = $GLOBALS['toolsCol']->find();
    $payload = json_encode(iterator_to_array($tools));
    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});


$app->post('/jobs/', function (Request $request, Response $response, $args) {
    $queryParams = $request->getQueryParams();
    if ($queryParams['tool'] != "mock_tool") {
        $response->getBody()->write(json_encode(["Error" => ["You should provide a valid tool\n"]]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    }

    if (!filter_var($queryParams['email'], FILTER_VALIDATE_EMAIL)) {
        $response->getBody()->write(json_encode(["Error" => ["You should provide a valid email\n"]]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    }

    try {
        $toolJson = launchTool($queryParams['tool'], $queryParams['email'], $queryParams['project'], $queryParams['input_files']);
    }  catch (MongoDB\Exception\Exception | MongoDB\Driver\Exception\Exception $e) {
        $response->getBody()->write(json_encode(["Error" => "Error connecting to database: " . $e->getMessage()]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["Error" => $e->getMessage()]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }

    if ($_SESSION['errorData'] != null) {
        $response->getBody()->write(json_encode(["Error" => $_SESSION['errorData']['Error']])); // Not returning warnings or other type of error data
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }

    $payload = json_encode($toolJson);
    $response->getBody()->write($payload);

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});


$app->run();
