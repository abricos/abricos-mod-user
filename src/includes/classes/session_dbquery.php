<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class UserQuery_Session
 */
class UserQuery_Session {

    public static function Session(Ab_Database $db, $cookieTimeOut, $hash, $idHash){
        $sql = "
			DELETE FROM ".$db->prefix."session
			WHERE lastactivity < ".(TIMENOW - $cookieTimeOut)." and userid > 0
		";
        $db->query_write($sql, true);

        $sql = "
			SELECT *
			FROM ".$db->prefix."session
			WHERE
				sessionhash='".bkstr($hash)."'
				AND lastactivity > ".(TIMENOW - $cookieTimeOut)."
				AND idhash='".bkstr($idHash)."'
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function SessionAppend(Ab_Database $db, $userid, $hash, $idHash){
        $sql = "
			INSERT INTO ".$db->prefix."session (userid, sessionhash, idhash, lastactivity)
			VALUES (
				".bkint($userid).",
				'".bkstr($hash)."',
				'".bkstr($idHash)."',
				".TIMENOW."
			)
		";
        $db->query_write($sql, true);
    }

    public static function SessionRemove(Ab_Database $db, $sessionHash){
        $sql = "
			DELETE FROM ".$db->prefix."session
			WHERE sessionhash='".bkstr($sessionHash)."'
		";
        $db->query_write($sql);
    }

    public static function UserUpdateLastActive(Ab_Database $db, $userid, $ip){
        $sql = "
			UPDATE ".$db->prefix."user
			SET lastvisit='".TIMENOW."',
				ipadress='".bkstr($ip)."'
			WHERE userid='".bkint($userid)."'
			LIMIT 1
		";
        $db->query_write($sql, true);
    }
}
