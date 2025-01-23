<?php

namespace src\Http;

class Request
{
    protected array $parsedUrl;
    protected array $routeData;
    protected array $get;
    protected array $post;

    public function __construct()
    {
        $this->parsedUrl = parse_url($_SERVER['REQUEST_URI']);
        $parsedUrl = $this->parsedUrl['path'];
        $parsedUrl = substr($parsedUrl, 1);

        if (empty($parsedUrl)) {
            $this->routeData['controller'] = 'Index';
            $this->routeData['action'] = 'index';
        } else {
            $parsedUrl = explode('/', $parsedUrl);
            // Warning! Deprecated as of PHP 8.1.0, use htmlspecialchars() instead.
            $this->routeData['controller'] = ucfirst(strtolower(htmlspecialchars($parsedUrl[0])));

            if (empty($parsedUrl[1])) {
                $this->routeData['action']= 'index';
            } else {
                $this->routeData['action'] = strtolower(htmlspecialchars($parsedUrl[1]));
            }

            if (!empty($_GET) && !empty($_GET['id']) && is_numeric($_GET['id'])) {
                $this->routeData['id'] = abs(intval($_GET['id']));
            }
        }

        return ;
    }

    public function getController()
    {
        return $this->routeData['controller'];
    }

    public function getAction()
    {
        return $this->routeData['action'];
    }

    public function getRouteData()
    {
        return $this->routeData;
    }
}