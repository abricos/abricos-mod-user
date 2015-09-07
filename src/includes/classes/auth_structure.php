<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class UserItem_Auth
 */
class UserItem_Auth extends UserItem {

    public $salt;
    public $email;
    public $emailconfirm;
    public $password;
    public $activateid;

    public function __construct(UserItem $user) {
        $d = $user->_data;
        parent::__construct($d);

        $this->email = strval($d['email']);
        $this->emailconfirm = $d['emailconfirm'] > 0;
        $this->salt = strval($d['salt']);
        $this->password = strval($d['password']);
    }

}

?>