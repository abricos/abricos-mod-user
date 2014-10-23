<?php

class UserPublicQuery {

    public static function User(Ab_Database $db, $userid) {
        $sql = "
			SELECT
				userid as id,
				username,
				joindate,
				lastvisit
			FROM ".$db->prefix."user
			WHERE userid='".bkint($userid)."'
			LIMIT 1
		";
        return $db->query_first($sql);
    }



}

?>