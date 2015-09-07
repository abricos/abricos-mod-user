<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Активация пользователя
 *
 * URL по типу http://mysite.com/user/activate/{userid}/{activeid}, где:
 * {userid} - идентификатор пользователя;
 * {activeid} - идентификатор активации.
 *
 * @ignore
 */

$brick = Brick::$builder->brick;
$v = &$brick->param->var;
$adress = Abricos::$adress;
$p_userid = bkint($adress->dir[2]);
$p_actid = bkint($adress->dir[3]);

$man = UserModule::$instance->GetManager();
$regManager = $man->GetRegistrationManager();
$error = $regManager->Activate($p_userid, $p_actid);

if ($error > 0){
    $v['result'] = Brick::ReplaceVarByData($v['err'], array(
        "err" => $v['err'.$error]
    ));
} else {
    $user = $man->User($p_userid);

    $v['result'] = Brick::ReplaceVarByData($v['ok'], array(
        "unm" => $user->username
    ));
}

?>