<?php

class UserItem_Admin extends UserItem {

    const TYPE = 'admin';

    public $email;

    public function __construct(UserItem $user) {
        $d = $user->_data;
        parent::__construct($d);

        $this->email = strval($d['email']);
    }

    public function ToAJAX(){
        $ret = parent::ToAJAX();
        $ret->email = $this->email;

        $ret->groups = $this->GetGroupList();

        return $ret;
    }

}

?>