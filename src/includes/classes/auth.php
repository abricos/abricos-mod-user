<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

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
            case "login":
                return $this->LoginToAJAX($d->login);
            case "logout":
                return $this->LogoutToAJAX();
        }
        return null;
    }

    private $_usercache = null;

    public function LoginToAJAX($d){
        $res = $this->Login($d->username, $d->password, $d->autologin);

        $ret = $this->manager->TreatResult($res);

        $user = $this->_usercache;
        if ($ret->err === 0 && !empty($user)){
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
    public function Login($username, $password, $remember = false){
        $username = trim($username);
        $password = trim($password);

        if (empty($username) || empty($password)){
            return 3;
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

        $this->LoginMethod($user, $remember);

        return 0;
    }

    public function LoginMethod($user, $remember = false){
        if ($user instanceof UserItem){
            $user = new UserItem_Auth($user);
        }

        $session = $this->manager->GetSessionManager();
        $session->Set('userid', $user->id);

        $guserid = $session->Get('guserid');
        $session->Set('guserid', $user->id);

        // зашел тот же человек, но под другой учеткой
        if ($guserid > 0 && $guserid != $user->id){
            UserQuery_Auth::UserDoubleLogAppend($this->db, $guserid, $user->id, $_SERVER['REMOTE_ADDR']);
        }
        if ($remember){
            // установить куки для автологина
            $privateKey = $session->GetSessionPrivateKey();
            $sessionKey = md5(TIMENOW.$privateKey.cmsrand(1, 1000000));
            setcookie(
                $session->sessionName,
                $sessionKey, TIMENOW + $session->cookieTimeout,
                $session->sessionPath,
                $session->sessionHost
            );
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
        $sessionKey = Abricos::CleanGPC('c', $session->sessionName, TYPE_STR);
        setcookie($session->sessionName, null, -1, $session->sessionPath);
        UserQuery_Session::SessionRemove($this->db, $sessionKey);
        $session->Drop('userid');
    }
}
