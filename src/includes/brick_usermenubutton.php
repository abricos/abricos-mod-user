<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$p = &$brick->param->param;
$v = &$brick->param->var;

if (Abricos::$user->id === 0){
    $modBrick = Brick::$builder->LoadBrickS("user", "loginform", $brick);

    $content = Brick::ReplaceVarByData($v['guest'], array(
        "loginform" => $modBrick->content
    ));
} else {

    $modBrick = Brick::$builder->LoadBrickS("user", "userform", $brick);

    $content = Brick::ReplaceVarByData($v['user'], array(
        "username" => Abricos::$user->username,
        "userform" => $modBrick->content
    ));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "result" => $content,
    "id" => $brick->id
));

?>