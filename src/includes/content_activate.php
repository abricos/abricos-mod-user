<?php
/**
 * Активация пользователя
 * 
 * URL по типу http://mysite.com/user/activate/{userid}/{activeid}, где:
 * {userid} - идентификатор пользователя;
 * {activeid} - идентификатор активации.
 * 
 * @version $Id$
 * @package Abricos
 * @subpackage User
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 * @ignore
 */

$brick = Brick::$builder->brick;
$adress = Abricos::$adress;
$p_userid = bkint($adress->dir[2]);
$p_actid =  bkint($adress->dir[3]);

$regManager = Abricos::$user->GetManager()->GetRegistrationManager();

$error = $regManager->Activate($p_userid, $p_actid);

if ($error > 0){
	$brick->param->var['result'] = Brick::ReplaceVarByData($brick->param->var['err'], array(
		"err" => $brick->param->var['err'.$error]
	)); 
}else{
    $user = UserQuery::User(Brick::$db, $p_userid);

    $brick->param->var['result'] = Brick::ReplaceVarByData($brick->param->var['ok'], array(
		"unm" => $user['username']
	));
}


?>