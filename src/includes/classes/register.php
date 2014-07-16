<?php

/**
 * Class UserRegistration
 */
class UserRegistration {

    /**
     * @var UserManager
     */
    public $manager;

    /**
     * @var Ab_Database
     */
    public $db;

    public function __construct(UserManager $manager) {
        $this->manager = $manager;
        $this->db = $manager->db;
    }

    public function AJAX($d) {
        switch ($d->do) {
            case "register":
                return $this->RegisterToAJAX($d->savedata);
            case "activate":
                return $this->ActivateToAJAX($d->savedata);

            case "useremailcnfsend":
                return $this->ConfirmEmailSendAgain($d->userid);

        }
        return null;
    }

    /**
     * Проверка регистрационных данных
     *
     * @param $username
     * @param $email
     * @param bool $checkEMail
     * @return int
     */
    public function RegistrationValidate($username, $email, $checkEMail = true) {
        if ($checkEMail && !UserManager::EmailValidate($email)) {
            return 4;
        }
        if (!UserManager::UserNameValidate($username)) {
            return 3;
        } else {
            $retCode = UserQueryExt::UserExists($this->manager->db, $username, $email);
            if ($retCode > 0) {
                return $retCode;
            }
        }

        return 0;
    }

    /**
     * Создать "Соль" пароля
     *
     * @return string
     */
    public function SaltGenerate() {
        $salt = '';
        for ($i = 0; $i < 3; $i++) {
            $salt .= chr(rand(32, 126));
        }
        return $salt;
    }

    public function RegisterToAJAX($d) {
        $result = $this->Register($d->username, $d->password, $d->email, true, true);

        $ret = new stdClass();
        if (is_integer($result)){
            $ret->err = $result;
        }else{
            $ret->userid = $result->userid;
        }

        return $ret;
    }

    /**
     * Регистриция пользователя. В случае неудачи вернуть код ошибки:
     * 1 - пользователь с таким логином уже зарегистрирован,
     * 2 - пользователь с таким email уже зарегистрирован
     * 3 - ошибка в имени пользователя,
     * 4 - ошибка в emial
     *
     * @param String $username
     * @param String $password
     * @param String $email
     * @param Boolean $sendEMail
     * @return Integer|Object
     */
    public function Register($username, $password, $email, $sendEMail = true, $checkEMail = true) {
        $retCode = $this->RegistrationValidate($username, $email, $checkEMail);
        if ($retCode > 0) {
            return $retCode;
        }

        $salt = $this->SaltGenerate();

        $user = array();
        $user["username"] = $username;
        $user["joindate"] = TIMENOW;
        $user["salt"] = $salt;
        $user["password"] = UserManager::UserPasswordCrypt($password, $salt);
        $user["email"] = $email;

        // Добавление пользователя в базу
        if ($this->manager->IsAdminRole()) {
            $userid = UserQueryExt::UserAppend($this->manager->db, $user, User::UG_REGISTERED);
        } else {
            $userid = UserQueryExt::UserAppend($this->manager->db, $user, User::UG_GUEST, $_SERVER['REMOTE_ADDR'], true);
            Abricos::$user->AntibotUserDataUpdate($userid);
            $this->manager->UserDomainUpdate($userid);
        }
        $ret = new stdClass();
        $ret->userid = $userid;

        if (!$sendEMail) {
            return $ret;
        }

        $this->ConfirmEmailSend($user);

        return $ret;
    }

    /**
     * Отправить письмо активации пользователю
     *
     * @param $user
     */
    private function ConfirmEmailSend($user) {
        $host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
        $link = "http://".$host."/user/activate/".$user["userid"]."/".$user["activateid"]."/";

        $brick = Brick::$builder->LoadBrickS('user', 'templates', null, null);

        $subject = $brick->param->var['reg_mailconf_subj'];
        $body = nl2br(Brick::ReplaceVarByData($brick->param->var['reg_mailconf'], array(
            "actcode" => $user["activateid"],
            "username" => $user['username'],
            "link" => $link,
            "sitename" => Brick::$builder->phrase->Get('sys', 'site_name')
        )));

        Abricos::Notify()->SendMail($user["email"], $subject, $body);
    }

    public function ConfirmEmailSendAgain($userid) {
        if (!$this->manager->IsAdminRole()) {
            return;
        }
        $user = UserQueryExt::User($this->manager->db, $userid);
        $actinfo = UserQueryExt::RegistrationActivateInfo($this->manager->db, $userid);
        $user['activateid'] = $actinfo['activateid'];
        $this->ConfirmEmailSend($user);
    }

    public function ActivateToAJAX($d) {
        $ret = new stdClass();
        $ret->err = $this->Activate($d->userid, $d->code);
        return $ret;
    }

    /**
     * Активировать нового пользователя.
     * В случае неудачи вернуть код ошибки:
     * 0 - ошбики нет,
     * 1 - пользователь не найден,
     * 2 - пользователь уже активирован
     * 3 - прочая ошибка
     *
     * @param integer $userid идентификатор пользователя
     * @param integer $code код активации
     * @return stdClass
     */
    public function Activate($userid, $code = 0) {
        if (empty($userid)) {
            $row = UserQueryExt::RegistrationActivateInfoByCode($this->db, $code);
            if (empty($row)) {
                sleep(1);
            } else {
                $userid = $row['userid'];
            }
        }

        $user = UserQuery::User($this->db, $userid);
        if (empty($user)) {
            return 1;
        } else if (intval($user['emailconfirm']) === 1) {
            return 2;
        }
        if ($code === 0) {
            if (!$this->IsAdminRole()) {
                return 0;
            }
            $row = UserQueryExt::RegistrationActivateInfo($this->db, $userid);
            $code = $row['activateid'];
        }

        return UserQueryExt::RegistrationActivate($this->db, $userid, $code);
    }

}

?>