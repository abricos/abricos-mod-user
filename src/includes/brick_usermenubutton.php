<?php
/**
 * @package Abricos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$p = & $brick->param->param;
$v = & $brick->param->var;

if (Abricos::$user->id === 0) {
    $modBrick = Brick::$builder->LoadBrickS("user", "loginform", $brick);

    $content = Brick::ReplaceVarByData($v['guest'], array(
        "loginform" => $modBrick->content
    ));
} else {
    $content = Brick::ReplaceVarByData($v['user'], array(
        "username" => Abricos::$user->login
    ));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "result" => $content,
    "id" => $brick->id
));

?>