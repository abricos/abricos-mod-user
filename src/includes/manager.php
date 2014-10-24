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
     * @var User
     */
    public $module = null;

    private $_disableRoles = false;

    public function __construct(UserModule $module) {
        parent::__construct($module);
    }

    /**
     * Отключить проверку всех ролей в текущей сессии пользователя
     */
    public function DisableRoles() {
        $this->_disableRoles = true;
    }

    /**
     * Включить проверку всех ролей в текущей сессии пользователя (по умолчанию - включено)
     */
    public function EnableRoles() {
        $this->_disableRoles = false;
    }

    /**
     * Имеет ли пользователь доступ к административным функциям.
     *
     * @return boolean
     */
    public function IsAdminRole() {
        if ($this->_disableRoles) {
            return true;
        }
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

    private $_sessionManager = null;

    /**
     * Получить менеджер авторизации
     *
     * @return UserSessionManager
     */
    public function GetSessionManager() {
        if (empty($this->_sessionManager)) {
            require_once 'classes/session.php';
            $this->_sessionManager = new UserSessionManager($this);
        }
        return $this->_sessionManager;
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
        $ret = $this->GetAuthManager()->AJAX($d);

        if (empty($ret)) {
            $ret = $this->GetRegistrationManager()->AJAX($d);
        }

        if (empty($ret)) {
            $ret = new stdClass();
            $ret->err = 500;
        }

        return $ret;
        /*

                switch ($d->do) {

                    // TODO: old functions

                    case "loginext":
                        return $this->LoginExt($d->username, $d->password, $d->autologin);
                    case "termsofuseagreement":
                        return $this->TermsOfUseAgreement();
                    case "user":
                        return $this->UserInfo($d->userid);
                    case "usersave":
                        return $this->UserUpdate($d);
                    case "passwordchange":
                        return $this->UserPasswordChange($d->userid, $d->pass, $d->passold);
                }
                return -1;
                /**/
    }

    private $_cacheUser = array();

    public function CacheUserClear() {
        $this->_cacheUser = array();
    }

    /**
     * @param int $userid
     * @param bool $cacheClear
     * @return null|UserItem
     */
    public function User($userid = 0, $cacheClear = false) {
        if ($cacheClear) {
            $this->CacheUserClear();
        }
        $userid = intval($userid);

        if (!empty($this->_cacheUser[$userid])) {
            return $this->_cacheUser[$userid];
        }

        $row = UserQuery::UserById($this->db, $userid);
        if (empty($row)) {
            return null;
        }

        $user = new UserItem($row);

        $this->_cacheUser[$userid] = $user;

        return $user;
    }

    public function UserByName($username, $orByEmail = false) {
        $row = UserQuery::UserByName($this->db, $username, $orByEmail);

        if (empty($row)) {
            return null;
        }

        $user = new UserItem($row);

        $this->_cacheUser[$user->id] = $user;

        return $user;
    }


    public function UserDomainUpdate($userid = 0) {
        // не обновлять, если в конфиге домен не определен
        if (empty(Abricos::$DOMAIN)) {
            return;
        }
        if ($userid === 0) {
            $userid = Abricos::$user->id;
        }

        UserQueryExt::UserDomainUpdate($this->db, $userid, Abricos::$DOMAIN);
    }






    private $_newGroupId = 0;

    public function DSProcess($name, $rows) {
        if ($this->IsAdminRole()) {
            return null;
        }
        $p = $rows->p;
        $db = $this->db;
        switch ($name) {
            case 'grouplist':
                foreach ($rows->r as $r) {
                    if ($r->f == 'a') {
                        $this->_newGroupId = UserQueryExt::GroupAppend($db, $r->d->nm);
                    }
                    if ($r->f == 'u') {
                        UserQueryExt::GroupUpdate($this->db, $r->d);
                    }
                }
                return;
            case 'rolelist':
                foreach ($rows->r as $r) {
                    if ($r->f == 'a') {
                        if (intval($p->groupid) == 0 && intval($this->_newGroupId) > 0) {
                            $p->groupid = $this->_newGroupId;
                        }
                        UserQueryExt::RoleAppend($db, $p->groupid, $r->d);
                    }
                    if ($r->f == 'd') {
                        UserQueryExt::RoleRemove($this->db, $r->d->id);
                    }
                }
                return;

        }
    }

    public function DSGetData($name, $rows) {
        $p = $rows->p;
        $db = $this->db;

        // Запросы доступные всем
        switch ($name) {
            /////// Пользователь //////
            case 'user':
                return array($this->UserInfo($p->userid));

            case 'permission':
                return $this->Permission();
        }

        // Запросы уровня администратора
        if ($this->IsAdminRole()) {
            switch ($name) {

                /////// Постраничный список пользователей //////
                case 'userlist':
                    return $this->UserList($p->page, $p->limit, $p->filter);
                case 'usercount':
                    return $this->UserCount($p->filter);
                case 'usergrouplist':
                    return $this->UserGroupList($p->page, $p->limit, $p->filter);

                /////// Постраничный список групп //////
                case 'grouplist':
                    return UserQueryExt::GroupList($db);
                case 'groupcount':
                    return UserQueryExt::GroupCount($db);

                /////// Роли //////
                case 'rolelist':
                    return UserQueryExt::RoleList($db, $p->groupid);
                case 'modactionlist':
                    return UserQueryExt::ModuleActionList($this->db);
            }
        }

        return null;
    }


    ////////////////////////////////////////////////////////////////////
    //                       Общедоступные запросы                    //
    ////////////////////////////////////////////////////////////////////

    public function Permission() {
        $rows = array();
        CMSRegistry::$instance->modules->RegisterAllModule();
        $mods = CMSRegistry::$instance->modules->GetModules();
        foreach ($mods as $modname => $module) {
            if (is_null($module->permission)) {
                continue;
            }
            $roles = $module->permission->GetRoles();
            if (is_null($roles)) {
                continue;
            }
            array_push($rows, array("nm" => $modname, "roles" => $roles));
        }
        return $rows;
    }

    ////////////////////////////////////////////////////////////////////
    //                      Административные функции                  //
    ////////////////////////////////////////////////////////////////////


    public function UserCount($filter = '') {
        if (!$this->IsAdminRole()) {
            return null;
        }

        $modAntibot = Abricos::GetModule('antibot');
        return UserQueryExt::UserCount($this->db, $filter, !empty($modAntibot));
    }

    public function UserGroupList($page = 1, $limit = 15, $filter = '') {
        if (!$this->IsAdminRole()) {
            return null;
        }

        $modAntibot = Abricos::GetModule('antibot');
        return UserQueryExt::UserGroupList($this->db, $page, $limit, $filter, !empty($modAntibot));
    }

    public function UserInfo($userid) {
        if (!$this->IsChangeUserRole($userid)) {
            $user = UserQueryExt::UserPublicInfo($this->db, $userid, true);
        } else {
            $user = UserQueryExt::UserPrivateInfo($this->db, $userid, true);
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
            $user = UserQueryExt::UserByName($this->db, $d->unm);
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

            UserQueryExt::UserUpdate($this->db, $d->userid, $data);
        }
        if (!$this->IsAdminRole()) {
            return;
        }
        UserQueryExt::UserGroupUpdate($this->db, $d->userid, explode(',', $d->gp));
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

        UserQueryExt::UserUpdate($this->db, $userid, $data);

        return 0;
    }


    ////////////////////////////////////////////////////////////////////
    //      	Функции: регистрации/авторизации пользователя     	  //
    ////////////////////////////////////////////////////////////////////


    /**
     * Запросить систему восстановить пароль и вернуть номер ошибки:
     * 0 - нет ошибки,
     * 1 - пользователь не найден,
     * 2 - письмо подверждения восстановить пароль уже отправлено
     *
     * @param string $email E-mail пользователя
     * @return Integer
     */
    public function PasswordRestore($email) {
        $user = UserQueryExt::UserByEmail($this->db, $email);
        if (empty($user)) {
            return 1;
        } // пользователь не найден

        $sendcount = UserQueryExt::PasswordSendCount($this->db, $user['userid']);
        if ($sendcount > 0) {
            return 2;
        } // письмо уже отправлено

        $hash = md5(microtime());
        UserQueryExt::PasswordRequestCreate($this->db, $user['userid'], $hash);

        $host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
        $link = "http://".$host."/user/recpwd/".$hash;

        $sitename = Brick::$builder->phrase->Get('sys', 'site_name');

        $brick = Brick::$builder->LoadBrickS('user', 'templates', null, null);

        $subject = Brick::ReplaceVarByData($brick->param->var['pwd_mail_subj'], array("sitename" => $sitename));
        $body = nl2br(Brick::ReplaceVarByData($brick->param->var['pwd_mail'], array("email" => $email, "link" => $link, "username" => $user['username'], "sitename" => $sitename)));

        Abricos::Notify()->SendMail($email, $subject, $body);

        return 0;
    }

    public function TermsOfUseAgreement() {
        if ($this->userid == 0) {
            return false;
        }

        UserQueryExt::TermsOfUseAgreement($this->db, $this->userid);
        return true;
    }

    public function PasswordRequestCheck($hash) {
        $ret = new stdClass();
        $ret->error = 0;

        $pwdreq = UserQueryExt::PasswordRequestCheck($this->db, $hash);
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
        UserQueryExt::PasswordChange($this->db, $userid, $passcrypt);

        $ph = Brick::$builder->phrase;
        $sitename = $ph->Get('sys', 'site_name');

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

    public function UserConfigList($userid, $modname) {
        if (!$this->IsChangeUserRole($userid)) {
            return null;
        }

        return UserQueryExt::UserConfigList($this->db, $userid, $modname);
    }

    public function UserConfigValueSave($userid, $modname, $varname, $value) {
        if (!$this->IsChangeUserRole($userid)) {
            return null;
        }
        UserQueryExt::UserConfigSave($this->db, $userid, $modname, $varname, $value);
    }

    /**
     * @deprecated
     */
    public function UserConfigAppend($userid, $modname, $cfgname, $cfgval) {
        if (!$this->IsChangeUserRole($userid)) {
            return null;
        }

        UserQueryExt::UserConfigAppend($this->db, $userid, $modname, $cfgname, $cfgval);
    }

    /**
     * @deprecated
     */
    public function UserConfigUpdate($userid, $cfgid, $cfgval) {
        if (!$this->IsChangeUserRole($userid)) {
            return null;
        }

        UserQueryExt::UserConfigUpdate($this->db, $userid, $cfgid, $cfgval);
    }

    private $_userFields = null;

    public function UserFieldList() {
        if (!is_null($this->_userFields)) {
            return $this->_userFields;
        }
        $rows = UserQueryExt::UserFieldList($this->db);
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
        $lng = $this->module->lang;
        return array(array("name" => "adminka", "title" => $lng['bosmenu']['adminka'], "icon" => "/modules/user/images/cpanel-24.png", "url" => "user/board/showBoardPanel", "parent" => "controlPanel"), array("name" => "user", "title" => $lng['bosmenu']['users'], "icon" => "/modules/user/images/users-24.png", "url" => "user/wspace/ws", "parent" => "controlPanel"));
    }


}

?>