<?php
/**
 * @package Abricos
 * @subpackage Bos
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$brick = Brick::$builder->brick;
$p = & $brick->param->param;
$v = & $brick->param->var;

$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "id" => $brick->id
));

?>