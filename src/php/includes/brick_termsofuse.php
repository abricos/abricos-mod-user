<?php
/**
 * @package Abricos
 * @subpackage User
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$siteName = SystemModule::$instance->GetPhrases()->Get("site_name");

$brick = Brick::$builder->brick;
$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "constsitename" => $siteName,
    "consthost" => $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST']
));

?>