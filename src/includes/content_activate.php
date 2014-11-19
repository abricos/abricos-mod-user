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
$v = &$brick->param->var;
$adress = Abricos::$adress;
$p_userid = bkint($adress->dir[2]);
$p_actid = bkint($adress->dir[3]);

$man = UserModule::$instance->GetManager();
$regManager = $man->GetRegistrationManager();
$error = $regManager->Activate($p_userid, $p_actid);

if ($error > 0) {
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