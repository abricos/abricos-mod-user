<?php

require_once 'password_dbquery.php';

/**
 * Class UserManager_Password
 */
class UserManager_Password {

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
            case "passwordRecovery":
                return $this->PasswordRecoveryToAJAX($d->savedata);
        }
        return null;
    }

    public function PasswordRecoveryToAJAX($d) {
        $result = $this->PasswordRecovery($d->email);
        $ret = new stdClass();

        if (is_integer($result)) {
            $ret->err = $result;
        } else {
            $ret->passwordRecovery->msg = 'ok';
        }

        return $ret;
    }

    /**
     * Запросить систему восстановить пароль и вернуть номер ошибки:
     * 0 - нет ошибки,
     * 1 - пользователь не найден,
     * 2 - письмо подверждения восстановить пароль уже отправлено,
     * 4 - пустой или ошибочный e-mail
     *
     * @param string $email E-mail пользователя
     * @return Integer
     */
    public function PasswordRecovery($email) {
        if (!UserManager::EmailValidate($email)) {
            return 4;
        }

        $user = $this->manager->UserByName($email, true);
        if (empty($user)) {
            return 1;
        }

        $sendcount = UserQuery_Password::PasswordSendCount($this->db, $user->id);
        if ($sendcount > 0) {
            return 2;
        } // письмо уже отправлено

        $hash = md5(microtime());
        UserQuery_Password::PasswordRequestCreate($this->db, $user->id, $hash);

        $host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_ENV['HTTP_HOST'];
        $link = "http://".$host."/user/passrec/".$hash;

        $sitename = SystemModule::$instance->GetPhrases()->Get('site_name');

        $brick = Brick::$builder->LoadBrickS('user', 'templates', null, null);
        $v = &$brick->param->var;

        $subject = Brick::ReplaceVarByData($v['pwd_mail_subj'], array("sitename" => $sitename));
        $body = nl2br(Brick::ReplaceVarByData($v['pwd_mail'], array(
            "email" => $email,
            "link" => $link,
            "username" => $user->username,
            "sitename" => $sitename
        )));

        Abricos::Notify()->SendMail($email, $subject, $body);

        return 0;
    }

}

?>