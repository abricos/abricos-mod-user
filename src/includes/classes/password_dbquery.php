<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class UserQuery_Password
 */
class UserQuery_Password {

    /**
     * Кол-во отправленых писем по восстановлению пароля юзеру
     */
    public static function PasswordSendCount(Ab_Database $db, $userid){
        $row = $db->query_first("
			SELECT counteml
			FROM ".$db->prefix."userpwdreq
			WHERE userid='".bkint($userid)."'
			LIMIT 1
		");
        if (empty($row)){
            return 0;
        }
        return $row['counteml'];
    }

    public static function PasswordRequestCreate(Ab_Database $db, $userid, $hash){
        $sql = "
			INSERT ".$db->prefix."userpwdreq (userid, hash, dateline, counteml) VALUES
			(
				".bkint($userid).",
				'".bkstr($hash)."',
				".TIMENOW.",
				1
			)
		";
        $db->query_write($sql);
    }

    public static function PasswordRequestCheck(Ab_Database $db, $hash){
        $sql = "
			SELECT *
			FROM ".$db->prefix."userpwdreq
			WHERE hash = '".bkstr($hash)."'
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function PasswordChange(Ab_Database $db, $userid, $newpass){
        $db->query_write("
			UPDATE ".$db->prefix."user
			SET password = '".$newpass."'
			WHERE userid = ".bkint($userid)."
			LIMIT 1
		");

        $db->query_write("
			DELETE FROM ".$db->prefix."userpwdreq
			WHERE userid = ".bkint($userid)."
		");
    }
}
