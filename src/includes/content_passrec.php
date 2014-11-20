<?php
/**
 * Восстановление пароля пользователя
 * 
 * URL по типу http://mysite.com/user/passrec/{hash}, где:
 * {hash} - идентификатор восстановления пароля.
 * 
 * @package Abricos
 * @subpackage User
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 * @ignore
 */

$brick = Brick::$builder->brick;
$v = &$brick->param->var;

$p_hash = bkstr(Abricos::$adress->dir[2]);

$passMan = UserModule::$instance->GetManager()->GetPasswordManager();
$ret = $passMan->PasswordRequestCheck($p_hash);

$result = $v['err'];
if ($ret->error === 0){
    $result = Brick::ReplaceVarByData($v['ok'], array(
		"email" => $ret->email
	));
}
$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "result" => $result,
    "brickid" => $brick->id
));

?>