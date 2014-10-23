<?php

/**
 * Class UserAuthManager
 */
class UserAuthManager {

    /**
     * @var User
     */
    public $module;

    /**
     * @var UserManager
     */
    public $manager;

    /**
     * @var Ab_Database
     */
    public $db;

    public function __construct(UserManager $manager) {
        $this->module = $manager->module;
        $this->manager = $manager;
        $this->db = $manager->db;
    }

    public function AJAX($d) {
        switch ($d->do) {
            case "login":
                return $this->LoginToAJAX($d->savedata);
            case "logout":
                return $this->LogoutToAJAX();
        }
        return null;
    }

    private $_usercache = null;

    public function LoginToAJAX($d) {
        $res = $this->Login($d->username, $d->password, $d->autologin);

        $ret = $this->manager->TreatResult($res);

        $user = $this->_usercache;
        if ($ret->err === 0 && !empty($user)) {
            $ret->user = array(
                "id" => $user['userid'],
                "agr" => $user['agreement']
            );
        }

        return $ret;
    }

    /**
     * Проверить данные авторизации и вернуть номер ошибки:
     * 0 - нет ошибки,
     * 1 - ошибка в имени пользователя,
     * 2 - неверное имя пользователя или пароль,
     * 3 - не заполнены обязательные поля,
     * 4 - пользователь заблокирован,
     * 5 - пользователь не прошел верификацию email
     *
     * @param String $username имя пользователя или емайл
     * @param String $password пароль
     * @return Integer
     */
    public function Login($username, $password, $autologin = false) {
        $username = trim($username);
        $password = trim($password);

        if (empty($username) || empty($password)) {
            return 3;
        }

        $user = UserQuery::UserByName($this->db, $username, true);

        if (empty($user)) {
            return 2;
        }
        $this->_usercache = $user;

        if ($user['emailconfirm'] < 1) {
            return 5;
        }

        $passcrypt = UserManager::UserPasswordCrypt($password, $user["salt"]);
        if ($passcrypt != $user["password"]) {
            return 2;
        }

        $this->LoginMethod($user, $autologin);

        return 0;
    }

    public function LoginMethod($user, $autologin = false) {
        $session = $this->module->session;
        $session->Set('userid', $user['userid']);

        $guserid = $session->Get('guserid');
        $session->Set('guserid', $user['userid']);

        // зашел тот же человек, но под другой учеткой
        if ($guserid > 0 && $guserid != $user['userid']) {
            UserQueryExt::UserDoubleLogAppend($this->db, $guserid, $user['userid'], $_SERVER['REMOTE_ADDR']);
        }
        if ($autologin) {
            // установить куки для автологина
            $privateKey = $this->module->GetSessionPrivateKey();
            $sessionKey = md5(TIMENOW.$privateKey.cmsrand(1, 1000000));
            setcookie($session->cookieName, $sessionKey, TIMENOW + $session->sessionTimeOut, $session->sessionPath);
            UserQuery::SessionAppend($this->db, $user['userid'], $sessionKey, $privateKey);
        }

        // Удалить пользователей не прошедших верификацию email (редкая операция)
        UserQueryExt::RegistrationActivateClear($this->db);

        $this->manager->UserDomainUpdate($user['userid']);
    }

    public function LogoutToAJAX() {
        $this->Logout();
        $ret = new stdClass();
        $ret->err = 0;
        return $ret;
    }

    public function Logout() {
        $session = $this->module->session;
        $sessionKey = Abricos::CleanGPC('c', $session->cookieName, TYPE_STR);
        setcookie($session->cookieName, '', TIMENOW, $session->sessionPath);
        UserQuery::SessionRemove($this->db, $sessionKey);
        $this->module->session->Drop('userid');
        $this->module->info = array(
            "userid" => 0,
            "group" => array(1),
            "username" => "Guest"
        );
    }


}

?>