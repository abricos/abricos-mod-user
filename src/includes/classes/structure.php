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

    public $antibotdetect = false;

    protected $_data;

    public function __construct($d) {
        parent::__construct($d);

        $d = array_merge(array(
            'username' => '',
            'firstname' => '',
            'lastname' => '',
            'joindate' => '',
            'lastvisit' => TIMENOW,
            'antibotdetect' => false,
            'agreement' => 0
        ), $d);

        $this->username = strval($d['username']);
        $this->firstname = strval($d['firstname']);
        $this->lastname = strval($d['lastname']);

        $this->joindate = intval($d['joindate']);
        $this->lastvisit = intval($d['lastvisit']);

        if (array_key_exists('antibotdetect', $d)){
            $this->antibotdetect = $d['antibotdetect'] > 0;
        }
        $this->agreement = $d['agreement'] > 0;

        $this->_data = $d;
    }

    public function GetType() {
        return 'user';
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

    public function GetActionRole($module, $action) {
        if ($module instanceof Ab_Module) {
            $module = $module->name;
        }

        if ($this->IsSuperAdmin()) {
            return 1;
        }

        $perm = $this->GetPermission();

        if (isset($perm[$module][$action])) {
            return $perm[$module][$action];
        }
        return -1;
    }

    protected $_permission = null;

    protected function GetPermission() {
        if (!is_null($this->_permission)) {
            return $this->_permission;
        }

        if ($this->antibotdetect) { // У бота нет ролей
            return $this->_permission = array();
        }

        $db = Abricos::$db;

        $perm = array();

        $rows = UserQuery::UserRole($db, $this);
        while (($row = $db->fetch_array($rows))) {
            $mod = $row['md'];
            if (!isset($perm[$mod])) {
                $perm[$mod] = array();
            }
            $perm[$mod][$row['act']] = $row['st'];
        }

        return $this->_permission = $perm;
    }

    protected $_groupList = null;

    /**
     * @return array
     */
    public function GetGroupList() {
        if (!is_null($this->_groupList)) {
            return $this->_groupList;
        }
        $db = Abricos::$db;
        $list = array();

        $rows = UserQuery::UserGroupList($db, $this->id);
        while (($row = $db->fetch_array($rows))) {
            array_push($list, $row['id']);
        }
        return $this->_groupList = $list;
    }
}

class UserList extends AbricosList {
    public $classConfig = 'UserListConfig';
}

class UserListConfig extends AbricosListConfig {

    public $filter;

    public $isAntiBot = false;

    public function __construct($d = null) {
        parent::__construct($d);
        if (is_array($d)) {
            $this->filter = strval($d['filter']);
        }

        $this->limit = 10;

        $modAntibot = Abricos::GetModule('antibot');
        $this->isAntiBot = !empty($modAntibot);
    }

    public function ToAJAX() {
        $ret = parent::ToAJAX();
        $ret->filter = $this->filter;
        return $ret;
    }

}

class UserGroup extends AbricosItem {
    public $title;
    public $sysname;
    public $permission;

    public function __construct($d) {
        parent::__construct($d);

        $this->title = strval($d['title']);
        $this->sysname = strval($d['sysname']);
        $this->permission = $d['permission'];
    }

    public function ToAJAX() {
        $ret = parent::ToAJAX();
        $ret->title = $this->title;
        $ret->sysname = $this->sysname;
        $ret->permission = $this->permission;
        return $ret;
    }
}

class UserGroupList extends AbricosList {
}


?>