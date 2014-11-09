<?php

require_once 'personal_structure.php';
require_once 'personal_dbquery.php';

/**
 * Class UserManager_Personal
 */
class UserManager_Personal {

    /**
     * @var UserManager
     */
    public $manager;

    /**
     * @var Ab_Database
     */
    public $db;

    public function __construct(UserManager $manager) {
        $this->manager = $manager;
        $this->db = $manager->db;
    }

    public function AJAX($d) {
        switch ($d->do) {
            case "userOptionList":
                return $this->UserOptionListToAJAX($d->module);
        }
        return null;
    }

    private function UserOptionNames($modName) {
        $mod = Abricos::GetModule($modName);
        if (empty($mod)) {
            return null;
        }
        $man = $mod->GetManager();
        if (empty($man)) {
            return null;
        }
        if (!method_exists($man, 'User_OptionNames')) {
            return null;
        }
        $optNames = $man->User_OptionNames();
        if (!is_array($optNames)) {
            return null;
        }

        $ret = array();
        foreach ($optNames as $optName) {
            $ret[$optName] = true;
        }
        return $ret;
    }

    public function UserOptionListToAJAX($modName) {
        $list = $this->UserOptionList($modName);
        if (empty($list)) {
            return 403;
        }
        return $list->ToAJAX();
    }

    /**
     * @param $modName
     * @param $varNames
     * @return UserOptionList
     */
    public function UserOptionList($modName) {
        $optNames = $this->UserOptionNames($modName);
        if (empty($optNames)) {
            return null;
        }
        $list = new UserOptionList();
        $rows = UserQuery_Personal::UserOptionList(Abricos::$db, Abricos::$user->id, $modName);

        while (($row = $this->db->fetch_array($rows))) {
            $item = new UserOptionItem($row);
            if (!$optNames[$item->name]) {
                continue;
            }
            $list->Add($item);
        }
        return $list;
    }

    public function UserOptionUpdate($modName, $sd) {
        $optList = $this->UserOptionList($modName);

        for ($i = 0; $i < $optList->Count(); $i++) {
            $opt = $optList->GetByIndex($i);

        }

        /*

                $rows = $uman->UserOptionList($this->userid, 'botask');
                $arr = $this->ToArrayById($rows);

                $names = array(
                    "tasksort",
                    "tasksortdesc",
                    "taskviewchild",
                    "taskviewcmts"
                );

                foreach ($names as $name) {
                    $find = null;
                    foreach ($arr as $cfgid => $crow) {
                        if ($name == $crow['nm']) {
                            $find = $crow;
                            break;
                        }
                    }
                    if (is_null($find)) {
                        $uman->UserOptionAppend($this->userid, 'botask', $name, $newcfg->$name);
                    } else {
                        $uman->UserOptionUpdate($this->userid, $cfgid, $newcfg->$name);
                    }
                }
                return $this->UserOptionList();
                /**/
    }

    /*
        public function UserConfigList($userid, $modname) {
            if (!$this->IsChangeUserRole($userid)) {
                return null;
            }

            return UserQuery::UserConfigList($this->db, $userid, $modname);
        }

        public function UserConfigValueSave($userid, $modname, $varname, $value) {
            if (!$this->IsChangeUserRole($userid)) {
                return null;
            }
            UserQuery::UserConfigSave($this->db, $userid, $modname, $varname, $value);
        }

        public function UserConfigAppend($userid, $modname, $cfgname, $cfgval) {
            if (!$this->IsChangeUserRole($userid)) {
                return null;
            }

            UserQuery::UserConfigAppend($this->db, $userid, $modname, $cfgname, $cfgval);
        }

        public function UserConfigUpdate($userid, $cfgid, $cfgval) {
            if (!$this->IsChangeUserRole($userid)) {
                return null;
            }

            UserQuery::UserConfigUpdate($this->db, $userid, $cfgid, $cfgval);
        }
    /**/

}

?>