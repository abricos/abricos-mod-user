<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$v = $brick->param->var;

$p_login = Abricos::CleanGPC('p', 'username', TYPE_STR);
$p_password = Abricos::CleanGPC('p', 'password', TYPE_STR);
$p_autologin = Abricos::CleanGPC('p', 'autologin', TYPE_STR) === 'on';

$result = array(
    'error' => '',
    'ok' => '',
    'loginform' => ''
);

$err = 0;
$isDoAuth = false;
if (Abricos::$user->id === 0){
    if (!empty($p_login) || !empty($p_password)){
        $isDoAuth = true;
        /** @var UserManager $manager */
        $manager = Abricos::GetModuleManager('user');
        $authManager = $manager->GetAuthManager();
        $err = $authManager->Login($p_login, $p_password, $p_autologin);

        if ($err){
            $i18n = Abricos::GetModule('user')->I18n();

            $result['auth'] = Brick::ReplaceVarByData($v['err'], array(
                'err' => $i18n->Translate('content.login.error.'.$err)
            ));
        }
    }
}

if (Abricos::$user->id > 0 || ($isDoAuth && !$err)){
    header('Location: /');
    $result['auth'] = $v['ok'];
} else if (!$isDoAuth || $err){
    $brickLoginForm = Brick::$builder->LoadBrickS('user', 'loginform');
    $result['loginform'] = Brick::ReplaceVarByData($v['form'], array(
        'form' => $brickLoginForm->content
    ));
}

$brick->content = Brick::ReplaceVarByData($brick->content, $result);


