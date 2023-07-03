<?php

namespace Athos\API;

/**
* Controller
* Main controller implementation to be used in modules
*
* @package  athos-api
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-api
*/

class Controller {
    public $config;
    public $auth;
    public $db;
    public $headers;

    public function __construct() {
        global $config, $auth, $db;

        $this->config = $config;
        $this->auth = $auth;
        $this->db = $db;
        $this->headers = array_change_key_case(getallheaders());

        if (method_exists($this, 'defaultAction')) {
            $this->defaultAction();
        }
    }
}
