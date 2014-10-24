<?php
/**
 * @package Abricos
 * @subpackage User 
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Внешнии запросы
 */
class UserQueryExt extends UserQuery {



    public static function User(Ab_Database $db, $userid) {
        $sql = "
			SELECT u.userid as id, u.*
			FROM ".$db->prefix."user u
			WHERE userid='".bkint($userid)."'
			LIMIT 1
		";
        return $db->query_first($sql);
    }









    ////////////////////////////////////////////////////////////////////
	//                      Запросы по пользователям                  //
	////////////////////////////////////////////////////////////////////
	


	
	public static function UserByEmail(Ab_Database $db, $email){
		$email = strtolower(trim($email));
		$sql = "
			SELECT *
			FROM ".$db->prefix."user
			WHERE email = '".bkstr($email)."'
		";
		return $db->query_first($sql);
	}

	
	/**
	 * Проверить наличие пользователя в базе по логину или эл. почте.
	 * Вернуть результат проверки:
	 * 0 - такого пользователя в базе нет,
	 * 1 - пользователь с таким логином уже зарегистрирован, 
	 * 2 - пользователь с таким email уже зарегистрирован
	 * 
	 * @param Ab_Database $db
	 * @param String $username
	 * @param String $email
	 * @return Integer
	 */
	public static function UserExists(Ab_Database $db, $username, $email){
		$email = strtolower($email);
		$username = htmlspecialchars_uni($username);
		
		$whereEMail = empty($email) ? "" : " OR email = '".bkstr($email)."'";
		
		$sql = "
			SELECT userid, username 
			FROM ".$db->prefix."user 
			WHERE username = '".bkstr($username)."' ".$whereEMail."
		";
		$row = $db->query_first($sql);
		
		if (empty($row)){ return 0; }
		if ($username == $row['username']){ return 1; }
		return 2;
	}
	
	public static function UserDomainUpdate(Ab_Database $db, $userid, $domain){
		$sql = "
			INSERT IGNORE INTO `".$db->prefix."userdomain` (`userid`, `domain`) VALUES (
				".bkint($userid).",
				'".bkstr($domain)."'
			)
		";
		$db->query_write($sql);
	}
	

