<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classes/structure.php';
require_once 'dbquery.php';

/**
 * Менеджер пользователей
 *
 * @package Abricos
 */
class UserManager extends Ab_ModuleManager {

    /**
     * Модуль
     *
     * @var UserModule
     */
    public $module = null;

    public function __construct(UserModule $module){
        parent::__construct($module);
    }

    /**
     * Имеет ли пользователь доступ к административным функциям.
     *
     * @return boolean
     */
    public function IsAdminRole(){
        return $this->IsRoleEnable(UserAction::USER_ADMIN);
    }

    /**
     * Имеет ли пользователь полный доступ к профилю пользователя
     *
     * @param integer $userid
     * @return boolean
     */
    public function IsChangeUserRole($userid){
        return (Abricos::$user->id > 0 && Abricos::$user->id === intval($userid)) || $this->IsAdminRole();
    }

    private $_sessionManager = null;

    /**
     * @return UserManager_Session
     */
    public function GetSessionManager(){
        if (empty($this->_sessionManager)){
            require_once 'classes/session.php';
            $this->_sessionManager = new UserManager_Session($this);
        }
        return $this->_sessionManager;
    }

    private $_registrationManager = null;

    /**
     * Получить менеджер регистрации пользователя
     *
     * @return UserManager_Registration
     */
    public function GetRegistrationManager(){
        if (empty($this->_registrationManager)){
            require_once 'classes/register.php';
            $this->_registrationManager = new UserManager_Registration($this);
        }
        return $this->_registrationManager;
    }

    private $_authManager = null;

    /**
     * Получить менеджер авторизации
     *
     * @return UserManager_Auth
     */
    public function GetAuthManager(){
        if (empty($this->_authManager)){
            require_once 'classes/auth.php';
            $this->_authManager = new UserManager_Auth($this);
        }
        return $this->_authManager;
    }

    private $_passwordManager = null;

    /**
     * @return UserManager_Password
     */
    public function GetPasswordManager(){
        if (empty($this->_passwordManager)){
            require_once 'classes/password.php';
            $this->_passwordManager = new UserManager_Password($this);
        }
        return $this->_passwordManager;
    }

    private $_adminManager = null;

    /**
     * @return UserManager_Admin
     */
    public function GetAdminManager(){
        if (empty($this->_adminManager)){
            require_once 'classes/admin.php';
            $this->_adminManager = new UserManager_Admin($this);
        }
        return $this->_adminManager;
    }

    private $_personalManager = null;

    /**
     * @return UserManager_Personal
     */
    public function GetPersonalManager(){
        if (empty($this->_personalManager)){
            require_once 'classes/personal.php';
            $this->_personalManager = new UserManager_Personal($this);
        }
        return $this->_personalManager;
    }

    public function TreatResult($res){
        $ret = new stdClass();
        $ret->err = 0;

        if (is_integer($res)){
            $ret->err = $res;
        } else if (is_object($res)){
            $ret = $res;
        }
        $ret->err = intval($ret->err);

        return $ret;
    }

    public function AJAX($d){
        $ret = $this->GetSessionManager()->AJAX($d);

        if (empty($ret)){
            $ret = $this->GetPersonalManager()->AJAX($d);
        }

        if (empty($ret)){
            $ret = $this->GetAuthManager()->AJAX($d);
        }

        if (empty($ret)){
            $ret = $this->GetRegistrationManager()->AJAX($d);
        }

        if (empty($ret)){
            $ret = $this->GetPasswordManager()->AJAX($d);
        }

        if (empty($ret)){
            $ret = $this->GetAdminManager()->AJAX($d);
        }

        if (empty($ret)){
            $ret = new stdClass();
            $ret->err = 500;
        }

        return $ret;
    }

    private $_cacheUser = array();

    public function CacheUserClear(){
        $this->_cacheUser = array();
    }

    public function CacheUser($userid, $type){
        if (!array_key_exists($type, $this->_cacheUser)){
            $this->_cacheUser[$type] = array();
        }
        if (!array_key_exists($userid, $this->_cacheUser[$type])){
            return null;
        }
        return $this->_cacheUser[$type][$userid];
    }

    public function CacheUserAdd($user){
        $this->_cacheUser[$user->GetType()][$user->id] = $user;
    }

    /**
     * @param int $userid
     * @param null $classUserItem
     *
     * @return null|UserItem
     */
    public function User($userid = 0, $classUserItem = null){
        $userid = intval($userid);
        $user = $this->CacheUser($userid, 'user');
        if (!empty($user)){
            if (!empty($classUserItem)){
                // TODO: hack
                $user = new $classUserItem($user);
                $tUser = $this->CacheUser($userid, $user->GetType());
                if (!empty($tUser)){
                    return $tUser;
                }
            }

            return $user;
        }

        $row = UserQuery::UserById($this->db, $userid);
        if (empty($row)){
            return null;
        }

        $user = new UserItem($row);
        $this->CacheUserAdd($user, $user->GetType());

        if (!empty($classUserItem)){
            $user = new $classUserItem($user);
            $this->CacheUserAdd($user, $user->GetType());
        }

        return $user;
    }

    public function UserByName($username, $checkEmail = false, $classUserItem = null){
        $row = UserQuery::UserByName($this->db, $username, $checkEmail);

        if (empty($row)){
            return null;
        }

        $user = new UserItem($row);
        $this->CacheUserAdd($user, $user->GetType());

        if (!empty($classUserItem)){
            $user = new $classUserItem($user);
            $this->CacheUserAdd($user, $user->GetType());
        }

        return $user;
    }

