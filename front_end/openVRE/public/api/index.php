<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Factory\AppFactory;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

require __DIR__ . "/../../config/bootstrap.php";
require __DIR__ . "/launchTool.php";



$app = AppFactory::create();
$app->setBasePath("/api");
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


$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Welcome to the OpenVRE API!\n");
    return $response;
});


$app->get('/tools/', function (Request $request, Response $response, $args) {
    try {
        $token = getBearerToken($request->getHeaderLine('Authorization'));
    } catch (Exception $e) {
        $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json')
            ->getBody()
            ->write(json_encode(['error' => $e->getMessage()]));

        return $response;
    }

    try {
        validateToken($token);
    } catch (Exception $e) {
        $response
            ->withStatus(403)
            ->withHeader('Content-Type', 'application/json')
            ->getBody()
            ->write(json_encode(['error' => 'Forbidden: ' . $e->getMessage()]));

        return $response;
    }

    $tools = $GLOBALS['toolsCol']->find();
    $payload = json_encode(iterator_to_array($tools));
    $response->getBody()->write($payload);
    $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);

    return $response;
});


$app->post('/jobs/', function (Request $request, Response $response, $args) {
    try {
        $token = getBearerToken($request->getHeaderLine('Authorization'));
    } catch (Exception $e) {
        $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json')
            ->getBody()
            ->write(json_encode(['error' => $e->getMessage()]));

        return $response;
    }

    try {
        $decodedToken = validateToken($token);
        $userEmail = $decodedToken->email;
    } catch (Exception $e) {
        $response
            ->withStatus(403)
            ->withHeader('Content-Type', 'application/json')
            ->getBody()
            ->write(json_encode(['error' => 'Forbidden: ' . $e->getMessage()]));

        return $response;
    }

    $queryParams = $request->getQueryParams();
    try {
        $toolJson = launchTool($queryParams['tool'], $userEmail, $queryParams['project'], $queryParams['input_files']);
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(["Error" => "Error connecting to database: " . $e->getMessage()]));
        $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);

        return $response;
    }

    if ($_SESSION['errorData'] != null) {
        $response->getBody()->write(json_encode(["Error" => $_SESSION['errorData']['Error']])); // Not returning warnings or other type of error data
        $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);

        return $response;
    }

    $payload = json_encode($toolJson);
    $response->getBody()->write($payload);

    $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);

    return $response;
});


$app->run();
