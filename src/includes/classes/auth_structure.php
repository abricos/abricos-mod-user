<?php

class UserItem_Auth extends UserItem {

    public $salt;
    public $emailconfirm;
    public $password;

    public function __construct(UserItem $user) {
        $d = $user->_data;
        parent::__construct($d);

        $this->emailconfirm = $d['emailconfirm'] > 0;
        $this->salt = strval($d['salt']);
        $this->password = strval($d['password']);
    }

}

?>