<?php

class UserOptionItem extends AbricosItem {

    public $value;

    public function __construct($d) {
        parent::__construct($d);
        $this->value = isset($d['val']) ? strval($d['val']) : '';
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