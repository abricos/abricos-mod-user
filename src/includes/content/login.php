<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


$brick = Brick::$builder->brick;
$userMod = Abricos::$user;
$userManager = $userMod->GetManager();

$p_login = Abricos::CleanGPC('p', 'login', TYPE_STR);
$p_pass = Abricos::CleanGPC('p', 'password', TYPE_STR);

$err = 0;
if (!empty($p_login) || !empty($p_pass)){
    $err = $userManager->Login($p_login, $p_pass);
    if ($err == 0){
        header('Location: /');
    }
}
if ($err == 0){
    $brick->param->var['err'] = '';
} else {
    $brick->param->var['err'] = Brick::ReplaceVar($brick->param->var['err'], 'err', $brick->param->var['e'.$err]);
}

?>