<?php
/**
 * Обработчик запросов клиента 
 * 
 * @version $Id$
 * @package Abricos
 * @subpackage User
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 * @ignore
 */

$userManager = UserModule::$instance->GetManager()->GetAuthManager();
$err = $userManager->Logout();
header('Location: /');

?>