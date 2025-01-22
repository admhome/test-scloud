<?php

namespace src;

use ReflectionMethod;
use src\Http\Request;

class App
{
    public $connection;

    public function run(): void
    {
        // .env
        $env = new Env();
        $env->load();

        // database
        $dsn = sprintf(
            '%s:host=%s;dbname=%s;charset=UTF8',
            getenv('DATABASE_TYPE'),
            getenv('DATABASE_HOST'),
            getenv('DATABASE_NAME')
        );

        try {
            $this->connection = new \PDO($dsn, getenv('DATABASE_USER'), getenv('DATABASE_PASS'));
        } catch (\PDOException $e) {
            exit($e->getMessage());
        }

        // request
        $request = new Request();
        $controllerName = '\src\Controller\\'.$request->getController();
        $actionName = $request->getAction();

        if (!class_exists($controllerName)) {
            throw new \Exception('Controller '.$request->getController().' not found');
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $actionName)) {
            throw new \Exception('Action '.$request->getAction().' not found');
        }

        $reflection = new ReflectionMethod($controller, $actionName);
        $methodParams = null ?? $reflection->getParameters();

        if (empty($methodParams)) {
            $controller->$actionName();
        } else {
            $params = [];
            $routeData = $request->getRouteData();

            foreach ($methodParams as $param) {
                $params[] = null ?? $routeData[$param->getName()];
            }

            call_user_func([$controller, $actionName], $params);
        }
    }
}
