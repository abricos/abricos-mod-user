<?php

class UserItem_Session extends UserItem {

    public function __construct(UserItem $user) {
        $d = $user->_data;
        parent::__construct($d);
    }

    public function ToAJAX(){
        $ret = parent::ToAJAX();

        $ret->groups = $this->GetGroupList();
        $ret->permission = $this->GetPermission();

        return $ret;
    }

}

?>