<?php

require_once 'admin_structure.php';
require_once 'admin_dbquery.php';

/**
 * Class UserAdminManager
 */
class UserAdminManager {

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

    public function __construct(UserManager $manager) {
        $this->module = $manager->module;
        $this->manager = $manager;
        $this->db = $manager->db;
    }

    public function IsAdminRole(){
        return $this->manager->IsAdminRole();
    }

    public function AJAX($d) {
        switch ($d->do) {
            case "userlist":
                return $this->UserListToAJAX($d->savedata);
        }
        return null;
    }


    public function UserList($page = 1, $limit = 15, $filter = '') {
        if (!$this->IsAdminRole()) {
            return null;
        }

        $modAntibot = Abricos::GetModule('antibot');
        return UserQueryExt::UserList($this->db, $page, $limit, $filter, !empty($modAntibot));
    }


}

?>