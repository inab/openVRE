<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Factory\AppFactory;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

require __DIR__ . "/../../config/bootstrap.php";
require __DIR__ . "/launchTool.php";



$app = AppFactory::create();
$app->setBasePath("/api/v1");
$app->addErrorMiddleware(true, true, true);


function getBearerToken($authHeader)
{
    if (empty($authHeader)) {
        throw new Exception('Authorization header not found');
    }

    $matchedBearer = preg_match('/^Bearer\s(\S+)$/', $authHeader, $bearerText);
    if ($matchedBearer === 0) {
        throw new Exception('Bearer authorization header not found');
    }

    if ($matchedBearer === false) {
        throw new Exception('Error parsing authorization header');
    }

    return $bearerText[1];
}


function validateToken(string $token)
{
    $jwksFilePath = '.jwks'; // Obtained from JWKS Endpoint of auth server
    try {
        $jwks = json_decode(file_get_contents($jwksFilePath), true);
        $parsedKeySet = JWK::parseKeySet($jwks);
        return JWT::decode($token, $parsedKeySet);
    } catch (Exception $e) {
        throw new Exception("Invalid token: " . $e->getMessage());
    }
}


function checkAuthorizationToken($request, $response)
{
    try {
        $token = getBearerToken($request->getHeaderLine('Authorization'));
    } catch (Exception $e) {
        $response
            ->getBody()
            ->write(json_encode(['error' => $e->getMessage()]));

        return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
    }

    try {
        validateToken($token);
    } catch (Exception $e) {
        $response
            ->getBody()
            ->write(json_encode(['error' => 'Forbidden: ' . $e->getMessage()]));

        return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);
    }

    return $response;
}


$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Welcome to the OpenVRE API!\n");
    return $response;
});


$app->get('/tools', function (Request $request, Response $response, $args) {
    $response = checkAuthorizationToken($request, $response);
    if ($response->getStatusCode() !== 200) {
        return $response;
    }

    $tools = $GLOBALS['toolsCol']->find();
    $payload = json_encode(iterator_to_array($tools));
    $response->getBody()->write($payload);

    return  $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
});


$app->get('/tools/{id}', function (Request $request, Response $response, $args) {
    $response = checkAuthorizationToken($request, $response);
    if ($response->getStatusCode() !== 200) {
        return $response;
    }

    $options = array('typemap' => ['root' => 'array', 'document' => 'array']);
    $tool = $GLOBALS['toolsCol']->findOne(array('_id' => $args['id']), $options);
    if (empty($tool)) {
        $response->getBody()->write(json_encode(["Error" => "Tool not found"]));
        return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
    }

    $payload = json_encode($tool);
    $response->getBody()->write($payload);

    return  $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
});


$app->post('/jobs', function (Request $request, Response $response, $args) {
    try {
        $token = getBearerToken($request->getHeaderLine('Authorization'));
    } catch (Exception $e) {
        $response
            ->getBody()
            ->write(json_encode(['error' => $e->getMessage()]));

        return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
    }

    try {
        $decodedToken = validateToken($token);
        $userEmail = $decodedToken->email;
    } catch (Exception $e) {
        $response
            ->getBody()
            ->write(json_encode(['error' => 'Forbidden: ' . $e->getMessage()]));

        return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);
    }

    $queryParams = $request->getQueryParams();
    if (empty($queryParams['tool'])) {
        $response->getBody()->write(json_encode(["Error" => "Missing tool id"]));

        return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
    }

    if (empty($queryParams['project'])) {
        $response->getBody()->write(json_encode(["Error" => "Missing project id"]));

        return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
    }

    $parsedBody = $request->getParsedBody();

    if (empty($parsedBody['input_files'])) {
        $response->getBody()->write(json_encode(["Error" => "Missing input files"]));

        return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
    }

    try {
        $toolJson = launchTool($queryParams['tool'], $userEmail, $queryParams['project'], $parsedBody['input_files']);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["Error" => "Error connecting to database: " . $e->getMessage()]));

        return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
        }

    if ($_SESSION['errorData']['Error'] != null) {
        $response->getBody()->write(json_encode(["Error" => $_SESSION['errorData']['Error']]));
        $_SESSION['errorData']['Error'] = null; // Clear the error data from the session to avoid concatenation on futher requests

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
