<?php

class UserOptionItem extends AbricosItem {

    public $value;

    public function __construct($d) {
        parent::__construct($d);
        $this->id = strval($d['optname']);
        $this->value = strval($d['optvalue']);
    }

    public function ToAJAX() {
        $ret = parent::ToAJAX();
        $ret->value = $this->value;
        return $ret;
    }
}

class UserOptionList extends AbricosList {

    /**
     * @param int $i
     * @return UserOptionItem
     */
    public function GetByIndex($i) {
        return parent::GetByIndex($i);
    }
}

?>