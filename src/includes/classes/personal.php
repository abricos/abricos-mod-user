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
            case "userOptionSave":
                return $this->UserOptionSaveToAJAX($d->module, $d->savedata);
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

        $ret = new stdClass();
        $ret->userOptionList = $list->ToAJAX();
        $ret->userOptionList->config = null;
        return $ret;
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
            if (!$optNames[$item->id]) {
                continue;
            }
            $list->Add($item);
        }

        foreach ($optNames as $optName => $optCfg) {
            $option = $list->Get($optName);
            if (!empty($option)) {
                continue;
            }
            $item = new UserOptionItem(array(
                "id" => $optName,
                "val" => ""
            ));
            $list->Add($item);
        }

        return $list;
    }

    public function UserOptionSaveToAJAX($modName, $d) {
        if (is_array($d)) {
            for ($i = 0; $i < count($d); $i++) {
                $this->UserOptionSave($modName, $d[$i]);
            }
        }else{
            $this->UserOptionSave($modName, $d);
        }
        return $this->UserOptionListToAJAX($modName);
    }

    public function UserOptionSave($modName, $d) {
        $optNames = $this->UserOptionNames($modName);
        if (empty($optNames)) {
            return null;
        }

        UserQuery_Personal::UserOptionSave(Abricos::$db, Abricos::$user->id, $modName, $d->id, $d->value);
    }


}

?>