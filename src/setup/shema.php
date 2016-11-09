<?php
/**
 * @package Abricos
 * @subpackage User
 * @copyright 2011-2015 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License (MIT)
 * @author Alexander Kuzmin <roosit@abricos.org>
 */


$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = Ab_UpdateManager::$current;
$db = Abricos::$db;
$pfx = $db->prefix;

if ($updateManager->isInstall()){
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."session (
		  sessionhash CHAR(32) NOT NULL DEFAULT '',
		  userid INT(10) UNSIGNED NOT NULL DEFAULT 0,
		  host CHAR(15) NOT NULL DEFAULT '',
		  idhash CHAR(32) NOT NULL DEFAULT '',
		  lastactivity INT(10) UNSIGNED NOT NULL DEFAULT 0,
		  location CHAR(255) NOT NULL DEFAULT '',
		  useragent CHAR(100) NOT NULL DEFAULT '',
		  loggedin smallint(5) UNSIGNED NOT NULL DEFAULT 0,
		  badlocation smallint(5) UNSIGNED NOT NULL DEFAULT 0,
		  bypass tinyint(4) NOT NULL DEFAULT '0',
		  PRIMARY KEY (sessionhash)
		)".$charset
    );
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."user (
		  userid INT(10) UNSIGNED NOT NULL auto_increment,
		  language CHAR(2) NOT NULL DEFAULT 'en', 
		  usergroupid INT(4) UNSIGNED NOT NULL DEFAULT 0,
		  
		  username VARCHAR(150) NOT NULL DEFAULT '',
		  firstname VARCHAR(100) NOT NULL DEFAULT '',
		  patronymic VARCHAR(100) NOT NULL DEFAULT '',
		  lastname VARCHAR(100) NOT NULL DEFAULT '',
		  avatar VARCHAR(8) NOT NULL DEFAULT '',
		  
		  email VARCHAR(100) NOT NULL DEFAULT '',
		  
		  salt CHAR(3) NOT NULL DEFAULT '',
		  password VARCHAR(32) NOT NULL DEFAULT '',
		  
		  joindate INT(10) UNSIGNED NOT NULL DEFAULT 0,
		  lastvisit INT(10) UNSIGNED NOT NULL DEFAULT 0,
		  
		  agreement TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
		  ipadress VARCHAR(15) NOT NULL DEFAULT '',
		  isvirtual TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-виртуальный пользователь',
		  
		  upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления',
		  deldate INT(10) NOT NULL DEFAULT '0',
		  PRIMARY KEY (userid),
		  UNIQUE KEY username (username)
		)".$charset
    );

    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."useractivate (
		  useractivateid INT(10) UNSIGNED NOT NULL auto_increment,
		  userid INT(10) UNSIGNED NOT NULL,
		  activateid INT(10) UNSIGNED NOT NULL,
		  joindate INT(10) UNSIGNED NOT NULL,
		  PRIMARY KEY  (useractivateid)
		)".$charset
    );
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."userpwdreq (
		  pwdreqid INT(10) UNSIGNED NOT NULL auto_increment,
		  userid INT(10) UNSIGNED NOT NULL,
		  hash VARCHAR(32) NOT NULL,
		  dateline INT(10) UNSIGNED NOT NULL,
		  counteml INT(2) NOT NULL DEFAULT '0',
		  PRIMARY KEY  (pwdreqid)
		)".$charset
    );

    $salt = '';
    $password = 'admin';
    for ($i = 0; $i < 3; $i++){
        $salt .= chr(rand(32, 126));
    }

    $passwordCrypt = md5(md5($password).$salt);

    // добавление в таблицу администратора
    $db->query_write("
		INSERT INTO ".$pfx."user (language, usergroupid, username, password, email, joindate, salt) VALUES
		('".Abricos::$LNG."', 6, 'admin', '".$passwordCrypt."', '', ".TIMENOW.", '".$salt."');
	");
}

// обновление для платформы Abricos версии 0.5
if ($updateManager->isInstall() || $updateManager->serverVersion === '1.0.1'){
    $updateManager->serverVersion = '0.2';

    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."userconfig (
		  userconfigid INT(10) UNSIGNED NOT NULL auto_increment,
		  userid INT(10) UNSIGNED NOT NULL,
		  module VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Имя модуля',
		  optname VARCHAR(25) NOT NULL DEFAULT '' COMMENT 'Имя параметра',
		  optvalue TEXT NOT NULL COMMENT 'Значение параметра',
		  PRIMARY KEY  (userconfigid),
		  UNIQUE KEY configvar (userid,module,optname),
		  KEY module (module),
		  KEY userid (userid)
	  )".$charset
    );
}
$createGroupTable = false;
if ($updateManager->isUpdate('0.2.1')){

    $db->query_write("DROP TABLE IF EXISTS ".$pfx."usergroup");
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."group (
		  groupid INT(5) UNSIGNED NOT NULL auto_increment,
		  groupname VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'Наименование группы',
		  groupkey VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'Идентификатор группы в ядре',
		  PRIMARY KEY  (groupid)
		)".$charset
    );

    // заполнение таблицы групп пользователей
    $db->query_write("
		INSERT INTO ".$pfx."group (groupid, groupname, groupkey) VALUES
		(1, 'Guest', 			'guest'),
		(2, 'Registered', 		'register'),
		(3, 'Administrator', 	'admin')
	");
    $createGroupTable = true;

    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."usergroup (
		  usergroupid INT(5) UNSIGNED NOT NULL auto_increment,
		  userid INT(10) UNSIGNED NOT NULL,
		  groupid INT(5) UNSIGNED NOT NULL,
		  PRIMARY KEY  (usergroupid),
		  UNIQUE KEY usergroup (userid,groupid)
		)".$charset
    );

    $db->query_write("
		INSERT IGNORE INTO ".$pfx."usergroup (userid, groupid)  
		SELECT 
			userid, 
			CASE usergroupid WHEN 6 THEN 3 ELSE 2 END
		FROM ".$pfx."user
	");
    $db->query_write("ALTER TABLE ".$pfx."user DROP usergroupid");

    $db->query_write("ALTER TABLE ".$pfx."user ADD emailconfirm TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER email");
    $db->query_write("
        UPDATE ".$pfx."user
        SET emailconfirm=1
        WHERE lastvisit > 0 OR userid=1
	");

    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."userrole (
		  roleid INT(10) UNSIGNED NOT NULL auto_increment,
		  modactionid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор действия',
		  usertype tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 - группа, 1 - пользователь',
		  userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Идентификатор пользователя/группы в зависимости от usertype',
		  status tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1 - разрешено, 0 - запрещено',
		  PRIMARY KEY  (roleid),
		  KEY userid (userid),
		  UNIQUE KEY userrole (modactionid,userid,usertype)
		)".$charset
    );
    Abricos::GetModule('user')->permission->Install();
}

