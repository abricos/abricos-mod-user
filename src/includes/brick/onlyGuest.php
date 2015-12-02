<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
if (Abricos::$user->id > 0){
    return $brick->content = "";
}

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "brickid" => $brick->id
));

?>