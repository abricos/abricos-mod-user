<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


/**
 * Class UserQuery
 */
class UserQuery {

    public static function UserById(Ab_Database $db, $userid){
        $sql = "
			SELECT u.userid as id, u.*
			FROM ".$db->prefix."user u
			WHERE userid='".bkint($userid)."'
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function UserByName(Ab_Database $db, $username, $orByEmail = false){
        $sql = "
			SELECT u.userid as id, u.*
			FROM ".$db->prefix."user u
			WHERE username='".bkstr($username)."'
				".($orByEmail ? " OR email='".bkstr($username)."'" : "")."
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    /**
     * @param Ab_Database $db
     * @param UserListConfig $config
     * @return int3
     */
    public static function UserList(Ab_Database $db, $config){
        $aw = array();
        if ($config->antibot){
            array_push($aw, "antibotdetect=0");
        }
        if (!empty($config->filter)){
            array_push($aw, "(username LIKE '%".bkstr($config->filter)."%' OR email LIKE '%".bkstr($config->filter)."%')");
        }
        $where = "";
        if (count($aw) > 0){
            $where = "WHERE ".implode(" AND ", $aw);
        }
        $sql = "
			SELECT count(u.userid) as cnt
			FROM ".$db->prefix."user u
			".$where."
		";
        $row = $db->query_first($sql);
        $config->SetTotal($row['cnt']);

        $sql = "
			SELECT u.userid as id, u.*
			FROM ".$db->prefix."user u
			".$where."
            ORDER BY CASE WHEN u.lastvisit>u.joindate THEN u.lastvisit ELSE u.joindate END DESC
            LIMIT ".$config->GetFrom().", ".$config->limit."
		";
        return $db->query_read($sql);

    }

    public static function UserGroupList(Ab_Database $db, $userid){
        $sql = "
			SELECT groupid as id
			FROM ".$db->prefix."usergroup
			WHERE userid=".bkint($userid)."
		";
        return $db->query_read($sql);
    }

    public static function GroupList(Ab_Database $db){
        $sql = "
			SELECT
				groupid as id,
				groupname as title,
				groupkey as sysname
			FROM ".$db->prefix."group
			ORDER BY groupid
		";
        return $db->query_read($sql);
    }

    public static function GroupRoleList(Ab_Database $db){
        $sql = "
			SELECT
				roleid as id,
				userid as gid,
				modactionid as maid,
				status as st
			FROM ".$db->prefix."userrole
			WHERE usertype = 0
		";
        return $db->query_read($sql);
    }


    public static function UserDomainUpdate(Ab_Database $db, $userid, $domain){
        $sql = "
			INSERT IGNORE INTO ".$db->prefix."userdomain (userid, `domain`) VALUES (
				".bkint($userid).",
				'".bkstr($domain)."'
			)
		";
        $db->query_write($sql);
    }



    // ********************************************************************
    // TODO: old functions
    // ********************************************************************


    public static function UserRole(Ab_Database $db, UserItem $user){
        if ($user->id === 0){
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
				WHERE ur.userid = ".bkint($user->id)." AND ur.usertype = 1
			";
            $gps = $user->GetGroupList();
            if (count($gps) > 0){
                $arr = array();
                for ($i = 0; $i < count($gps); $i++){
                    array_push($arr, "gp.groupid = ".$gps[$i]);
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

    public static function PermissionInstall(Ab_Database $db, Ab_UserPermission $permission){
        $modName = $permission->module->name;
        $actions = array();
        $rows = UserQuery::ModuleActionList($db, $modName);
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
                UserQuery::ModuleActionRemove($db, $row['id']);
            }
        }

        $asql = array();
        foreach ($permission->defRoles as $role){
            if (!empty($actions[$role->action])){
                continue;
            }
            array_push($asql, "('".$modName."', ".$role->action.")");
        }
        if (!empty($asql)){
            $sql = "INSERT IGNORE INTO ".$db->prefix."sys_modaction (`module`, `action`) VALUES ";
            $sql .= implode(",", $asql);
            $db->query_write($sql);
        }

        $rows = UserQuery::GroupList($db);
        $groups = array();
        while (($row = $db->fetch_array($rows))){
            if (empty($row['sysname'])){
                continue;
            }
            $groups[$row['sysname']] = $row['id'];
        }

        require_once 'classes/admin_dbquery.php';

        $rows = UserQuery::ModuleActionList($db, $modName);
        while (($row = $db->fetch_array($rows))){

            foreach ($permission->defRoles as $role){
                if (intval($row['act']) != intval($role->action)){
                    continue;
                }
                $groupid = isset($groups[$role->groupkey]) ? intval($groups[$role->groupkey]) : 0;
                if (empty($groupid)){
                    $i18n = $permission->module->I18n();

                    $groupname = $i18n->Translate('groups.'.$role->groupkey);
                    if (empty($groupname)){
                        $groupname = $role->groupkey;
                    }

                    $groupid = UserQuery_Admin::GroupAppend($db, $groupname, $role->groupkey);
                    $groups[$role->groupkey] = $groupid;
                }

                $sql = "
					INSERT IGNORE INTO ".$db->prefix."userrole
					(modactionid, usertype, userid, status) VALUES
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

    /**
     * Получить список действий модуля
     *
     * @param Ab_Database $db
     */
    public static function ModuleActionList(Ab_Database $db, $modName = ''){
        $where = "";
        if (!empty($modName)){
            $where = "WHERE module='".bkstr($modName)."'";
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

    public static function UserFieldList(Ab_Database $db){
        $sql = "SHOW COLUMNS FROM ".$db->prefix."user";
        return $db->query_read($sql);
    }


}


class UserQuery_old {


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
        if (empty($arr)){
            return;
        }

        $sql = "
			UPDATE ".$db->prefix."user
			SET ".implode(',', $arr)." 
			WHERE userid = ".bkint($userid)."
			LIMIT 1
		";
        $db->query_write($sql);
    }

    public static function UserGroupRemoveByKey(Ab_Database $db, $userid, $key){
        $group = UserQuery::GroupByKey($db, $key, true);
        if (empty($group)){
            return;
        }
        UserQuery::UserGroupRemove($db, $userid, $group['id']);
    }

    public static function UserGroupRemove(Ab_Database $db, $userid, $groupid){
        $sql = "
			DELETE FROM `".$db->prefix."usergroup`
			WHERE userid=".bkint($userid)." AND groupid=".bkint($groupid)."
		";
        $db->query_write($sql);
    }

    public static function UserGroupAppendByKey(Ab_Database $db, $userid, $key){
        $group = UserQuery::GroupByKey($db, $key, true);
        if (empty($group)){
            return;
        }
        UserQuery::UserGroupAppend($db, $userid, $group['id']);
    }


    public static function UserGroupList(Ab_Database $db, $page, $limit, $filter = '', $notbot = false){
        $from = (($page - 1) * $limit);

        $sql = "
			SELECT
				u.userid as uid, 
				ug.groupid as gid
			FROM (
				SELECT 
					userid
				FROM ".$db->prefix."user
				".UserQuery::BuildListWhere($filter, $notbot)."
				ORDER BY CASE WHEN lastvisit>joindate THEN lastvisit ELSE joindate END DESC
				LIMIT ".$from.",".bkint($limit)."
			) u
			LEFT JOIN ".$db->prefix."usergroup ug ON u.userid = ug.userid
		";
        return $db->query_read($sql);
    }

    public static function UserOnline(Ab_Database $db){
        $sql = "
			SELECT count( * ) AS cnt
			FROM (
				SELECT idhash
				FROM ".$db->prefix."session
				WHERE lastactivity > ".(TIMENOW - 60 * 5)."
				GROUP BY idhash
			)a		
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
}
