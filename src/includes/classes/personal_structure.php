<?php

class UserPeronalItem extends UserItem {

    public $email;
    public $emailconfirm;

    public function __construct($d) {
        parent::__construct($d);

        $this->email = strval($d['email']);
        $this->emailconfirm = intval($d['emailconfirm']) > 0;
    }

    public function ToAJAX() {
        $ret = parent::ToAJAX();
        $ret->email = $this->email;
        $ret->emailconfirm = $this->emailconfirm ? 1 : 0;
        return $ret;
    }

}

?>