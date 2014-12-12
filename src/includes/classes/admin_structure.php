<?php

class UserItem_Admin extends UserItem {

    public $email;
    public $salt;
    public $passwordCrypt;
    public $emailconfirm;

    public function __construct(UserItem $user) {
        $d = $user->_data;
        parent::__construct($d);

        $this->email = strval($d['email']);
        $this->emailconfirm = intval($d['emailconfirm']) > 0;
        $this->salt = strval($d['salt']);
        $this->passwordCrypt = strval($d['password']);
    }

    public function ToAJAX() {
        $ret = parent::ToAJAX();
        $ret->email = $this->email;
        $ret->emailconfirm = $this->emailconfirm;
        $ret->groups = $this->GetGroupList();

        return $ret;
    }

    public function GetType() {
        return 'admin';
    }

}

?>