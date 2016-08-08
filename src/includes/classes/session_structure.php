<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Class UserItem_Session
 */
class UserItem_Session extends UserItem {

    public function __construct(UserItem $user){
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
