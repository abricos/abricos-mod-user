<?php

require_once 'private_structure.php';
require_once 'private_dbquery.php';

/**
 * Class UserManager_Private
 */
class UserManager_Private {

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

}

?>