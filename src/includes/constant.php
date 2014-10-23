<?php
/**
 * @package Abricos
 * @subpackage User
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 * @ignore
 */

$brick = Brick::$builder->brick;
$brick->content = Brick::ReplaceVarByData($brick->content, array(
	"constsitename" => Brick::$builder->phrase->Get('sys', 'site_name'),
	"consthost" => $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST']
)); 

?>