<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class UserQuery_Admin
 */
class UserQuery_Admin {

    /**
     * Добавить пользователя в базу
     *
     * @param Ab_Database $db
     * @param Array $user данные пользователя
     * @param integer $groupid
     * @param string $ip
     * @param boolean $agreement True-согласен с пользовательским соглашением
     * @param boolean $isVirtual True-виртуальный пользователь
     */
    public static function UserAppend(Ab_Database $db, &$uData, $groupid = UserModule::UG_GUEST, $ip = '', $agreement = false, $isVirtual = false) {

        $db->query_write("
			INSERT INTO ".$db->prefix."user
				(language, username, password, email, emailconfirm, joindate, salt, ipadress, agreement, isvirtual) VALUES (
				'".Abricos::$LNG."',
				'".bkstr($uData['username'])."',
				'".bkstr($uData['password'])."',
				'".bkstr($uData['email'])."',
				".($groupid == UserModule::UG_GUEST ? 0 : 1).",
				'".bkstr($uData['joindate'])."',
				'".bkstr($uData['salt'])."',
				'".bkstr($ip)."',
				".($agreement ? 1 : 0).",
				".($isVirtual ? 1 : 0)."
		)");
        $userid = $db->insert_id();


        UserQuery_Admin::UserGroupUpdate($db, $userid, array($groupid));

        if ($groupid != UserModule::UG_GUEST) {
            return $userid;
        }

        $uData["userid"] = $userid;
        $uData['activateid'] = cmsrand(0, 100000000);
        $sql = "
			INSERT INTO `".$db->prefix."useractivate`
				(userid, activateid, joindate) VALUES (
				'".bkint($userid)."',
				'".bkstr($uData['activateid'])."',
				'".bkstr($uData['joindate'])."'
		)";
        $db->query_write($sql);
        return $userid;
    }

    public static function UserUpdate(Ab_Database $db, $userId, $d) {
        $sql = "
			UPDATE ".$db->prefix."user
			SET email='".strval($d->email)."'
			WHERE userid=".intval($userId)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function UserPasswordUpdate(Ab_Database $db, $userId, $passwordCrypt) {
        $sql = "
			UPDATE ".$db->prefix."user
			SET password='".strval($passwordCrypt)."'
			WHERE userid=".intval($userId)."
			LIMIT 1
		";
        $db->query_write($sql);
    }


    public static function UserGroupAppend(Ab_Database $db, $userid, $groupid) {
        $sql = "
			INSERT IGNORE INTO ".$db->prefix."usergroup (userid, groupid) VALUES
			(".bkint($userid).",".bkint($groupid).")
		";
        $db->query_write($sql);
    }

    public static function UserGroupUpdate(Ab_Database $db, $userid, $groups) {
        $sql = "
			DELETE FROM `".$db->prefix."usergroup`
			WHERE userid=".bkint($userid)."
		";
        $db->query_write($sql);

        $arr = array();
        foreach ($groups as $gp) {
            array_push($arr, "(".bkint($userid).",".bkint($gp).")");
        }
        if (count($arr) < 1) {
            return;
        }

        $sql = "
			INSERT IGNORE INTO `".$db->prefix."usergroup` (`userid`, `groupid`) VALUES
			".implode(',', $arr)."
		";
        $db->query_write($sql);
    }

    public static function GroupAppend(Ab_Database $db, $title, $key = '') {
        $sql = "
			INSERT INTO ".$db->prefix."group (groupname, groupkey) VALUES (
				'".bkstr($title)."',
				'".bkstr($key)."'
			)
		";
        $db->query_write($sql);
        return $db->insert_id();
    }

    public static function GroupUpdate(Ab_Database $db, $d) {
        $sql = "
			UPDATE ".$db->prefix."group
			SET groupname = '".bkstr($d->title)."'
			WHERE groupid = ".bkint($d->id)."
		";
        $db->query_write($sql);
    }

    public static function GroupRoleActionUpdate(Ab_Database $db, $modActionId, $groupId, $action){
        $sql = "
            INSERT INTO ".$db->prefix."userrole
            (modactionid, usertype, userid, status) VALUES
            ('".intval($modActionId)."', 0, ".intval($groupId).", ".intval($action).")
            ON DUPLICATE KEY UPDATE status=".intval($action)."
        ";
        $db->query_write($sql);
    }
}

?>