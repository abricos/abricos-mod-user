<?php

require_once 'admin_structure.php';
require_once 'admin_dbquery.php';

/**
 * Class UserManager_Admin
 */
class UserManager_Admin {

    /**
     * @var UserModule
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

    public function IsAdminRole() {
        return $this->manager->IsAdminRole();
    }

    public function AJAX($d) {
        switch ($d->do) {
            case "adminuserlist":
                return $this->UserListToAJAX($d->adminuserlistconfig);
            case "admingrouplist":
                return $this->GroupListToAJAX();
        }
        return null;
    }

    public function UserListToAJAX($configData) {
        $config = new UserListConfig($configData);

        $list = $this->UserList($config);

        if (empty($list)) {
            return 403;
        }

        $ret = new stdClass();
        $ret->adminuserlist = $list->ToAJAX();
        return $ret;
    }

    /**
     * @param UserListConfig $config
     * @return UserList
     */
    public function UserList($config) {
        if (!$this->IsAdminRole()) {
            return null;
        }

        return $this->manager->UserList($config, UserItem_Admin);
    }

    public function GroupListToAJAX() {
        $list = $this->GroupList();
        if (empty($list)) {
            return 403;
        }

        $ret = new stdClass();
        $ret->admingrouplist = $list->ToAJAX();
        return $ret;
    }

    public function GroupList() {
        if (!$this->IsAdminRole()) {
            return null;
        }

        return $this->manager->GroupList();
    }

}

?>