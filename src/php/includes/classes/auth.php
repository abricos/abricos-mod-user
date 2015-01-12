<?php

require_once 'auth_structure.php';
require_once 'auth_dbquery.php';


/**
 * Class UserManager_Auth
 */
class UserManager_Auth {

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

    public function __construct(UserManager $manager){
        $this->module = $manager->module;
        $this->manager = $manager;
        $this->db = $manager->db;
    }

    public function AJAX($d){
        switch ($d->do){
            case "auth":
                $d->authData = isset($d->authData) ? $d->authData : new stdClass();
                return $this->LoginToAJAX($d->authData);
            case "logout":
                return $this->LogoutToAJAX();
        }
        return null;
    }

    public function LoginToAJAX($d){

        $d->username = isset($d->username) ? $d->username : '';
        $d->password = isset($d->password) ? $d->password : '';
        $d->autologin = isset($d->autologin) ? $d->autologin : false;

        $res = $this->Login($d->username, $d->password, $d->autologin);

        $ret = $this->manager->TreatResult($res);

        if ($ret->err !== 0){
            return $ret;
        }

        $retUser = $this->manager->GetSessionManager()->UserCurrentToAJAX();
        $ret->userCurrent = $retUser->userCurrent;

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
    public function Login($username, $password, $autologin = false){
        $username = trim($username);
        $password = trim($password);

        if (empty($username) || empty($password)){
            return 3;
        }

        if ((strpos($username, '@') > 0 && !UserManager::EmailValidate($username))
            || !UserManager::UserNameValidate($username)
        ){
            return 1;
        }

        $user = $this->manager->UserByName($username, true);

        if (empty($user)){
            return 2;
        }
        $user = new UserItem_Auth($user);

        if (!$user->emailconfirm){
            return 5;
        }

        $passcrypt = UserManager::UserPasswordCrypt($password, $user->salt);
        if ($passcrypt != $user->password){
            return 2;
        }

        $this->LoginMethod($user, $autologin);

        return 0;
    }

    private function LoginMethod($user, $autologin = false){
        if ($user instanceof UserItem){
            $user = new UserItem_Auth($user);
        }

        Abricos::$user = $user;

        $session = $this->manager->GetSessionManager();
        $session->Set('userid', $user->id);

        $guserid = $session->Get('guserid');
        $session->Set('guserid', $user->id);

        // зашел тот же человек, но под другой учеткой
        if ($guserid > 0 && $guserid != $user->id){
            UserQuery_Auth::UserDoubleLogAppend($this->db, $guserid, $user->id, $_SERVER['REMOTE_ADDR']);
        }
        if ($autologin){
            // установить куки для автологина
            $privateKey = $session->GetSessionPrivateKey();
            $sessionKey = md5(TIMENOW.$privateKey.cmsrand(1, 1000000));
            setcookie($session->cookieName, $sessionKey, TIMENOW + $session->sessionTimeOut, $session->sessionPath);
            UserQuery_Session::SessionAppend($this->db, $user->id, $sessionKey, $privateKey);
        }

        $this->manager->GetRegistrationManager();

        // Удалить пользователей не прошедших верификацию email (редкая операция)
        UserQuery_Register::RegistrationActivateClear($this->db);

        $this->manager->UserDomainUpdate($user->id);
    }

    public function LogoutToAJAX(){
        $this->Logout();
        $ret = new stdClass();
        $ret->err = 0;
        return $ret;
    }

    public function Logout(){

        $session = $this->manager->GetSessionManager();
        $sessionKey = Abricos::CleanGPC('c', $session->cookieName, TYPE_STR);
        setcookie($session->cookieName, '', TIMENOW, $session->sessionPath);
        UserQuery_Session::SessionRemove($this->db, $sessionKey);
        $session->Drop('userid');

        // $this->module->info = array("userid" => 0, "group" => array(1), "username" => "Guest");
    }


}

?>