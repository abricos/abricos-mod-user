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
            case "userlist":
                return $this->UserListToAJAX($d->userlistconfig);
            case "grouplist":
                return $this->GroupListToAJAX();
            case "groupsave":
                return $this->GroupSaveToAJAX($d->groupdata);
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
        $ret->users = $list->ToAJAX();
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
        $ret->groups = $list->ToAJAX();
        return $ret;
    }

    public function GroupList() {
        if (!$this->IsAdminRole()) {
            return null;
        }

        return $this->manager->GroupList();
    }

    public function GroupSaveToAJAX($sd) {
        if (!$this->IsAdminRole()) {
            return 403;
        }

        $res = $this->GroupSave($sd);
        if (empty($res)) {
            return 500;
        }
        $ret = $this->GroupListToAJAX();
        $ret->groupid = $res->groupid;

        return $ret;
    }

    public function GroupSave($d) {
        if (!$this->IsAdminRole()) {
            return null;
        }

        $utmf = Abricos::TextParser(true);

        $d->id = intval($d->id);
        $d->title = $utmf->Parser($d->title);

        if ($d->id === 0) {
            $d->id = UserQuery_Admin::GroupAppend($this->db, $d->title);
        } else {
            UserQuery_Admin::GroupUpdate($this->db, $d);
        }

        $ret = new stdClass();
        $ret->groupid = $d->id;

        return $ret;
    }

}

?>