	public static function TermsOfUseAgreement(Ab_Database $db, $userid){
		$sql = "
			UPDATE ".$db->prefix."user
			SET agreement=1
			WHERE userid=".bkint($userid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	


	public static function UserUpdate(Ab_Database $db, $userid, $data){
		$arr = array();
		foreach ($data as $key => $value){
			array_push($arr, $key."='".$value."'");
		}
		if (empty($arr)){ return; }
		
		$sql = "
			UPDATE ".$db->prefix."user
			SET ".implode(',', $arr)." 
			WHERE userid = ".bkint($userid)."
			LIMIT 1
		";
		$db->query_write($sql);
	}
	
	public static function UserGroupRemoveByKey(Ab_Database $db, $userid, $key){
		$group = UserQueryExt::GroupByKey($db, $key, true);
		if (empty($group)){ return; }
		UserQueryExt::UserGroupRemove($db, $userid, $group['id']);
	}
	
	public static function UserGroupRemove(Ab_Database $db, $userid, $groupid){
		$sql = "
			DELETE FROM `".$db->prefix."usergroup`
			WHERE userid=".bkint($userid)." AND groupid=".bkint($groupid)."
		";
		$db->query_write($sql);
	}
	
	public static function UserGroupAppendByKey(Ab_Database $db, $userid, $key){
		$group = UserQueryExt::GroupByKey($db, $key, true);
		if (empty($group)){ return; }
		UserQueryExt::UserGroupAppend($db, $userid, $group['id']);
	}
	
	public static function UserGroupAppend(Ab_Database $db, $userid, $groupid){
		$sql = "
			INSERT IGNORE INTO `".$db->prefix."usergroup` (`userid`, `groupid`) VALUES 
			(".bkint($userid).",".bkint($groupid).")
		";
		$db->query_write($sql);
	}
	
	public static function UserGroupUpdate(Ab_Database $db, $userid, $groups){
		$sql = "
			DELETE FROM `".$db->prefix."usergroup`
			WHERE userid=".bkint($userid)."
		";
		$db->query_write($sql);
		
		$arr = array();
		foreach ($groups as $gp){
			array_push($arr, "(".bkint($userid).",".bkint($gp).")");
		}
		if (count($arr) < 1){ return; }
		
		$sql = "
			INSERT IGNORE INTO `".$db->prefix."usergroup` (`userid`, `groupid`) VALUES 
			".implode(',', $arr)."
		";
		$db->query_write($sql);
	}
	
	private static function BuildListWhere ($filter = '', $notbot = false){
		$aw = array();
		if ($notbot){
			array_push($aw, "antibotdetect=0");
		}
		if (!empty($filter)){
			array_push($aw, "(username LIKE '%".bkstr($filter)."%' OR email LIKE '%".bkstr($filter)."%')");
		}
		$where = "";
		if (count($aw)>0){
			$where = "WHERE ".implode(" AND ", $aw);
		}
		return $where;
	}
	
	public static function UserGroupList(Ab_Database $db, $page, $limit, $filter = '', $notbot = false){
		$from = (($page-1)*$limit);

		$sql = "
			SELECT
				u.userid as uid, 
				ug.groupid as gid
			FROM (
				SELECT 
					userid
				FROM ".$db->prefix."user
				".UserQueryExt::BuildListWhere($filter, $notbot)."
				ORDER BY CASE WHEN lastvisit>joindate THEN lastvisit ELSE joindate END DESC
				LIMIT ".$from.",".bkint($limit)."
			) u
			LEFT JOIN ".$db->prefix."usergroup ug ON u.userid = ug.userid
		";
		return $db->query_read($sql);
	}

	public static function UserList(Ab_Database $db, $page, $limit, $filter = '', $notbot = false){
		$from = (($page-1)*$limit);
		
		$sql = "
			SELECT 
				userid as id, 
				username as unm,
				email as eml,
				joindate as dl,
				lastvisit as vst
			FROM ".$db->prefix."user
			".UserQueryExt::BuildListWhere($filter, $notbot)."
			ORDER BY CASE WHEN lastvisit>joindate THEN lastvisit ELSE joindate END DESC
			LIMIT ".$from.",".bkint($limit)."
		";
		return $db->query_read($sql);
	}
	
	public static function UserCount(Ab_Database $db, $filter = '', $notbot = false){
		$sql = "
			SELECT COUNT(userid) as cnt
			FROM ".$db->prefix."user
			".UserQueryExt::BuildListWhere($filter, $notbot)."
			LIMIT 1
		";
		return $db->query_read($sql);
	}
	
	public static function UserListAll(Ab_Database $db){
		$sql = "
			SELECT 
				userid as id, 
				username as unm,
				email as eml,
				joindate as dl,
				lastvisit as vst
			FROM ".$db->prefix."user
		";
		return $db->query_read($sql); 		
	}
	
	public static function UserOnline(Ab_Database $db){
		$sql = "
			SELECT count( * ) AS cnt
			FROM (
				SELECT idhash
				FROM ".$db->prefix."session
				WHERE lastactivity > ".(TIMENOW-60*5)."
				GROUP BY idhash
			)a		
		";
		return $db->query_read($sql);
	}
	
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
	
	
	////////////////////////////////////////////////////////////////////
	//                       Общедоступные запросы                    //
	////////////////////////////////////////////////////////////////////
	
	/**
	 * Получить список действий модуля
	 * 
	 * @param Ab_Database $db
	 */
	public static function ModuleActionList(Ab_Database $db, $modname = ''){
		$where = "";
		if (!empty($modname)){
			$where = "WHERE module='".bkstr($modname)."'";
		}
		$sql = "
			SELECT 
				modactionid as id,
				module as md,
				action as act 
			FROM ".$db->prefix."sys_modaction
			".$where."
			ORDER BY module, action
		";
		return $db->query_read($sql);
	}
	
	////////////////////////////////////////////////////////////////////
	//                       Административные запросы                 //
	////////////////////////////////////////////////////////////////////

	/**
	 * Список ролей (ID роли, ID действия, статус)
	 * 
	 * @param Ab_Database $db
	 * @param integer $groupid если $usertype=0, то роль группы, иначе роль пользователя 
	 * @param integer $usertype 
	 */
	public static function RoleList(Ab_Database $db, $groupid, $usertype = 0){
		$sql = "
			SELECT 
				roleid as id,
				modactionid as maid,
				status as st
			FROM ".$db->prefix."userrole
			WHERE userid=".bkint($groupid)." AND usertype=".bkint($usertype)."
		";
		return $db->query_read($sql);
	}
	
	public static function RoleAppend(Ab_Database $db, $groupid, $d){
		$sql = "
			INSERT IGNORE INTO ".$db->prefix."userrole 
			(`modactionid`, `usertype`, `userid`, `status`) VALUES (
			'".$d->maid."', 
			0,
			".$groupid.",
			".$d->st."
		)";
		$db->query_write($sql);
	}
	
	public static function RoleRemove(Ab_Database $db, $roleid){
		$sql = "
			DELETE FROM ".$db->prefix."userrole
			WHERE roleid=".bkint($roleid)."
		";
		$db->query_write($sql);
	}
	
	public static function PermissionInstall(Ab_Database $db, Ab_UserPermission $permission){
		$modname = $permission->module->name;
		$actions = array();
		$rows = UserQueryExt::ModuleActionList($db, $modname);
		while (($row = $db->fetch_array($rows))){

			$find = false;
			foreach ($permission->defRoles as $role){
				if (intval($role->action) == intval($row['act'])){
					$find = true;
					break;
				}
			}
			if ($find){
				$actions[$row['act']] = $row;
			} else {
				// action был удален, надо его зачистить на в базе
				UserQueryExt::ModuleAction($db, $row['id']);
			}
		}
		
		$asql = array();
		foreach ($permission->defRoles as $role){
			if (!empty($actions[$role->action])){ continue; }
			array_push($asql, "('".$modname."', ".$role->action.")");
		}
		if (!empty($asql)){
			$sql = "INSERT IGNORE INTO ".$db->prefix."sys_modaction (`module`, `action`) VALUES ";
			$sql .= implode(",", $asql);
			$db->query_write($sql);
		}

		$rows = UserQueryExt::GroupList($db);
		$groups = array();
		while (($row = $db->fetch_array($rows))){
			if (empty($row['k'])){ continue; }
			$groups[$row['k']] = $row['id'];
		}
		
		$rows = UserQueryExt::ModuleActionList($db, $modname);
		while (($row = $db->fetch_array($rows))){
			
			foreach ($permission->defRoles as $role){
				if (intval($row['act']) != intval($role->action)){ continue; }
				$groupid = intval($groups[$role->groupkey]);
				if (empty($groupid)){
					
					$groupname = $permission->module->lang['groups'][$role->groupkey];
					if (empty($groupname)){
						$groupname = $role->groupkey;
					}
					
					$groupid = UserQueryExt::GroupAppend($db, $groupname, $role->groupkey);
					$groups[$role->groupkey] = $groupid;
				}
				
				$sql = "
					INSERT IGNORE INTO ".$db->prefix."userrole 
					(`modactionid`, `usertype`, `userid`, `status`) VALUES 
					('".$row['id']."', 0, ".$groupid.", ".$role->status.")
				";
				$db->query_write($sql);
			}
			
		}
	}
	
	public static function PermissionRemove(Ab_Database $db, Ab_UserPermission $permission){
		$rows = $db->query_read("
			SELECT 
				modactionid as id,
				action
			FROM ".$db->prefix."sys_modaction
			WHERE module = '".$permission->module->name."'
		");
		while (($row = $db->fetch_array($rows))){
			$sql = "
				DELETE FROM ".$db->prefix."userrole
				WHERE modactionid=".bkint($row['id'])."
			";
			$db->query_write($sql);
		}
		$sql = "
			DELETE FROM ".$db->prefix."sys_modaction
			WHERE module = '".$permission->module->name."'
		";
		$db->query_write($sql);
	}
	
	public static function ModuleActionRemove(Ab_Database $db, $modactionid){
		$sql = "
			DELETE FROM ".$db->prefix."userrole
			WHERE modactionid=".bkint($modactionid)."
		";
		$db->query_write($sql);
		
		$sql = "
			DELETE FROM ".$db->prefix."sys_modaction
			WHERE modactionid=".bkint($modactionid)."
		";
		$db->query_write($sql);
	}
	
	public static function GroupByKey(Ab_Database $db, $key, $retarray = false){
		$sql = "
			SELECT 
				groupid as id, 
				groupname as nm,
				groupkey as k
			FROM ".$db->prefix."group
			WHERE groupkey='".bkstr($key)."'
			LIMIT 1
		";
		return $retarray ? $db->query_first($sql) : $db->query_read($sql);
	}
	
	public static function GroupList(Ab_Database $db){
		$sql = "
			SELECT 
				groupid as id, 
				groupname as nm,
				groupkey as k
			FROM ".$db->prefix."group
		";
		return $db->query_read($sql);
	}

	public static function GroupCount(Ab_Database $db){
		$sql = "
			SELECT COUNT(groupid) as cnt 
			FROM ".$db->prefix."group
			LIMIT 1
		";
		return $db->query_read($sql); 
	}
	
	public static function GroupAppend(Ab_Database $db, $name, $key = ''){
		$sql = "
			INSERT INTO ".$db->prefix."group (`groupname`, `groupkey`) VALUES (
				'".bkstr($name)."',
				'".bkstr($key)."'
			)
		";
		$db->query_write($sql); 
		return $db->insert_id();
	}
	
	public static function GroupUpdate(Ab_Database $db, $d){
		$sql = "
			UPDATE ".$db->prefix."group 
			SET groupname = '".bkstr($d->nm)."'
			WHERE groupid = ".bkint($d->id)."
		";
		$db->query_write($sql); 
	}
	
	public static function UserFieldList (Ab_Database $db){
		$sql = "SHOW COLUMNS FROM ".$db->prefix."user";
		return $db->query_read($sql);
	}
	
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

/**
 * Часто запрашиваемые запросы (для внутреннего использования)
 *
 * @package Abricos
 */
class UserQuery {

    /**
     * Вернуть полные данные пользователя для внутренних функций.
     *
     * @param Ab_Database $db
     * @param integer $userid
     * @return array
     */
    public static function User(Ab_Database $db, $userid) {
        $userid = bkint($userid);
        if ($userid < 1) {
            return;
        }
        $sql = "
			SELECT *
			FROM ".$db->prefix."user
			WHERE userid='".bkint($userid)."'
			LIMIT 1
		";
        $user = $db->query_first($sql);
        if (empty($user)) {
            return;
        }
        $user['group'] = UserQuery::GroupByUserId($db, $user['userid']);
        return $user;
    }

    public static function UserByName(Ab_Database $db, $username, $orByEmail = false) {
        $sql = "
			SELECT *
			FROM ".$db->prefix."user
			WHERE username='".bkstr($username)."'
				".($orByEmail ? " OR email='".bkstr($username)."'" : "")."
			LIMIT 1
		";
        $user = $db->query_first($sql);
        if (empty($user)) {
            return;
        }
        $user['group'] = UserQuery::GroupByUserId($db, $user['userid']);
        return $user;
    }

    public static function GroupByUserId(Ab_Database $db, $userid) {
        $rows = $db->query_read("
			SELECT
				groupid as id
			FROM ".$db->prefix."usergroup
			WHERE userid=".bkint($userid)."
		");
        $ret = array();
        while (($row = $db->fetch_array($rows))) {
            array_push($ret, $row['id'] * 1);
        }
        return $ret;
    }

    public static function UserRole(Ab_Database $db, $user) {
        if ($user['userid'] == 0) {
            $sql = "
				SELECT
					ma.module as md,
					ma.action as act,
					ur.status as st
				FROM ".$db->prefix."userrole ur
				LEFT JOIN ".$db->prefix."sys_modaction ma ON ur.modactionid = ma.modactionid
				WHERE ur.userid = 1 AND ur.usertype = 0
			";
        } else {
            $sql = "
				SELECT
					ma.module as md,
					ma.action as act,
					ur.status as st
				FROM ".$db->prefix."userrole ur
				LEFT JOIN ".$db->prefix."sys_modaction ma ON ur.modactionid = ma.modactionid
				WHERE ur.userid = ".bkint($user['userid'])." AND ur.usertype = 1
			";
            $gps = $user['group'];
            if (count($gps) > 0) {
                $arr = array();
                foreach ($gps as $gp) {
                    array_push($arr, "gp.groupid = ".$gp);
                }
                $sql .= "
					UNION
					SELECT
						ma.module as md,
						ma.action as act,
						ur.status as st
					FROM ".$db->prefix."userrole ur
					LEFT JOIN ".$db->prefix."sys_modaction ma ON ur.modactionid = ma.modactionid
					LEFT JOIN ".$db->prefix."group gp ON gp.groupid = ur.userid
					WHERE ur.usertype = 0 AND (".implode(' OR ', $arr).")
				";
            }
        }
        return $db->query_read($sql);
    }
}


?>