<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$userManager = UserModule::$instance->GetManager()->GetAuthManager();
$err = $userManager->Logout();
header('Location: /');

?>