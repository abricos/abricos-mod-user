<?php

class UserItem extends AbricosItem {

    /**
     * @deprecated
     */
    public $login;

    public $username;
    public $firstname;
    public $lastname;

    public $agreement;

    public $joindate;
    public $lastvisit;

    public $antibotdetect;

    protected  $info;

    public function __construct($d) {
        parent::__construct($d);

        $this->username = strval($d['username']);
        $this->firstname = strval($d['firstname']);
        $this->lastname = strval($d['lastname']);

        $this->joindate = intval($d['joindate']);
        $this->lastvisit = intval($d['lastvisit']);

        $this->antibotdetect = $d['antibotdetect'] > 0;
        $this->agreement = $d['agreement'] > 0;

        $this->info = $d;
    }

    public function ToAJAX() {
        $ret = parent::ToAJAX();
        $ret->username = $this->username;
        $ret->joindate = $this->joindate;
        $ret->lastvisit = $this->lastvisit;
        return $ret;
    }

    private $_isSuperAdmin = null;

    public function IsSuperAdmin() {
        if (!is_null($this->_isSuperAdmin)) {
            return $this->_isSuperAdmin;
        }
        $this->_isSuperAdmin = false;

        $superAdmin = Abricos::$config['superadmin'];

        if (!empty($superAdmin)) {
            $ids = explode(',', $superAdmin);
            foreach ($ids as $id) {
                if (intval($id) === intval($this->id)) {
                    $this->_isSuperAdmin = true;
                    break;
                }
            }
        }
        return $this->_isSuperAdmin;
    }

    protected $permission = null;

    public function GetActionRole($module, $action) {
        if ($module instanceof Ab_Module) {
            $module = $module->name;
        }

        if ($this->IsSuperAdmin()) {
            return 1;
        }

        if (is_null($this->permission)) {
            $this->permission = $this->LoadPermission();
        }

        if (isset($this->permission[$module][$action])) {
            return $this->permission[$module][$action];
        }
        return -1;
    }

    protected function LoadPermission() {
        if ($this->antibotdetect) { // У бота нет ролей
            return array();
        }

        $db = Abricos::$db;

        $perm = array();

        $rows = UserQuery::UserRole($db, $this->info);
        while (($row = $db->fetch_array($rows))) {
            $mod = $row['md'];
            if (!$perm[$mod]) {
                $perm[$mod] = array();
            }
            $perm[$mod][$row['act']] = $row['st'];
        }

        return $perm;
    }


}

class UserList extends AbricosList {
}

?>