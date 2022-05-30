<?php

namespace RTF;

class Auth extends Base {

    public function __construct() {
        session_start();
    }

    public function __invoke() {
        $this->exitIfNoAccess();
    }

    public function exitIfNoAccess() {
        if (!$this->hasAccess()) {
            http_response_code(401);
            die();
        }
    }

    public function hasAccess() {
        return $this->isLoggedIn(); // here be the place for user roles etc.
    }

    public function isLoggedIn($method = 'session') {
        switch ($method) {
            case 'session':
                return empty($_SESSION['user_id']) ? $this->checkCookieLogin() : true;
                break;
        }
        return false;
    }

    public function checkCookieLogin() {
        $cookieLoginSession = $_COOKIE['login_session'];
        if (!empty($cookieLoginSession)) {
            $dbUser = $this->db->getByLoginSessionHash('users', sha1($_COOKIE['login_session']));
            if ($dbUser) {
                $this->createSession($dbUser['id']);
                return true;
            }
        }
        return false;
    }

    public function login($username, $password, $method = 'session') {

        switch ($method) {
            case 'session':
                return $this->loginSession($username, $password);
            case 'token':
                return $this->loginToken($username, $password);
        }

    }

    public function loginSession($username, $password) {
        if ($this->isLoggedIn('session')) {
            return true;
        }

        // get user from db
        $user = $this->db->getByUsername('users', $username);
        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                return $this->createSession($user['id']);
            }
        }

        return false;
    }

    /**
     * Generate token on successful login.
     * Token = JSON with
     * - user id
     * - expires timestamp
     * Signed with config/auth/token/private_key
     * A bit like JWT, but simpler.
     * @param $username
     * @param $password
     *
     * @return mixed token or false
     */
    public function loginToken($username, $password) {

        $user = $this->db->getByUsername('users', $username);
        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                return $this->createToken($user['id']);
            }
        }
    }

    public function logout() {
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', 1,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
        session_destroy();
        setcookie('login_session', '', 1);
    }

    public function createSession($userId, $rememberMe = true) {
        $_SESSION['user_id'] = $userId;
        if ($rememberMe) {
            $loginSession = sha1(uniqid('', true) . $userId);
            $res = $this->db->update('users', ['login_session_hash' => sha1($loginSession)], ['id' => $userId]);
            if ($res) {
                return setcookie("login_session", $loginSession, time() + 60*60*24*365); // one year
            }

        }
        return true;
    }

    public function createToken($userId) {
        $data = [
            'user_id' => $userId,
            'expires' => time() + $this->config("auth/token/expires") // expires in 1 month
        ];
    }

    public function encodeToken($data) {
        $json = json_encode($data);
        $signature = $this->getTokenSignature($json);
        return base64_encode($json . "." . $signature);
    }

    public function getTokenSignature($data) {
        return empty($this->config("auth/token/key")) ? false: hash_hmac("sha3-512", $data, $this->config("auth/token/key"));
    }
}