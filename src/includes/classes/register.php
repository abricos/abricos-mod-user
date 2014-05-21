<?php

/**
 * Class UserRegistration
 */
class UserRegistration {

    /**
     * @var UserManager
     */
    public $manager;

    public function __construct(UserManager $manager) {
        $this->manager = $manager;
    }

    public function AJAX($d) {
        switch ($d->do) {
            case "register":
                return $this->RegisterToAJAX($d->savedata);
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
        $ret = new stdClass();
        $ret->err = $this->Register($d->username, $d->password, $d->email, true, true);

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
     * @return Integer
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
            UserQueryExt::UserAppend($this->manager->db, $user, User::UG_REGISTERED);
        } else {
            $userid = UserQueryExt::UserAppend($this->manager->db, $user, User::UG_GUEST, $_SERVER['REMOTE_ADDR'], true);
            Abricos::$user->AntibotUserDataUpdate($userid);
            $this->manager->UserDomainUpdate($userid);
        }

        if (!$sendEMail) {
            return 0;
        }

        $this->ConfirmEmailSend($user);

        return 0;
    }

    /**
     * Отправить письмо активации пользователю
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
        if (!$this->IsAdminRole()) {
            return;
        }
        $user = UserQueryExt::User($this->manager->db, $userid);
        $actinfo = UserQueryExt::RegistrationActivateInfo($this->manager->db, $userid);
        $user['activateid'] = $actinfo['activateid'];
        $this->ConfirmEmailSend($user);
    }

}

?>