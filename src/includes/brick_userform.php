<?php
/**
 * @package Abricos
 * @subpackage User
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$p = & $brick->param->param;
$v = & $brick->param->var;

$tplBosMenu = "";

$bosModule = Abricos::GetModule("bos");
if (!empty($bosModule)) {
    $modBrick = Brick::$builder->LoadBrickS("bos", "menu", $brick, array( "p" => array(
        "noWrap" => true
    )));
    $tplBosMenu = Brick::ReplaceVarByData($v["bosmenu"], array(
        "bosmenu" => $modBrick->content
    ));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "id" => $brick->id,
    "bosmenu" => $tplBosMenu,
    "username" => Abricos::$user->login
));

?>