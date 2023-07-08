<?php

namespace Athos\API;

/**
* API
* API wrapper that loads and executes the appropriate
* controller methods.
*
* @package  athos-api
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-api
*/

class API {
    private $config;
    private $headers;
    private $apiVersion;

    function __construct() {
        global $config;

        $this->config = $config;
        $this->apiVersion = '1.0';

        if (!$this->hasCorrectHeaders()) {
            exit();
        }
    }

    /**
    * Set the API Version for the requested module
    *
    * @param string $version API version
    */
    public function setApiVersion(string $version) {
        $this->apiVersion = $version;
    }

    /**
    * Load the requested controller with optional action.
    *
    * @param string $name Name of the controller
    * @param string $action Name of a specific action within the controller
    */
    public function loadController(string $name, string $action = null) {
        $controllerDirectory = $this->config->get('modules_dir') . '/v' . $this->apiVersion;

        if (isset($name)) {
            $controllerName = ucfirst($name) . 'Controller';

            $file = $controllerDirectory . '/' . $controllerName . '.php';

            if (!file_exists($file)) {
                http_response_code(404);
                echo json_encode(array('status' => 'INVALID_COMMAND'));
                return;
            }

            include $file;

            $controller = new $controllerName();

            if (isset($action)) {
                $actionName = strtolower($action) . 'Action';

                if (method_exists($controllerName, $actionName)) {
                    echo $controller->$actionName();
                    return;
                }
            }

            $method = strtolower($_SERVER['REQUEST_METHOD']);
            $actionName = $method . 'Action';

            if (method_exists($controllerName, $actionName)) {
                echo $controller->$actionName();
                return;
            }

            http_response_code(404);
            echo json_encode(array('status' => 'INVALID_ACTION'));
        }
    }

    /**
    * Checks if the API request was made with the correct accept header.
    * Returns true if $config['require_accept_header'] is false.
    *
    * @return bool true if headers are present, or not required.
    */
    private function hasCorrectHeaders(): bool {
        header('Content-Type: application/json');
        $this->headers = array_change_key_case(getallheaders());

        $requireAcceptHeader = $this->config->get('require_accept_header');

        if(!isset($requireAcceptHeader) || (isset($requireAcceptHeader) && $requireAcceptHeader == false)) {
            return true;
        }

        if (isset($this->headers['accept'])) {
            if ($this->headers['accept'] != $this->config->get('accept_header')) {
                http_response_code(403);
                echo json_encode(array('status' => 'FORBIDDEN'));
                return false;
            }
        } else {
            http_response_code(403);
            echo json_encode(array('status' => 'FORBIDDEN'));
            return false;
        }

        return true;
    }
}
?>
