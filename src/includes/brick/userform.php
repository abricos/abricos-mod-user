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

$tplBosMenu = "";

$bosModule = Abricos::GetModule("bos");
if (!empty($bosModule)){
    $modBrick = Brick::$builder->LoadBrickS("bos", "menu", $brick, array(
        "p" => array(
            "noWrap" => true,
            "noChild" => true,
            "noBosUI" => true
        )
    ));
    $tplBosMenu = Brick::ReplaceVarByData($v["bosmenu"], array(
        "bosmenu" => $modBrick->content
    ));
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "id" => $brick->id,
    "bosmenu" => $tplBosMenu,
    "username" => Abricos::$user->username
));

?>