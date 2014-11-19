<?php
/**
 * @package Abricos
 * @copyright Copyright (C) 2008-2011 Abricos. All rights reserved.
 * @license Licensed under the MIT license
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

require_once 'classes/structure.php';
require_once 'dbquery.php';

/**
 * Менеджер управления пользователями
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

    public function __construct(UserModule $module) {
        parent::__construct($module);
    }

    /**
     * Имеет ли пользователь доступ к административным функциям.
     *
     * @return boolean
     */
    public function IsAdminRole() {
        return $this->IsRoleEnable(UserAction::USER_ADMIN);
    }

    /**
     * Имеет ли пользователь полный доступ к профилю пользователя
     *
     * @param integer $userid
     * @return boolean
     */
    public function IsChangeUserRole($userid) {
        return $this->userid == $userid || $this->IsAdminRole();
    }

    private $_sessionManager = null;

    /**
     * @return UserManager_Session
     */
    public function GetSessionManager() {
        if (empty($this->_sessionManager)) {
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
    public function GetRegistrationManager() {
        if (empty($this->_registrationManager)) {
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
    public function GetAuthManager() {
        if (empty($this->_authManager)) {
            require_once 'classes/auth.php';
            $this->_authManager = new UserManager_Auth($this);
        }
        return $this->_authManager;
    }

    private $_passwordManager = null;

    /**
     * @return UserManager_Password
     */
    public function GetPasswordManager() {
        if (empty($this->_passwordManager)) {
            require_once 'classes/password.php';
            $this->_passwordManager = new UserManager_Password($this);
        }
        return $this->_passwordManager;
    }

    private $_adminManager = null;

    /**
     * @return UserManager_Admin
     */
    public function GetAdminManager() {
        if (empty($this->_adminManager)) {
            require_once 'classes/admin.php';
            $this->_adminManager = new UserManager_Admin($this);
        }
        return $this->_adminManager;
    }

    private $_personalManager = null;

    /**
     * @return UserManager_Personal
     */
    public function GetPersonalManager() {
        if (empty($this->_personalManager)) {
            require_once 'classes/personal.php';
            $this->_personalManager = new UserManager_Personal($this);
        }
        return $this->_personalManager;
    }

    public function TreatResult($res) {
        $ret = new stdClass();
        $ret->err = 0;

        if (is_integer($res)) {
            $ret->err = $res;
        } else if (is_object($res)) {
            $ret = $res;
        }
        $ret->err = intval($ret->err);

        return $ret;
    }

    public function AJAX($d) {
        $ret = $this->GetSessionManager()->AJAX($d);

        if (empty($ret)) {
            $ret = $this->GetPersonalManager()->AJAX($d);
        }

        if (empty($ret)) {
            $ret = $this->GetAuthManager()->AJAX($d);
        }

        if (empty($ret)) {
            $ret = $this->GetRegistrationManager()->AJAX($d);
        }

        if (empty($ret)) {
            $ret = $this->GetPasswordManager()->AJAX($d);
        }

        if (empty($ret)) {
            $ret = $this->GetAdminManager()->AJAX($d);
        }

        if (empty($ret)) {
            $ret = new stdClass();
            $ret->err = 500;
        }

        return $ret;
    }

    private $_cacheUser = array();

    public function CacheUserClear() {
        $this->_cacheUser = array();
    }

    public function CacheUser($userid, $type) {
        return $this->_cacheUser[$type][$userid];
    }

    public function CacheUserAdd($user) {
        $this->_cacheUser[$user->GetType()][$user->id] = $user;
    }

    /**
     * @param int $userid
     * @param null $classUserItem
     *
     * @return null|UserItem
     */
    public function User($userid = 0, $classUserItem = null) {
        $userid = intval($userid);
        $user = $this->CacheUser($userid, 'user');
        if (!empty($user)) {
            if (!empty($classUserItem)) {
                // TODO: hack
                $user = new $classUserItem($user);
                $tUser = $this->CacheUser($userid, $user->GetType());
                if (!empty($tUser)) {
                    return $tUser;
                }
            }

            return $user;
        }

        $row = UserQuery::UserById($this->db, $userid);
        if (empty($row)) {
            return null;
        }

        $user = new UserItem($row);
        $this->CacheUserAdd($user, $user->GetType());

        if (!empty($classUserItem)) {
            $user = new $classUserItem($user);
            $this->CacheUserAdd($user, $user->GetType());
        }

        return $user;
    }

    public function UserByName($username, $checkEmail = false, $classUserItem = null) {
        $row = UserQuery::UserByName($this->db, $username, $checkEmail);

        if (empty($row)) {
            return null;
        }

        $user = new UserItem($row);
        $this->CacheUserAdd($user, $user->GetType());

        if (!empty($classUserItem)) {
            $user = new $classUserItem($user);
            $this->CacheUserAdd($user, $user->GetType());
        }

        return $user;
    }

    public function UserExist($useNameOrEmail, $checkEmail = true) {
        $user = $this->UserByName($useNameOrEmail, $checkEmail);

        return !empty($user);
    }

    public function UserDomainUpdate($userid = 0) {
        // не обновлять, если в конфиге домен не определен
        if (empty(Abricos::$DOMAIN)) {
            return;
        }
        if ($userid === 0) {
            $userid = Abricos::$user->id;
        }

        UserQuery::UserDomainUpdate($this->db, $userid, Abricos::$DOMAIN);
    }



    ////////////////////////////////////////////////////////////////////
    //                      Административные функции                  //
    ////////////////////////////////////////////////////////////////////


    public function UserGroupList($page = 1, $limit = 15, $filter = '') {
        if (!$this->IsAdminRole()) {
            return null;
        }

        $modAntibot = Abricos::GetModule('antibot');
        return UserQuery::UserGroupList($this->db, $page, $limit, $filter, !empty($modAntibot));
    }

    public function UserInfo($userid) {
        if (!$this->IsChangeUserRole($userid)) {
            $user = UserQuery::UserPublicInfo($this->db, $userid, true);
        } else {
            $user = UserQuery::UserPrivateInfo($this->db, $userid, true);
        }
        if (empty($user)) {
            return array('id' => $userid);
        }
        $groups = UserQuery::GroupByUserId($this->db, $userid);
        $user['gp'] = implode(",", $groups);

        return $user;
    }

    public function UserUpdate($d) {

        if (!$this->IsChangeUserRole($d->userid)) {
            // haker?
            return -1;
        }

        if ($d->userid == 0) {
            if (!$this->IsAdminRole()) {
                return -1;
            }
            // зарегистрировать пользователя
            $err = $this->Register($d->unm, $d->pass, $d->eml, false, false);
            if ($err > 0) {
                return $err;
            }
            $user = UserQuery::UserByName($this->db, $d->unm);
            $d->userid = $user['userid'];
        } else {

            $user = UserQuery::User($this->db, $d->userid, true);

            // данные для внесения в бд
            $data = array();

            // смена пароля
            if (!empty($d->pass)) {
                if ($this->IsAdminRole()) {
                    $data['password'] = UserManager::UserPasswordCrypt($d->pass, $user['salt']);
                } else {
                    $passcrypt = UserManager::UserPasswordCrypt($d->oldpass, $user["salt"]);
                    if ($passcrypt == $user["password"]) {
                        $data['password'] = UserManager::UserPasswordCrypt($d->pass, $user['salt']);
                    }
                }
            }

            // смена емайл
            if ($this->IsAdminRole()) {
                $data['email'] = $d->eml;
            }

            UserQuery::UserUpdate($this->db, $d->userid, $data);
        }
        if (!$this->IsAdminRole()) {
            return;
        }
        UserQuery::UserGroupUpdate($this->db, $d->userid, explode(',', $d->gp));
        return 0;
    }

    public function UserPasswordChange($userid, $newpassword, $oldpassword = '') {
        if (!$this->IsChangeUserRole($userid)) {
            return 1; // нет доступа на изменение пароля
        }

        $user = UserQuery::User($this->db, $userid, true);

        // данные для внесения в бд
        $data = array();

        if ($this->IsAdminRole()) {
            // отключено
            $data['password'] = UserManager::UserPasswordCrypt($newpassword, $user['salt']);
        } else {

            // смена пароля
            if (empty($newpassword) || strlen($newpassword) < 4) {
                return 2; // короткий пароль
            }
            if ($newpassword == $user['username']) {
                return 3; // пароль совпадает с логином
            }

            $passcrypt = UserManager::UserPasswordCrypt($oldpassword, $user["salt"]);
            if ($passcrypt == $user["password"]) {
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




    public function TermsOfUseAgreement() {
        if ($this->userid == 0) {
            return false;
        }

        UserQuery::TermsOfUseAgreement($this->db, $this->userid);
        return true;
    }

    public function PasswordRequestCheck($hash) {
        $ret = new stdClass();
        $ret->error = 0;

        $pwdreq = UserQuery::PasswordRequestCheck($this->db, $hash);
        if (empty($pwdreq)) {
            $ret->error = 1;
            sleep(1);
            return $ret;
        }
        $userid = $pwdreq['userid'];
        $user = UserQuery::User($this->db, $userid);
        $ret->email = $user['email'];

        $newpass = cmsrand(100000, 999999);
        $passcrypt = UserManager::UserPasswordCrypt($newpass, $user['salt']);
        UserQuery::PasswordChange($this->db, $userid, $passcrypt);

        $sitename = SystemModule::$instance->GetPhrases()->Get('site_name');

        $brick = Brick::$builder->LoadBrickS('user', 'templates', null, null);

        $subject = $brick->param->var['pwdres_changemail_subj'];
        $subject = str_replace("%1", $sitename, $subject);

        $message = nl2br($brick->param->var['pwdres_changemail']);
        $message = str_replace("%1", $user['username'], $message);
        $message = str_replace("%2", $newpass, $message);
        $message = str_replace("%3", $sitename, $message);

        Abricos::Notify()->SendMail($user['email'], $subject, $message);

        return $ret;
    }

    private $_userFields = null;

    public function UserFieldList() {
        if (!is_null($this->_userFields)) {
            return $this->_userFields;
        }
        $rows = UserQuery::UserFieldList($this->db);
        $cols = array();
        while (($row = $this->db->fetch_array($rows))) {
            $cols[$row['Field']] = $row;
        }
        $this->_userFields = $cols;
        return $this->_userFields;
    }

    public function UserFieldCacheClear() {
        $this->_userFields = null;
    }

    public function UserField($fieldName) {
        $fields = $this->UserFieldList();
        return $fields[$fieldName];
    }

    public function UserFieldCheck($fieldName) {
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
    public static function UserNameValidate($username) {
        $username = strtolower(trim($username));

        $length = strlen($username);
        if ($length == 0) {
            return false;
        } else if ($length < 3) {
            return false;
        } else if ($length > 50) {
            return false;
        } else if (preg_match("/^[^a-z]{1}|[^a-z0-9_.-]+/i", $username)) {
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
    public static function EmailValidate($address) {
        if (function_exists('filter_var')) { //Introduced in PHP 5.2
            if (filter_var($address, FILTER_VALIDATE_EMAIL) === FALSE) {
                return false;
            } else {
                return true;
            }
        } else {
            return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_](?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $address);
        }
    }

    public static function UserPasswordCrypt($password, $salt) {
        return md5(md5($password).$salt);
    }

    public function Bos_MenuData() {
        if (!$this->IsAdminRole()) {
            return null;
        }
        $lng = $this->module->GetI18n();
        return array(
            array(
                "name" => "adminka",
                "title" => $lng['bosmenu']['adminka'],
                "icon" => "/modules/user/images/cpanel-24.png",
                "url" => "user/board/showBoardPanel",
                "parent" => "controlPanel"
            ),
            array(
                "name" => "user",
                "title" => $lng['bosmenu']['users'],
                "icon" => "/modules/user/images/users-24.png",
                "url" => "user/wspace/ws",
                "parent" => "controlPanel"
            )
        );
    }


}

?>