<?php

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