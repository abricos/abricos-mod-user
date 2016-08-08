<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class UserQuery_Personal
 */
class UserQuery_Personal {

    public static function UserOptionList(Ab_Database $db, $userid, $module){
        $sql = "
			SELECT  c.optname as id, c.optvalue as val
			FROM ".$db->prefix."userconfig c
			WHERE c.userid=".bkint($userid)." AND c.module='".bkstr($module)."'
		";
        return $db->query_read($sql);
    }

    public static function UserOptionSave(Ab_Database $db, $userid, $module, $name, $value){
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
}
