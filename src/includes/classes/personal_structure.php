<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


/**
 * Class UserOptionItem
 */
class UserOptionItem extends AbricosItem {

    public $value;

    public function __construct($d){
        parent::__construct($d);
        $this->value = isset($d['val']) ? strval($d['val']) : '';
    }

    public function ToAJAX(){
        $ret = parent::ToAJAX();
        $ret->value = $this->value;
        return $ret;
    }
}

class UserOptionList extends AbricosList {

    /**
     * @param string $optionName
     * @return UserOptionItem
     */
    public function Get($optionName){
        return parent::Get($optionName);
    }

    /**
     * @param int $i
     * @return UserOptionItem
     */
    public function GetByIndex($i){
        return parent::GetByIndex($i);
    }
}
