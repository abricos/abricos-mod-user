<?php
/**
 * @package Abricos
 * @license MIT License, https://github.com/abricos/abricos-mod-user/blob/master/LICENSE
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Модуль управления пользователями
 *
 * @package Abricos
 */
class UserModule extends Ab_Module {

    /**
     * @var UserModule
     */
    public static $instance;

    /**
     * TODO: remove
     */
    private $id = 0;


    /**
     * Группа пользователей "Гость"
     *
     * @var integer
     * @deprecated
     */
    const UG_GUEST = 1;

    /**
     * Группа пользователей "Зарегистрированный"
     *
     * @var integer
     * @deprecated
     */
    const UG_REGISTERED = 2;

    /**
     * Группа пользователей "Администратор"
     *
     * @var integer
     * @deprecated
     */
    const UG_ADMIN = 3;

    private $_manager = null;

    public function __construct() {
        UserModule::$instance = $this;

        $this->version = "0.3.0";
        $this->name = "user";
        $this->takelink = "user";
        $this->permission = new UserPermission($this);
    }

    /**
     * Получить менеджер пользователей
     *
     * @return UserManager
     */
    public function GetManager() {
        if (is_null($this->_manager)) {
            require_once 'includes/manager.php';
            $this->_manager = new UserManager($this);
        }
        return $this->_manager;
    }

    public function GetContentName() {
        $adress = Abricos::$adress;
        $cname = '';

        if ($adress->level == 1) { // http://mysite.com/user/
            if (Abricos::$user->id === 0) {
                $cname = 'index_guest';
            } else {
                $cname = 'index';
            }
        } else if ($adress->level > 1) {
            $cname = $adress->dir[1];
        }
        if ($cname == '') {
            Abricos::SetPageStatus(PAGESTATUS_404);
        }
        return $cname;
    }

    /**
     * @return AntibotModule
     */
    public function GetAntibotModule() {
        return Abricos::GetModule('antibot');
    }

    public function AntibotUserDataUpdate($userid = 0) {
        $mod = $this->GetAntibotModule();
        if (empty($mod)) {
            return;
        }
        $mod->UserDataUpdate($userid);
    }



    public function Bos_IsMenu(){
        return true;
    }

    /**
     * Текущий пользователь СУПЕРАДМИНИСТРАТОР
     *
     * @return boolean
     */
    public function IsSuperAdmin() {
        return $this->info["superadmin"];
    }

    /**
     * Вернуть TRUE если пользователь является администратором
     * TODO: необходимо удалить (временое решение для совместимости)
     *
     * @ignore
     * @deprecated
     * @return bool
     */
    public function IsAdminMode() {
        foreach ($this->info["group"] as $group) {
            if ($group == 3) {
                return true;
            }
        }
        return false;
    }

    /**
     * Вернуть TRUE если пользователь является зарегистрированным
     * TODO: необходимо удалить (временое решение для совместимости)
     *
     * @deprecated
     * @ignore
     * @return bool
     */
    public function IsRegistred() {
        foreach ($this->info["group"] as $group) {
            if ($group == 2 || $group == 3) {
                return true;
            }
        }
        return false;
    }
}

/**
 * Идентнификаторы действий ролей пользователя
 *
 * @package Abricos
 */
class UserAction {
    // регистрация пользователя
    const REGISTRATION = 10;

    // администрирование пользователей
    const USER_ADMIN = 50;
}

/**
 * Роли пользователей
 *
 * @package Abricos
 */
class UserPermission extends Ab_UserPermission {

    public function __construct(UserModule $module) {
        $defRoles = array(
            new Ab_UserRole(UserAction::REGISTRATION, Ab_UserGroup::GUEST),
            new Ab_UserRole(UserAction::USER_ADMIN, Ab_UserGroup::ADMIN)
        );
        parent::__construct($module, $defRoles);
    }

    public function GetRoles() {
        return array(
            UserAction::REGISTRATION => $this->CheckAction(UserAction::REGISTRATION),
            UserAction::USER_ADMIN => $this->CheckAction(UserAction::USER_ADMIN)
        );
    }
}

Abricos::ModuleRegister(new UserModule());

?>