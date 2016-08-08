<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$siteName = SystemModule::$instance->GetPhrases()->Get("site_name");

$brick = Brick::$builder->brick;
$brick->content = Brick::ReplaceVarByData($brick->content, array(
    "constsitename" => $siteName,
    "consthost" => $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST']
));
