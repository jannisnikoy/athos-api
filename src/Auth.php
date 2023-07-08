<?php

namespace Athos\API;

use \Firebase\JWT\JWT;

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
    * Verifies the token validity and returns the decoded object.
    *
    * @return object Decoded JWT token
    */
    public function checkToken() {
        if (!empty($this->headers['authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $this->headers['authorization'], $matches)) {
                $jwtToken = $matches[1];
            } else {
                http_response_code(403);
                echo json_encode(array('status' => 'FORBIDDEN'));
                exit();
            }
        } else {
            http_response_code(403);
            echo json_encode(array('status' => 'FORBIDDEN'));
            exit();
        }

        $decoded = JWT::decode($jwtToken, new \Firebase\JWT\Key(file_get_contents($this->config->get('jwt_public_key')), 'RS256'));
        
        $this->db->query("SELECT * FROM users WHERE id=?", $decoded->userId);
        if($this->db->hasRows()) {
            $user = $this->db->getRow();
            if($user->active == 0) {
                http_response_code(403);
                echo json_encode(array('status' => 'FORBIDDEN'));
                exit();
            }
        } else {
            http_response_code(404);
            echo json_encode(array('status' => 'NOT_FOUND'));
            exit();
        }

        $this->db->query("UPDATE users SET userAgent=?, lastActiveAt=NOW() WHERE id=?", $_SERVER['HTTP_USER_AGENT'], $decoded->userId);

        return $decoded;
    }

    /**
     * Create a Guest user account and generates a JWT token
     */
    public function createGuestUser() {
        $rand = rand(100000, 10000000);
        $username = 'Guest' . $rand;
        $password = md5(($rand*time()/time().'secret').$username.md5(time().'secret'));

        $this->db->query("INSERT INTO users(username, password) VALUES(?, ?)", $username, $password);
        $userId = $this->db->insertId();

        return $this->getJwtToken($userId);
    }

    /**
    * Attempts to log in the user and generate a JWT token.
    *
    * @param string $username
    * @param string $password
    * @return JWT token if successful, otherwise false.
    */
    public function login(string $username, string $password) {
        $this->db->query("SELECT * FROM users WHERE username=? AND password=? AND active=1", $username, hash('sha256', $password));

        if (!$this->db->hasRows()) {
            return false;
        }

        $row = $this->db->getRow();

        return $this->getJwtToken($row->id);
    }

    //
    // Private methods
    //

    /**
    * Created a new JWT token for the userId provided.
    *
    * @param string userId Id of the user
    * @return object Encoded JWT token
    */
    private function getJwtToken(string $userId) {
        $arClaim['iss'] = $_SERVER['HTTP_HOST'];
        $arClaim['iat'] = time();
        $arClaim['userId'] = $userId;

        $key = file_get_contents($this->config->get('jwt_private_key'));

        return JWT::encode($arClaim, $key, 'RS256');
    }
}
?>
