<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Восстановление пароля пользователя
 *
 * URL по типу http://mysite.com/user/passrec/{hash}, где:
 * {hash} - идентификатор восстановления пароля.
 *
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