if ($updateManager->isUpdate('0.2.1') && !$updateManager->isInstall()){
    $db->query_write("
        ALTER TABLE ".$pfx."user 
            DROP INDEX username, 
            ADD UNIQUE username (username)
    ");
}

if ($updateManager->isUpdate('0.2.2') && !$updateManager->isInstall()){
    // удалить все второстепенные поля, для работы новой технологии
    // хранения этих полей, такие как Фамилия, Имя и т.п.
    // по умолчанию таблица пользователей будет содержать только основные
    // рабочие поля

    $rows = $db->query_read("SHOW COLUMNS FROM ".$pfx."user");
    $cols = array();
    while (($row = $db->fetch_array($rows))){
        $cols[$row['Field']] = $row;
    }
    if (!empty($cols['realname']))
        $db->query_write("ALTER TABLE ".$pfx."user DROP realname");
    if (!empty($cols['sex']))
        $db->query_write("ALTER TABLE ".$pfx."user DROP sex");
    if (!empty($cols['homepagename']))
        $db->query_write("ALTER TABLE ".$pfx."user DROP homepagename");
    if (!empty($cols['homepage']))
        $db->query_write("ALTER TABLE ".$pfx."user DROP homepage");
    if (!empty($cols['icq']))
        $db->query_write("ALTER TABLE ".$pfx."user DROP icq");
    if (!empty($cols['aim']))
        $db->query_write("ALTER TABLE ".$pfx."user DROP aim");
    if (!empty($cols['yahoo']))
        $db->query_write("ALTER TABLE ".$pfx."user DROP yahoo");
    if (!empty($cols['msn']))
        $db->query_write("ALTER TABLE ".$pfx."user DROP msn");
    if (!empty($cols['skype']))
        $db->query_write("ALTER TABLE ".$pfx."user DROP skype");
    if (!empty($cols['birthday']))
        $db->query_write("ALTER TABLE ".$pfx."user DROP birthday");
}

if ($updateManager->isUpdate('0.2.3') && !$updateManager->isInstall()){
    if (!$createGroupTable){
        $db->query_write("
			ALTER TABLE ".$pfx."group 
			ADD groupkey VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'Глобальный идентификатор группы в ядре'
		");
    }

    $db->query_write("UPDATE ".$pfx."group SET groupkey='guest' WHERE groupid=1");
    $db->query_write("UPDATE ".$pfx."group SET groupkey='register' WHERE groupid=2");
    $db->query_write("UPDATE ".$pfx."group SET groupkey='admin' WHERE groupid=3");
}

if ($updateManager->isUpdate('0.2.5') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."user ADD language CHAR(2) NOT NULL DEFAULT 'ru' AFTER userid
	");
}

if ($updateManager->isUpdate('0.2.5.1') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."userconfig ADD UNIQUE configvar (userid, module, optname)
	");
}

if ($updateManager->isUpdate('0.2.5.2') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."user ADD agreement TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
	");
}

if ($updateManager->isUpdate('0.2.5.3')){
    // логи входа дубликатов
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."userdoublelog (
			doublelogid INT(10) UNSIGNED NOT NULL auto_increment,
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			doubleuserid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			ipadress VARCHAR(15) NOT NULL DEFAULT '',
			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			PRIMARY KEY  (doublelogid)
		)".$charset
    );

    // дубликаты
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."userdouble (
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			doubleuserid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			dateline INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
		  	UNIQUE KEY userdouble (userid,doubleuserid)
		)".$charset
    );
}

if ($updateManager->isUpdate('0.2.5.4') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."user ADD upddate INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Дата обновления'
	");
}

if ($updateManager->isUpdate('0.2.5.5')){
    // Принадлежность пользователя к домену (мультидоменная система)
    $db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."userdomain (
			userid INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '',
			domain VARCHAR(15) NOT NULL DEFAULT '',
		  	UNIQUE KEY userdomain (userid,domain)
		)".$charset
    );
}

if ($updateManager->isUpdate('0.2.5.6') && !$updateManager->isInstall()){
    $db->query_write("
		ALTER TABLE ".$pfx."user 
		ADD isvirtual TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '1-виртуальный пользователь'
	");
}
