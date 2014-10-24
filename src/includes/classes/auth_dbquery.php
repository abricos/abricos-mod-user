<?php

class UserQuery_Auth {
    public static function UserDoubleLogAppend(Ab_Database $db, $userid, $duserid, $ip){
        $sql = "
			INSERT INTO ".$db->prefix."userdoublelog
			(userid, doubleuserid, ipadress, dateline) VALUES (
				".bkint($userid).",
				".bkint($duserid).",
				'".bkstr($ip)."',
				".TIMENOW."
			)
		";
        $db->query_write($sql);
    }


}

?>