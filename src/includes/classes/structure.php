<?php

class UserItem extends AbricosItem {

    public $username;
    public $joindate;
    public $lastvisit;

    public function __construct($d) {
        parent::__construct($d);

        $this->username = strval($d['username']);
        $this->joindate = intval($d['joindate']);
        $this->lastvisit = intval($d['lastvisit']);
    }

    public function ToAJAX() {
        $ret = parent::ToAJAX();
        $ret->username = $this->username;
        $ret->joindate = $this->joindate;
        $ret->lastvisit = $this->lastvisit;
        return $ret;
    }
}

class UserList extends AbricosList {
}

?>