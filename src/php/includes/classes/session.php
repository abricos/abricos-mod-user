<?php

require_once 'session_dbquery.php';
require_once 'session_structure.php';

/**
 * Класс работы с сессиями
 *
 * @package Abricos
 */
class UserManager_Session {

    /**
     * Время хранения сесси
     *
     * @var integer
     */
    public $sessionTimeOut = 1209600; // 86400*14

    public $sessionHost = null;

    public $sessionPath = '/';

    private $phpSessionName = 'PHPSESSID';

    public $cookieName = 'skey';

    /**
     * Идентификатор сессии пользователя
     *
     * @var string
     */
    public $sessionHash = '';

    /**
     * Идентификатор PHP сессии
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

        $cfg = &Abricos::$config['session'];

        if (isset($cfg['phpname'])){
            $this->phpSessionName = $cfg['phpname'];
        }

        if (isset($cfg['timeout'])){
            $this->sessionTimeOut = $cfg['timeout'];
        }

        if (isset($cfg['host'])){
            $this->sessionHost = $cfg['host'];
        }

        if (isset($cfg['path'])){
            $this->sessionPath = $cfg['path'];
        }

        $cookiePrefix = '';
        if (isset($cfg['cookie_prefix'])){
            $cookiePrefix = $cfg['cookie_prefix'];
        }

        $cookieName = 'skey';
        if (isset($cfg['cookie_name'])){
            $cookieName = $cfg['cookie_name'];
        }
        $this->cookieName = $cookiePrefix.$cookieName;

        $this->Start();

        $this->key = session_id();
    }

    private function ParseRequestHeaders(){

        if (function_exists('getallheaders')){
            return getallheaders();
        }

        $headers = array();
        foreach ($_SERVER as $key => $value){
            if (substr($key, 0, 5) <> 'HTTP_'){
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        return $headers;
    }

    public function GetRequestHeader($hName){
        $headers = $this->ParseRequestHeaders();
        foreach ($headers as $key => $value){
            if (strtolower($hName) === strtolower($key)){
                return $value;
            }
        }
    }

    public function GetSessionPrivateKey(){
        return md5($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Старт сессии
     */
    public function Start(){
        $sessionIDG = Abricos::CleanGPC('g', 'session', TYPE_STR);
        if (!empty($sessionIDG)){
            session_id($sessionIDG);
        } else {
            $hSession = $this->GetRequestHeader('Authorization');
            if (!empty($hSession)){
                $aSession = explode(" ", $hSession);
                if (count($aSession) === 2 && strtolower($aSession[0]) === 'session'){
                    session_id($aSession[1]);

                }
            }
        }

        session_name($this->phpSessionName);
        session_set_cookie_params($this->sessionTimeOut, $this->sessionPath, $this->sessionHost);
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

        $userid = $this->Get('userid');
        $flag = $this->Get('flag');

        if (empty($userid) && empty($flag)){
            // сессия на пользователя не установлена, проверка на автологин
            $sessionKey = Abricos::CleanGPC('c', $this->cookieName, TYPE_STR);
            if (!empty($sessionKey)){
                $privateKey = $this->GetSessionPrivateKey();
                $sessionDB = UserQuery_Session::Session($db, $this->sessionTimeOut, $sessionKey, $privateKey);
                if (!empty($sessionDB)){
                    $userid = $sessionDB['userid'];
                }
            }
        }

        $user = null;

        if ($userid > 0){
            $user = $this->manager->User($userid);

            if (empty($user)){ // Гость
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

}

?>