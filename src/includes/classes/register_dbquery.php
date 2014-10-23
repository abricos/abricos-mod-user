<?php

class UserQuery_Register {

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
    public static function UserAppend(Ab_Database $db, &$user, $groupid = User::UG_GUEST, $ip='', $agreement = false, $isVirtual = false){

        $db->query_write("
			INSERT INTO `".$db->prefix."user`
				(language, username, password, email, emailconfirm, joindate, salt, ipadress, agreement, isvirtual) VALUES (
				'".Abricos::$LNG."',
				'".bkstr($user['username'])."',
				'".bkstr($user['password'])."',
				'".bkstr($user['email'])."',
				".($groupid == User::UG_GUEST ? 0 : 1).",
				'".bkstr($user['joindate'])."',
				'".bkstr($user['salt'])."',
				'".bkstr($ip)."',
				".($agreement ? 1 : 0).",
				".($isVirtual ? 1 : 0)."
		)");
        $userid = $db->insert_id();

        UserQueryExt::UserGroupUpdate($db, $userid, array($groupid));

        if ($groupid != User::UG_GUEST){ return $userid; }

        $usernew = UserQuery::User($db, $userid);

        $user["userid"] = $userid;
        $user['activateid'] = cmsrand(0, 100000000);
        $sql = "
			INSERT INTO `".$db->prefix."useractivate`
				(userid, activateid, joindate) VALUES (
				'".bkint($userid)."',
				'".bkstr($user['activateid'])."',
				'".bkstr($user['joindate'])."'
		)";
        $db->query_write($sql);
        return $userid;
    }

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
     * 		0 - ошибки нет;
     * 		1 - пользователь не найден;
     * 		2 - пользователь уже активирован;
     * 		3 - прочая ошибка
     */
    public static function RegistrationActivate(Ab_Database $db, $userid, $activateId){

        $actData = UserQueryExt::RegistrationActivateInfo($db, $userid);

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
        UserQueryExt::UserGroupUpdate($db, $userid, array(User::UG_REGISTERED));

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
        $d = TIMENOW-60*60*24*7;
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

        if (count($aw) == 0){ return; }
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

?>