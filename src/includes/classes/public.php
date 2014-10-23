<?php

require_once 'personal_structure.php';
require_once 'personal_dbquery.php';

/**
 * Class UserPersonalManager
 */
class UserPersonalManager {

    /**
     * @var User
     */
    public $module;

    /**
     * @var UserManager
     */
    public $manager;

    /**
     * @var Ab_Database
     */
    public $db;

    public function __construct(UserManager $manager) {
        $this->module = $manager->module;
        $this->manager = $manager;
        $this->db = $manager->db;
    }

    public function AJAX($d) {
        switch ($d->do) {
            // case "userlist":
            //    return $this->UserListToAJAX($d->savedata);
        }
        return null;
    }

}

?>