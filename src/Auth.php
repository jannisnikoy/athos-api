<?php

namespace Athos\API;

/**
* Authentication
* Provides basic session-based authentication.
*
* @package  athos-api
* @author   Jannis Nikoy <info@mobles.nl>
* @license  MIT
* @link     https://github.com/jannisnikoy/athos-api
*/

class Auth {
    private $db;
    private $headers;
    private $config;

    function __construct() {
        global $db, $config;

        $this->db = $db;
        $this->config = $config;
        $this->headers = array_change_key_case(getallheaders());
    }

    /**
    * Verifies the incoming request has a valid session. Finding a match with the 
    * provided X-Session-Id and mapping it to an existing user.
    *
    * @return object userId
    */
    public function checkSession(){
        if (!isset($this->headers["x-session-id"])) {
            http_response_code(403);
            echo json_encode(array('status' => 'INVALID_SESSION'));
            exit();
        }

        $sessionId = $this->headers["x-session-id"];

        $this->db->query("SELECT user_id FROM api_sessions WHERE session_id=?", $sessionId);

        if (!$this->db->hasRows()) {
            http_response_code(403);
            echo json_encode(array('status' => 'INVALID_SESSION'));
            exit();
        }

        $row = $this->db->getRow();
        $this->db->query("UPDATE users SET lastActiveAt=current_timestamp() WHERE id=?", $row->user_id);
        $this->db->query("UPDATE api_sessions SET user_agent=? WHERE session_id=?", $_SERVER['HTTP_USER_AGENT'], $sessionId);
        return $row->user_id;
    }
}
?>
