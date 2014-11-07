<?php

class UserPersonalQuery {

    public static function User(Ab_Database $db, $userid) {
        $sql = "
			SELECT
				userid as id,
				username,
				joindatel,
				lastvisit,
				email,
				emailconfirm
			FROM ".$db->prefix."user
			WHERE userid='".bkint($userid)."'
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function UserConfigList(Ab_Database $db, $userid, $module){
        $sql = "
			SELECT
				userconfigid as id,
				optname as nm,
				optvalue as vl
			FROM ".$db->prefix."userconfig
			WHERE userid=".bkint($userid)." AND module='".bkstr($module)."'
		";
        return $db->query_read($sql);
    }

    public static function UserConfigSave(Ab_Database $db, $userid, $module, $name, $value){
        $sql = "
			INSERT INTO ".$db->prefix."userconfig (module, userid, optname, optvalue) VALUES (
				'".bkstr($module)."',
				".bkint($userid).",
				'".bkstr($name)."',
				'".bkstr($value)."'
			)
			ON DUPLICATE KEY UPDATE optvalue='".bkstr($value)."'
		";
        $db->query_write($sql);
    }

    public static function UserConfigInfo(Ab_Database $db, $id){
        $sql = "
			SELECT
				userid as uid,
				optname as nm
			FROM ".$db->prefix."userconfig
			WHERE userconfigid=".bkint($id)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function UserConfigAppend(Ab_Database $db, $userid, $module, $name, $value){
        $sql = "
			INSERT INTO ".$db->prefix."userconfig (module, userid, optname, optvalue) VALUES (
				'".bkstr($module)."',
				".bkint($userid).",
				'".bkstr($name)."',
				'".bkstr($value)."'
			)
		";
        $db->query_write($sql);
    }

    public static function UserConfigUpdate(Ab_Database $db, $userid, $cfgid, $cfgval){
        $sql = "
			UPDATE ".$db->prefix."userconfig
			SET optvalue='".bkstr($cfgval)."'
			WHERE userid=".bkint($userid)." AND userconfigid=".bkint($cfgid)."
		";
        $db->query_write($sql);
    }



}

?>