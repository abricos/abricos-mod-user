<?php

class UserItem_Session extends UserItem {

    public $session;

    public function __construct(UserItem $user){
        $d = $user->_data;
        parent::__construct($d);

        $this->session = UserModule::$instance->GetManager()->GetSessionManager()->key;
    }

    public function ToJSON(){
        $ret = parent::ToJSON();

        $ret->session = $this->session;
        $ret->groups = $this->GetGroupList();
        $ret->permission = $this->GetPermission();

        return $ret;
    }

}

?>