    public function UserExist($useNameOrEmail, $checkEmail = true){
        $user = $this->UserByName($useNameOrEmail, $checkEmail);

        return !empty($user);
    }

    public function UserDomainUpdate($userid = 0){
        // не обновлять, если в конфиге домен не определен
        if (empty(Abricos::$DOMAIN)){
            return;
        }
        if ($userid === 0){
            $userid = Abricos::$user->id;
        }

        UserQuery::UserDomainUpdate($this->db, $userid, Abricos::$DOMAIN);
    }



    ////////////////////////////////////////////////////////////////////
    //                      Административные функции                  //
    ////////////////////////////////////////////////////////////////////


    public function UserGroupList($page = 1, $limit = 15, $filter = ''){
        if (!$this->IsAdminRole()){
            return null;
        }

        $modAntibot = Abricos::GetModule('antibot');
        return UserQuery::UserGroupList($this->db, $page, $limit, $filter, !empty($modAntibot));
    }

    public function UserInfo($userid){
        if (!$this->IsChangeUserRole($userid)){
            $user = UserQuery::UserPublicInfo($this->db, $userid, true);
        } else {
            $user = UserQuery::UserPrivateInfo($this->db, $userid, true);
        }
        if (empty($user)){
            return array('id' => $userid);
        }
        $groups = UserQuery::GroupByUserId($this->db, $userid);
        $user['gp'] = implode(",", $groups);

        return $user;
    }

    public function UserPasswordChange($userid, $newpassword, $oldpassword = ''){
        if (!$this->IsChangeUserRole($userid)){
            return 1; // нет доступа на изменение пароля
        }

        $user = UserQuery::UserById($this->db, $userid);

        // данные для внесения в бд
        $data = array();

        if ($this->IsAdminRole()){
            // отключено
            $data['password'] = UserManager::UserPasswordCrypt($newpassword, $user['salt']);
        } else {

            // смена пароля
            if (empty($newpassword) || strlen($newpassword) < 4){
                return 2; // короткий пароль
            }
            if ($newpassword == $user['username']){
                return 3; // пароль совпадает с логином
            }

            $passcrypt = UserManager::UserPasswordCrypt($oldpassword, $user["salt"]);
            if ($passcrypt == $user["password"]){
                $data['password'] = UserManager::UserPasswordCrypt($newpassword, $user['salt']);
            } else {
                return 4; // старый пароль ошибочный
            }
        }

        UserQuery::UserUpdate($this->db, $userid, $data);

        return 0;
    }


    ////////////////////////////////////////////////////////////////////
    //      	Функции: регистрации/авторизации пользователя     	  //
    ////////////////////////////////////////////////////////////////////


    public function TermsOfUseAgreement(){
        if ($this->userid == 0){
            return false;
        }

        UserQuery::TermsOfUseAgreement($this->db, $this->userid);
        return true;
    }


    private $_userFields = null;

    public function UserFieldList(){
        if (!is_null($this->_userFields)){
            return $this->_userFields;
        }
        $rows = UserQuery::UserFieldList($this->db);
        $cols = array();
        while (($row = $this->db->fetch_array($rows))){
            $cols[$row['Field']] = $row;
        }
        $this->_userFields = $cols;
        return $this->_userFields;
    }

    public function UserFieldCacheClear(){
        $this->_userFields = null;
    }

    public function UserField($fieldName){
        $fields = $this->UserFieldList();
        if (!isset($fields[$fieldName])){
            return '';
        }
        return $fields[$fieldName];
    }

    public function UserFieldCheck($fieldName){
        $field = $this->UserField($fieldName);
        return !empty($field);
    }

    /////////////////////////////////////////////////////////
    //                   Static functions                  //
    /////////////////////////////////////////////////////////


    /**
     * Проверка имени пользователя (логин) на допустимость символов
     *
     * @param $username
     * @return bool
     */
    public static function UserNameValidate($username){
        $username = strtolower(trim($username));

        $length = strlen($username);
        if ($length == 0){
            return false;
        } else if ($length < 3){
            return false;
        } else if ($length > 50){
            return false;
        } else if (preg_match("/^[^a-z]{1}|[^a-z0-9_.-]+/i", $username)){
            return false;
        }
        // $username = htmlspecialchars_uni($username);
        return true;
    }

    /**
     * Проверка адреса электронной почты на допустимость формата
     *
     * @param $address
     * @return bool
     */
    public static function EmailValidate($address){
        if (function_exists('filter_var')){ //Introduced in PHP 5.2
            if (filter_var($address, FILTER_VALIDATE_EMAIL) === FALSE){
                return false;
            } else {
                return true;
            }
        } else {
            return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $address);
        }
    }

    public static function UserPasswordCrypt($password, $salt){
        return md5(md5($password).$salt);
    }

    public function Bos_MenuData(){
        if (!$this->IsAdminRole()){
            return null;
        }
        $i18n = $this->module->I18n();
        return array(
            array(
                "name" => "user",
                "title" => $i18n->Translate('bosmenu.users'),
                "icon" => "/modules/user/images/users-24.png",
                "url" => "user/wspace/ws",
                "parent" => "controlPanel"
            )
        );
    }

    public function Bos_SummaryData(){
        if (!$this->IsAdminRole()){
            return;
        }

        $i18n = $this->module->I18n();
        return array(
            array(
                "module" => "user",
                "component" => "summary",
                "widget" => "SummaryWidget",
                "title" => $i18n->Translate('bosmenu.users'),
            )
        );
    }
}
