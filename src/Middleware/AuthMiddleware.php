<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {
        $response = new Response();
        $apiPassword = $_ENV['API_PASSWORD'] ?? '';
        if (empty($apiPassword)) {
            $response->getBody()->write(json_encode([
                'error' => 'API password not configured'
            ]));
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader) || $authHeader !== 'Bearer ' . $apiPassword) {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized - Invalid or missing API key'
            ]));
            return $response
                ->withStatus(401)
                ->withHeader('Content-Type', 'application/json');
        }
        return $handler->handle($request);
    }
}
