<?php
return array(
    "title" => "Пользователи",
    "bosmenu" => array(
        "adminka" => "Админка",
        "users" => "Пользователи"
    ),
    'brick' => array(
        'templates' => array(
            "1" => "Регистрация - активация учетной записи",
            "2" => "Уважаемый(ая) {v#username},

Сообщаем Вам, что вы успешно зарегистрированы в системе. 

Для активизации вашего аккаунта, необходимо пройти последний пункт регистрации!

Чтобы стать зарегистрированным пользователем, вам необходимо ввести код активации <b>{v#actcode}</b> в форму регистрации или однократно проследовать по указанной ниже ссылке:
				
Ссылка активации: <a href=\"{v#link}\">{v#link}</a>
Код активации: <b>{v#actcode}</b>

С наилучшими пожеланиями,
Администрация {v#sitename}",
            "3" => "Информация о вашем аккаунте для доступа на {v#sitename}",
            "4" => "Уважаемый(ая),

Вы запросили повторную установку пароля на {v#email}, т.к. забыли свой пароль. 
Если Вы не делали такого запроса, пожалуйста, не отвечайте на данное сообщение. 
Запрос будет автоматически аннулирован по прошествии 24 часов.

Чтобы повторно установить ваш пароль, пожалуйста, проследуйте на страницу: <a href=\"{v#link}\">{v#link}</a>.
При входе на данную страницу, ваш пароль будет переустановлен, и новый пароль будет отослан вам по электронной почте.

Ваше имя пользователя: {v#username}

С наилучшими пожеланиями,
Администрация {v#sitename}"
        ),
        'userblock' => array(
            "1" => "Логин",
            "2" => "Пароль",
            "3" => "Регистрация нового пользователя",
            "4" => "Регистрация",
            "5" => "Восстановить пароль",
            "6" => "Забыли пароль?",
            "7" => "Запомнить меня",
            "8" => "Войти",
            "9" => "Перейти в панель управления",
            "10" => "Выйти",
            "11" => "Панель управления",
            "12" => "Перейти в панель управления",
            "13" => "Личный раздел",
            "14" => "Выйти",
            "15" => "Открыть менеджер приложений",
            "16" => "Приложения",
            "17" => "Пожалуйста, подождите",
            "18" => "Построение списка приложений..."
        )
    ),
    'content' => array(
        'activate' => array(
            "1" => "Активация пользователя прошла успешно.",
            "2" => "Имя пользователя",
            "3" => "Ошибка активации пользователя",
            "4" => "Пользователь не найден",
            "5" => "Пользователь уже активирован",
            "6" => "Неизвестная ошибка",
            "7" => "Активация пользователя"
        ),
        'index' => array(
            "1" => "Пожалуйста, подождите, идет загрузка..."
        ),
        'index_guest' => array(
            "1" => "Пожалуйста, подождите, идет загрузка...",
            "2" => "Работает на платформе <a href=\"http://abricos.org\" target=\"_blank\">Абрикос</a>"
        ),
        'login' => array(
            "1" => "Ошибка в имени пользователя",
            "2" => "Неверное имя пользователя или пароль",
            "3" => "Не заполнены обязательные поля",
            "4" => "Пользователь заблокирован",
            "5" => "Пользователь не прошел верификацию email",
            "6" => "Ошибка авторизации",
            "7" => "Авторизация на сайте"
        ),
        'logout' => array(
            "1" => "Авторизация на сайте"
        ),
        'recpwd' => array(
            "1" => "Ваш новый пароль для доступа на %1",
            "2" => "Дорогой(ая) %1,
Как Вы и просили, ваш пароль был переустановлен. Новая информация о пароле следующая:
Имя пользователя: %1
Пароль пользователя: %2
Изменить ваш пароль вы можете на личной странице.
С наилучшими пожеланиями,
 Администрация %3",
            "3" => "Ваш пароль был переустановлен и выслан вам на email: {v#email}",
            "4" => "Проверьте вашу почту",
            "5" => "Системе не удалось распознать идентификатор восстановления пароля",
            "6" => "Возможно это связанно с истечением срока отведенного под изменение пароля.",
            "7" => "Если вы все еще хотите запросить инструкцию по восстановлению пароля, <br />
то воcпользуйтесь процедурой <a href=\"#\" onclick=\"Brick.f('user', 'api', 'showPwdRestPanel'); return false;\">восстановления пароля</a> "
        ),
        'register' => array(
            "1" => "Пользователь с таким логином уже зарегистрирован на сайте",
            "2" => "Пользователь с таким email уже зарегистрирован",
            "3" => "Недопустимое имя пользователя",
            "4" => "Неверный формат email",
            "5" => "Пароли не совпадают",
            "6" => "Email не совпадают",
            "7" => "Ошибка регистрации: {v#err}",
            "8" => "Имя",
            "9" => "Пароль",
            "10" => "Введите свой пароль. Пароль чувствителен к регистру букв.",
            "11" => "Пароль",
            "12" => "Подтвердите пароль",
            "13" => "Email адрес",
            "14" => "Введите правильный email адрес.",
            "15" => "Email адрес",
            "16" => "Подтвердите email адрес",
            "17" => "Зарегистрироваться",
            "18" => "Заявка на регистрацию принята успешно.",
            "19" => "На Ваш email: \"{v#email}\" выслано письмо с потверждением регистрации.",
            "20" => "Проверьте вашу почту.",
            "21" => "Регистрация на сайте"
        )
    )
);
?>