<?php

class UserQuery_Admin {
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

}

?>