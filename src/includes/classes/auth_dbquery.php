<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class UserQuery_Auth
 */
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
