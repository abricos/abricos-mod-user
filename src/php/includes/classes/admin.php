<?php

require_once 'admin_structure.php';
require_once 'admin_dbquery.php';

/**
 * Class UserManager_Admin
 */
class UserManager_Admin {

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

    public function IsAdminRole() {
        return $this->manager->IsAdminRole();
    }

    public function AJAX($d) {
        switch ($d->do) {
            case "user":
                return $this->UserToAJAX($d->userid);
            case "userSave":
                return $this->UserSaveToAJAX($d->userData);
            case "userActivateCustom":
                return $this->UserActivateCustomToAJAX($d->userid);
            case "userActivateSendEMail":
                return $this->UserActivateSendEMailToAJAX($d->userid);
            case "userList":
                return $this->UserListToAJAX($d->userListConfig);
            case "groupList":
                return $this->GroupListToAJAX();
            case "groupsave":
                return $this->GroupSaveToAJAX($d->groupdata);

        }
        return null;
    }

    public function UserActivateCustomToAJAX($userId) {
        $user = $this->UserActivateCustom($userId);

        if (empty($user)) {
            return 403;
        }

        $ret = new stdClass();
        $ret->user = $user->ToAJAX();
        return $ret;

    }

    public function UserActivateCustom($userId) {
        if (!$this->IsAdminRole()) {
            return null;
        }

        $userId = intval($userId);

        $this->manager->GetRegistrationManager()->Activate($userId);

        $this->manager->CacheUserClear();

        return $this->User($userId);
    }

    public function UserActivateSendEMailToAJAX($userId){
        $user = $this->UserActivateSendEMail($userId);

        if (empty($user)) {
            return 403;
        }

        $ret = new stdClass();
        $ret->user = $user->ToAJAX();
        return $ret;
    }

    public function UserActivateSendEMail($userId){
        if (!$this->IsAdminRole()) {
            return null;
        }

        $userId = intval($userId);

        $result = $this->manager->GetRegistrationManager()->ConfirmEmailSendAgain($userId);
        if (!$result){
            return null;
        }

        $this->manager->CacheUserClear();

        return $this->User($userId);
    }

    public function UserSaveToAJAX($d) {
        $user = $this->UserSave($d, 'UserItem_Admin');

        if (empty($user)) {
            return 403;
        }

        $ret = new stdClass();
        $ret->user = $user->ToAJAX();
        return $ret;
    }

    public function UserSave($d, $classUserItem = null) {
        if (!$this->IsAdminRole()) {
            return null;
        }

        $userId = isset($d->id) ? intval($d->id) : 0;

        if ($userId === 0) {

            $regMan = $this->manager->GetRegistrationManager();
            $err = $regMan->Register($d->username, $d->password, $d->email, false, false);
            if ($err > 0) {
                return $err;
            }

            $user = $this->manager->UserByName($d->username, false, $classUserItem);
            $userId = $user->id;

        } else {

            $user = $this->User($userId);

            if (!empty($d->password)) {
                $passwordCrypt = UserManager::UserPasswordCrypt($d->password, $user->salt);
                UserQuery_Admin::UserPasswordUpdate($this->db, $userId, $passwordCrypt);
            }

            UserQuery_Admin::UserUpdate($this->db, $userId, $d);
        }

        UserQuery_Admin::UserGroupUpdate($this->db, $userId, $d->groups);
        $this->manager->CacheUserClear();

        return $this->User($userId);
    }

    public function UserToAJAX($userId) {
        $user = $this->User($userId);

        if (empty($user)) {
            return 403;
        }

        $ret = new stdClass();
        $ret->user = $user->ToAJAX();
        return $ret;
    }

    /**
     * @param $userId
     * @param null $classUserItem
     * @return UserItem_Admin
     */
    public function User($userId, $classUserItem = null) {
        if (!$this->IsAdminRole()) {
            return null;
        }

        if (empty($classUserItem)) {
            $classUserItem = 'UserItem_Admin';
        }

        return $this->manager->User($userId, $classUserItem);
    }

    public function UserListToAJAX($configData) {
        $config = new UserListConfig($configData);

        $list = $this->UserList($config, 'UserItem_Admin');

        if (empty($list)) {
            return 403;
        }

        $ret = new stdClass();
        $ret->userList = $list->ToAJAX();
        return $ret;
    }

    /**
     * @param UserListConfig $config
     * @return UserList
     */
    public function UserList($config, $classUserItem = null) {
        if (!$this->IsAdminRole()) {
            return null;
        }

        $list = new UserList($config);

        $rows = UserQuery::UserList($this->db, $list->config);
        while (($row = $this->db->fetch_array($rows))) {
            $user = new UserItem($row);
            $this->manager->CacheUserAdd($user, $user->GetType());

            if (!empty($classUserItem)) {
                $user = new $classUserItem($user);
                $this->manager->CacheUserAdd($user, $user->GetType());
            }
            $list->Add($user);
        }
        return $list;
    }

    public function GroupListToAJAX() {
        $list = $this->GroupList();
        if (empty($list)) {
            return 403;
        }

        $ret = new stdClass();
        $ret->groupList = $list->ToAJAX();
        return $ret;
    }

    /**
     * @return UserGroupList
     */
    public function GroupList() {
        if (!$this->IsAdminRole()) {
            return null;
        }
        $list = new UserGroupList();

        $rows = UserQuery::ModuleActionList($this->db);
        $mods = array();
        while (($row = $this->db->fetch_array($rows))) {

            $modName = $row['md'];
            if (!isset($mods[$modName])) {
                $mods[$modName] = array();
            }

            $mods[$modName][$row['id']] = $row;
        }

        $rows = UserQuery::GroupRoleList($this->db);
        $roles = array();
        while (($row = $this->db->fetch_array($rows))) {
            $roles[$row['maid']."-".$row['gid']] = $row;
        }

        $rows = UserQuery::GroupList($this->db);
        while (($row = $this->db->fetch_array($rows))) {
            $perms = array();
            foreach ($mods as $modName => $acts) {
                $perms[$modName] = array();

                foreach ($acts as $actid => $actRow) {
                    $rkey = $actRow['id']."-".$row['id'];
                    $role = isset($roles[$rkey]) ? $roles[$rkey] : null;

                    $perms[$modName][$actRow['act']] = !empty($role) ? intval($role['st']) : 0;
                }
            }
            $row['permission'] = &$perms;

            $group = new UserGroup($row);
            $list->Add($group);
        }

        return $list;
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