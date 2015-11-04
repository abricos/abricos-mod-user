<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2008-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

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

    public function __construct(UserManager $manager){
        $this->manager = $manager;
        $this->db = $manager->db;
    }

    public function AJAX($d){
        switch ($d->do){
            case "passwordRecovery":
                return $this->PasswordRecoveryToAJAX($d->passwordRecovery);
        }
        return null;
    }

    public function PasswordRecoveryToAJAX($d){
        $result = $this->PasswordRecovery($d->email);
        $ret = new stdClass();

        if (is_integer($result)){
            $ret->err = $result;
        } else {
            $ret->passwordRecovery = new stdClass();
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
    public function PasswordRecovery($email){
        if (!UserManager::EmailValidate($email)){
            return 4;
        }

        $user = $this->manager->UserByName($email, true);
        if (empty($user)){
            return 1;
        }

        $sendcount = UserQuery_Password::PasswordSendCount($this->db, $user->id);
        if ($sendcount > 0){
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

    public function PasswordRequestCheck($hash){
        $ret = new stdClass();
        $ret->error = 0;

        $pwdreq = UserQuery_Password::PasswordRequestCheck($this->db, $hash);
        if (empty($pwdreq)){
            $ret->error = 1;
            sleep(1);
            return $ret;
        }
        $userid = $pwdreq['userid'];

        $user = UserQuery::UserById($this->db, $userid);
        $ret->email = $user['email'];

        $newpass = cmsrand(100000, 999999);
        $passcrypt = UserManager::UserPasswordCrypt($newpass, $user['salt']);
        UserQuery_Password::PasswordChange($this->db, $userid, $passcrypt);

        $sitename = SystemModule::$instance->GetPhrases()->Get('site_name');

        $brick = Brick::$builder->LoadBrickS('user', 'templates', null, null);

        $subject = $brick->param->var['pwdres_changemail_subj'];
        $subject = str_replace("%1", $sitename, $subject);

        $message = nl2br($brick->param->var['pwdres_changemail']);
        $message = str_replace("%1", $user['username'], $message);
        $message = str_replace("%2", $newpass, $message);
        $message = str_replace("%3", $sitename, $message);

        Abricos::Notify()->SendMail($user['email'], $subject, $message);

        return $ret;
    }

    public function PasswordChange($userid, $oldPassword, $newPassword){
        if (!$this->manager->IsChangeUserRole($userid)){
            return false;
        };

        $user = UserQuery::UserById($this->db, $userid);

        $currPassCrypt = UserManager::UserPasswordCrypt($oldPassword, $user['salt']);
        if ($user['password'] !== $currPassCrypt){
            return false;
        }

        $passcrypt = UserManager::UserPasswordCrypt($newPassword, $user['salt']);
        UserQuery_Password::PasswordChange($this->db, $userid, $passcrypt);
        return true;
    }

}

?>