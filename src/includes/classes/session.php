<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


require_once 'session_dbquery.php';
require_once 'session_structure.php';

/**
 * Класс работы с сессиями
 *
 * @package Abricos
 */
class UserManager_Session {

    /**
     * Время хранения сессии
     *
     * @var integer
     */
    public $sessionTimeOut = 60 * 60 * 24 * 14;

    public $sessionHost = null;

    public $sessionPath = '/';

    public $sessionName = 'PHPSESSID';

    public $cookieTimeout = 60 * 60 * 24 * 14;

    /**
     * Идентификатор сессии пользователя
     *
     * @var string
     */
    public $key;

    /**
     * @var UserManager
     */
    public $manager;

    public function __construct(UserManager $manager){
        $this->manager = $manager;

        $cfg = isset(Abricos::$config['user'])
            ? Abricos::$config['user']
            : array();

        if (isset($cfg['cookie']['host'])){
            $this->sessionHost = $cfg['cookie']['host'];
        }

        if (isset($cfg['cookie']['path'])){
            $this->sessionPath = $cfg['cookie']['path'];
        }

        if (isset($cfg['cookie']['timeout'])){
            $this->cookieTimeout = intval($cfg['cookie']['timeout']);
        }

        if (isset($cfg['session']['name'])){
            $this->sessionName = ['session']['name'];
        }

        if (isset($cfg['session']['timeout'])){
            $this->sessionTimeOut = intval($cfg['session']['timeout']);
        }

        $this->Start();

        $this->key = session_id();
    }

    public function AJAX($d){
        switch ($d->do){
            case "userCurrent":
                return $this->UserCurrentToAJAX();
        }
        return null;
    }


    public function GetSessionPrivateKey(){
        return md5($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Старт сессии
     */
    public function Start(){
        session_name($this->sessionName);
        session_set_cookie_params(
            $this->sessionTimeOut,
            $this->sessionPath,
            $this->sessionHost
        );

        if (!session_id()){
            if (isset($_COOKIE[$this->sessionName])
                && !is_string($_COOKIE[$this->sessionName])
            ){
                die("Hacking!");
            }

            $aRequest = array_merge($_GET, $_POST);
            if (@ini_get('session.use_only_cookies') === "0"
                && isset($aRequest[$this->sessionName])
                && !is_string($aRequest[$this->sessionName])
            ){
                die("Hacking!");
            }
        }

        /*
        $sessionIDG = Abricos::CleanGPC('g', 'session', TYPE_STR);
        if (!empty($sessionIDG)){
            session_id($sessionIDG);
        }
        /**/

        session_start();
    }

    public function Get($name){
        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    public function GetData(){
        return $_SESSION;
    }

    public function Set($name, $value){
        $_SESSION[$name] = $value;
    }

    public function Drop($name){
        unset($_SESSION[$name]);
    }

    public function DropSession(){
        unset($_SESSION);
        session_destroy();
    }

    /**
     * @return null|UserItem
     */
    public function Update(){
        $db = $this->manager->db;

        $userid = intval($this->Get('userid'));
        $flag = intval($this->Get('flag'));

        if (empty($userid) && empty($flag)){
            // сессия на пользователя не установлена, проверка на автологин
            $sessionKey = Abricos::CleanGPC('c', $this->sessionName, TYPE_STR);
            if (!empty($sessionKey)){
                $privateKey = $this->GetSessionPrivateKey();
                $sessionDB = UserQuery_Session::Session($db, $this->sessionTimeOut, $sessionKey, $privateKey);
                if (!empty($sessionDB)){
                    $userid = intval($sessionDB['userid']);
                }
            }
        }

        $user = null;

        if ($userid > 0){
            $user = $this->manager->User($userid);

            if (empty($user)){ // Гость
                $userid = 0;
                $this->Drop('userid');
            } else {
                if ($user->IsSuperAdmin()){
                    $db->readonly = false;
                }

                UserQuery_Session::UserUpdateLastActive($db, $userid, $_SERVER['REMOTE_ADDR']);

                // TODO: set antibot data
                // $this->AntibotUserDataUpdate($userid);
            }
        }
        $this->Set('userid', $userid);
        $this->Set('flag', 1); // флаг установки сессии

        if (empty($user)){
            $user = new UserItem(array(
                'id' => 0,
                'username' => "Guest",
                'agreement' => 1
            ));
        }

        return $user;
    }

    public function UserCurrentToAJAX(){
        $ret = new stdClass();
        $user = new UserItem_Session(Abricos::$user);
        $ret->userCurrent = $user->ToAJAX();
        return $ret;
    }
}
