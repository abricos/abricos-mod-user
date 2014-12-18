<?php

require_once 'admin_dbquery.php';
require_once 'register_dbquery.php';
require_once 'auth_structure.php';

/**
 * Class UserManager_Registration
 */
class UserManager_Registration {

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
                return $this->RegisterToAJAX($d->register);
            case "activate":
                return $this->ActivateToAJAX($d->activate);
            case "useremailcnfsend":
                return $this->ConfirmEmailSendAgain($d->userid);
            case "termsOfUse":
                return $this->TermsOfUseToAJAX();
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
        }

        $user = $this->manager->UserByName($email, true);
        if (!empty($user)) {
            return 2;
        }

        $user = $this->manager->UserByName($username, false);
        if (!empty($user)) {
            return 1;
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
        if (is_integer($result)) {
            $ret->err = $result;
        } else {
            $ret->register = new stdClass();
            $ret->register->userid = $result->userid;
        }

        return $ret;
    }

    /**
     * Регистриция пользователя. В случае неудачи вернуть код ошибки:
     * 1 - пользователь с таким логином уже зарегистрирован
     * 2 - пользователь с таким email уже зарегистрирован
     * 3 - ошибка в имени пользователя
     * 4 - ошибка в e-mail
     * 5 - пароль слабый или пустой
     *
     * @param String $username
     * @param String $password
     * @param String $email
     * @param Boolean $sendEMail
     * @return Integer|Object
     */
    public function Register($username, $password, $email, $sendEMail = true, $checkEMail = true) {
        $username = trim($username);
        $password = trim($password);
        $email = trim($email);

        $retCode = $this->RegistrationValidate($username, $email, $checkEMail);
        if ($retCode > 0) {
            return $retCode;
        }

        $salt = $this->SaltGenerate();

        $ud = array();
        $ud["username"] = $username;
        $ud["joindate"] = TIMENOW;
        $ud["salt"] = $salt;
        $ud["password"] = UserManager::UserPasswordCrypt($password, $salt);
        $ud["email"] = $email;

        // Добавление пользователя в базу
        if ($this->manager->IsAdminRole()) {
            $userid = UserQuery_Admin::UserAppend($this->manager->db, $ud, UserModule::UG_REGISTERED);
        } else {
            if (strlen($password) < 4){// TODO: реализовать проверку на более стойкий пароль
                return 5;
            }
            $userid = UserQuery_Admin::UserAppend($this->manager->db, $ud, UserModule::UG_GUEST, $_SERVER['REMOTE_ADDR'], true);
            UserModule::$instance->AntibotUserDataUpdate($userid);
            $this->manager->UserDomainUpdate($userid);
        }
        $ret = new stdClass();
        $ret->userid = $userid;

        if (!$sendEMail) {
            return $ret;
        }

        $this->ConfirmEmailSend($userid);

        return $ret;
    }

    /**
     * Отправить письмо активации пользователю
     *
     * @param $userid
     */
    private function ConfirmEmailSend($userid) {
        $user = $this->manager->User($userid);
        if (empty($user)) {
            return null;
        }

        $user = new UserItem_Auth($user);
        $actinfo = UserQuery_Register::RegistrationActivateInfo($this->manager->db, $userid);

        $host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
        $link = "http://".$host."/user/activate/".$user->id."/".$actinfo["activateid"]."/";

        $brick = Brick::$builder->LoadBrickS('user', 'templates', null, null);

        $subject = $brick->param->var['reg_mailconf_subj'];
        $body = nl2br(Brick::ReplaceVarByData($brick->param->var['reg_mailconf'], array(
            "actcode" => $actinfo["activateid"],
            "username" => $user->username,
            "link" => $link,
            "sitename" => SystemModule::$instance->GetPhrases()->Get('site_name')
        )));

        Abricos::Notify()->SendMail($user->email, $subject, $body);
        return true;
    }

    public function ConfirmEmailSendAgain($userid) {
        if (!$this->manager->IsAdminRole()) {
            return;
        }
        return $this->ConfirmEmailSend($userid);
    }

    public function ActivateToAJAX($d) {
        $ret = new stdClass();
        $ret->err = $this->Activate($d->userid, $d->code, $d->email, $d->password);
        return $ret;
    }

    /**
     * Активировать нового пользователя.
     * В случае неудачи вернуть код ошибки:
     * 0 - ошбики нет,
     * 1 - пользователь не найден,
     * 2 - пользователь уже активирован
     * 3 - неверный код активации
     *
     * @param integer $userid идентификатор пользователя
     * @param integer $code код активации
     * @return stdClass
     */
    public function Activate($userid, $code = 0, $email = '', $password = '') {
        if (empty($userid)) {
            $row = UserQueryExt::RegistrationActivateInfoByCode($this->db, $code);
            if (empty($row)) {
                sleep(1);
            } else {
                $userid = $row['userid'];
            }
        }

        $user = $this->manager->User($userid);
        if (empty($user)) {
            return 1;
        }
        $user = new UserItem_Auth($user);

        if ($user->emailconfirm) {
            return 2;
        }
        if ($code === 0) {
            if (!$this->manager->IsAdminRole()) {
                return 0;
            }
            $row = UserQuery_Register::RegistrationActivateInfo($this->db, $userid);
            $code = $row['activateid'];
        }

        $ret = UserQuery_Register::RegistrationActivate($this->db, $userid, $code);

        if ($ret === 0 && !empty($email) && !empty($password)) {
            $auth = $this->manager->GetAuthManager();
            $auth->Login($email, $password);
        }

        return $ret;
    }

    public function TermsOfUseToAJAX() {
        $ret = new stdClass();
        $ret->termsOfUse = $this->TermsOfUse();
        return $ret;
    }

    public function TermsOfUse() {
        $brick = Brick::$builder->LoadBrickS('user', 'termsofuse', null, null);
        return $brick->content;
    }

}

?>