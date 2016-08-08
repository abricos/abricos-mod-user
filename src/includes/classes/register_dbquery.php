<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class UserQuery_Register
 */
class UserQuery_Register {

    public static function RegistrationActivateInfo(Ab_Database $db, $userid){
        $sql = "
			SELECT *
			FROM ".$db->prefix."useractivate
			WHERE userid=".bkint($userid)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function RegistrationActivateInfoByCode(Ab_Database $db, $code){
        $sql = "
			SELECT *
			FROM ".$db->prefix."useractivate
			WHERE activateid=".bkint($code)."
			LIMIT 1
		";
        return $db->query_first($sql);
    }


    /**
     * Активация пользователя
     *
     * @param Ab_Database $db
     * @param Integer $userid
     * @param Integer $activateId
     * @return Integer ошибка:
     *        0 - ошибки нет;
     *        1 - пользователь не найден;
     *        2 - пользователь уже активирован;
     *        3 - прочая ошибка
     */
    public static function RegistrationActivate(Ab_Database $db, $userid, $activateId){

        $actData = UserQuery_Register::RegistrationActivateInfo($db, $userid);

        if (empty($actData) || $actData['activateid'] != $activateId){
            return 3;
        }
        $sql = "
			UPDATE ".$db->prefix."user
			SET emailconfirm=1
			WHERE userid = ".bkint($userid)."
			LIMIT 1
		";
        $db->query_write($sql);
        UserQuery_Admin::UserGroupUpdate($db, $userid, array(UserModule::UG_REGISTERED));

        $db->query_write("
			DELETE FROM ".$db->prefix."useractivate
			WHERE useractivateid = ".bkint($actData['useractivateid'])."
		");

        return 0;
    }

    /**
     * Удалить учетки не прошедшии верификацию email спустя 7 дней
     *
     * @param Ab_Database $db
     */
    public static function RegistrationActivateClear(Ab_Database $db){
        $d = TIMENOW - 60 * 60 * 24 * 7;
        $aw = array();
        $sql = "
			SELECT userid
			FROM ".$db->prefix."user
			WHERE emailconfirm=0 AND joindate<".$d."
			LIMIT 250
		";
        $rows = $db->query_read($sql);
        while (($row = $db->fetch_array($rows))){
            array_push($aw, "userid=".$row['userid']);
        }

        if (count($aw) == 0){
            return;
        }
        $sql = "
			DELETE FROM ".$db->prefix."user
			WHERE emailconfirm=0 AND joindate<".$d."
		";
        $db->query_write($sql);

        $sql = "
			DELETE FROM ".$db->prefix."usergroup
			WHERE ".implode(" OR ", $aw)."
		";
        $db->query_write($sql);
        $sql = "
			DELETE FROM ".$db->prefix."useractivate
			WHERE ".implode(" OR ", $aw)."
		";
        $db->query_write($sql);
    }
}
