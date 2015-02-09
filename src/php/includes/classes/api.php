<?php

class UserAPI extends AbricosAPI {

    public function __construct(UserManager $manager){
        $methodsV1 = new UserAPIv1($manager);

        $this->AddAPIMethods('v1', $methodsV1);
    }

}

class UserAPIv1 extends AbricosAPIMethods {

    /**
     * @var UserManager
     */
    public $manager;


    public function __construct(UserManager $manager){
        $this->manager = $manager;

        $this->AddGetRoute('current', 'Current');
        $this->AddGetRoute('logout', 'Logout');
        $this->AddPostRoute('auth', 'Auth');
        $this->AddPostRoute('registration', 'Registration');
        $this->AddPostRoute('activation', 'Activation');
    }

    /**
     * @apiDefine UserCurrentSuccess
     *
     * @apiSuccess {Integer} id UserID
     * @apiSuccess {String} username Username of the User
     * @apiSuccess {UnixTimeStamp} [joindate] Date of registration if the user is logged in
     * @apiSuccess {UnixTimeStamp} [lastvisit] Date last visit
     * @apiSuccess {String} session User session key
     * @apiSuccess {Array} groups User group id list
     * @apiSuccess {Array} permission User permission list
     *
     * @apiSuccessExample {json} Success Response Example:
     *  HTTP/1.1 200 OK
     *  Authorization: Session humleii21ni8i74m56av2qo2q3
     *  {
     *      "id": 265,
     *      "username": "Stepaska",
     *      "session": "humleii21ni8i74m56av2qo2q3",
     *      "joindate": 1408098034,
     *      "lastvisit": 1423213899,
     *      "groups": ["2"],
     *      "permission": {
     *          "comment": {"10": 1, "20": 1},
     *          "sitemap": {"10": 1, "30": 1},
     *          "uprofile": {"10": 1, "30": 1},
     *          "todolist": {"10": 1, "30": 1},
     *          "blog": {"10": 1, "20": 1}
     *      }
     *  }
     */

    /**
     * @api {get} /api/user/v1/current Current User
     * @apiDescription Request Current User information
     * @apiName Current
     * @apiGroup User
     * @apiVersion 0.1.0
     *
     * @apiHeader {String} Authorization User session key
     * @apiHeaderExample {json} Header-Example:
     *  GET /api/user/v1/current HTTP/1.1
     *  Authorization: Session humleii21ni8i74m56av2qo2q3
     *
     * @apiUse UserCurrentSuccess
     *
     */
    public function Current(){
        $user = new UserItem_Session(Abricos::$user);
        return $user->ToJSON();
    }

    /**
     * @api {post} /api/user/v1/auth User Authorization
     * @apiName Auth
     * @apiGroup User
     * @apiVersion 0.1.0
     *
     * @apiParam {String} username User Name
     * @apiParam {String} password User Password
     *
     * @apiParamExample {json} Request Example:
     *  {
     *      "username": "Stepashka",
     *      "password" : "mysuperpass"
     *  }
     *
     * @apiUse UserCurrentSuccess
     *
     * @apiError (Error 401 Unauthorized) {String} err Error Code:
     * <table>
     * <tr><td>BAD_USERNAME</td><td>Error in the username</td></tr>
     * <tr><td>INVALID_USERNAME_PASSWORD</td><td>Invalid user name or password</td></tr>
     * <tr><td>EMPTY_PARAMS</td><td>Do not fill in the required fields</td></tr>
     * <tr><td>USER_BLOCKED</td><td>User is blocked</td></tr>
     * <tr><td>NOT_ACTIVATE</td><td>User has not passed the activation procedure</td></tr>
     * <tr><td>UNKNOW</td><td>Unknown authorization error</td></tr>
     * </table>
     *
     * @apiError (Error 401 Unauthorized) {String} msg Error Description
     *
     * @apiErrorExample {json} Error Response Exmaple:
     *  HTTP/1.1 401 Unauthorized
     *  {
     *      "err": "NOT_ACTIVATE",
     *      "msg": "User has not passed the activation procedure"
     *  }
     */
    public function Auth(){
        $authMan = $this->manager->GetAuthManager();

        $username = $this->GetPostParam('username');
        $password = $this->GetPostParam('password');
        $autologin = $this->GetPostParam('autologin');


        $error = $authMan->Login($username, $password, $autologin);
        if ($error > 0){
            $response = new AbricosAPIResponse();
            $response->headers['status'] = 'HTTP/1.1 401 Unauthorized';
            switch ($error){
                case 1:
                    $response->errorCode = 'BAD_USERNAME';
                    $response->message = 'Error in the username';
                    break;
                case 2:
                    $response->errorCode = 'INVALID_USERNAME_PASSWORD';
                    $response->message = 'Invalid user name or password';
                    break;
                case 3:
                    $response->errorCode = 'EMPTY_PARAMS';
                    $response->message = 'Do not fill in the required fields';
                    break;
                case 4:
                    $response->errorCode = 'USER_BLOCKED';
                    $response->message = 'User is blocked';
                    break;
                case 5:
                    $response->errorCode = 'NOT_ACTIVATE';
                    $response->message = 'User has not passed the activation procedure';
                    break;
                default:
                    $response->errorCode = 'UNKNOW';
                    $response->message = 'Unknown authorization error';
                    break;
            }
            return $response;
        }

        return $this->Current();
    }

    /**
     * @api {get} /api/user/v1/logout User Logout
     * @apiName Logout
     * @apiGroup User
     * @apiVersion 0.1.0
     *
     * @apiUse UserCurrentSuccess
     */
    public function Logout(){
        $authMan = $this->manager->GetAuthManager();
        $authMan->Logout();

        return $this->Current();
    }

    /**
     * @api {post} /api/user/v1/registration User Registration
     * @apiName Registration
     * @apiGroup User
     * @apiVersion 0.1.0
     *
     * @apiParam {String} username User Name
     * @apiParam {String} password User Password
     * @apiParam {String} email User Email
     *
     * @apiParamExample {json} Request Example:
     *  {
     *      "username": "Stepashka",
     *      "password": "mysuperpass",
     *      "email": "stepashka@example.com"
     *  }
     *
     * @apiSuccess {Integer} userid UserID
     * @apiSuccess {Object} [emailInfo] User activation sent email, if server is debug mode
     * @apiSuccess {String} emailInfo.messageId Sent Email ID
     * @apiSuccess {String} emailInfo.error Sent Email Error info
     *
     * @apiSuccessExample {json} Success Response Example:
     *  HTTP/1.1 200 OK
     *  {
     *      "userid": 265,
     *      "emailInfo": {
     *          "messageId": "98ca52f5b3737a349ea597d7e53e2529",
     *          "error": "SMTP connect() failed."
     *      }
     *  }
     *
     * @apiError (Error 422 Unprocesable entity) {String} err Error Code:
     * <table>
     * <tr><td> ALREADY_REGISTERED </td><td> Username already registered </td></tr>
     * <tr><td> ALREADY_REGISTERED_EMAIL </td><td> User with the email is already registered </td></tr>
     * <tr><td> BAD_USERNAME </td><td> Error in the username </td></tr>
     * <tr><td> BAD_EMAIL </td><td> Error in email </td></tr>
     * <tr><td> BAD_PASSWORD </td><td> Weak or blank password </td></tr>
     * <tr><td>UNKNOW</td><td>Unknown authorization error</td></tr>
     * </table>
     *
     * @apiError (Error 422 Unprocesable entity) {String} msg Error Description
     *
     * @apiErrorExample {json} Error Response Exmaple:
     *  HTTP/1.1 422 Unprocesable entity
     *  {
     *      "err": "ALREADY_REGISTERED",
     *      "msg": "Username already registered"
     *  }
     */
    public function Registration(){
        $regMan = $this->manager->GetRegistrationManager();

        $username = $this->GetPostParam('username');
        $password = $this->GetPostParam('password');
        $email = $this->GetPostParam('email');

        $result = $regMan->Register($username, $password, $email);

        if (is_object($result)){
            return $result;
        }

        $error = $result;

        $response = new AbricosAPIResponseError(array(
            1 => array("ALREADY_REGISTERED", "Username already registered"),
            2 => array("ALREADY_REGISTERED_EMAIL", "User with the email is already registered"),
            3 => array("BAD_USERNAME", "Error in the username"),
            4 => array("BAD_EMAIL", "Error in email"),
            5 => array("BAD_PASSWORD", "Weak or blank password"),
            "unknow" => array("UNKNOW", "Unknown registration error"),
        ));

        if (is_integer($error) && $error > 0){
            $response->SetError($error);
        }
        return $response;
    }

    /**
     * @api {post} /api/user/v1/activation User Activation
     * @apiName Activation
     * @apiGroup User
     * @apiVersion 0.1.0
     *
     * @apiParam {String} userid UserID
     * @apiParam {String} code Activation Code
     * @apiParam {String} [login] Username or Email for authorization
     * @apiParam {String} [password] Password for authorization
     *
     * @apiParamExample {json} Request Example:
     *  {
     *      "userid": 256,
     *      "code": 5784651
     *  }
     *
     * @apiSuccess {Integer} userid UserID
     *
     * @apiSuccessExample {json} Success Response Example:
     *  HTTP/1.1 200 OK
     *  {
     *      "userid": 265
     *  }
     *
     * @apiError (Error 422 Unprocesable entity) {String} err Error Code:
     * <table>
     * <tr><td> USER_NOT_FOUND </td><td> User not found </td></tr>
     * <tr><td> ALREADY_ACTIVATED </td><td> User is already activated </td></tr>
     * <tr><td> BAD_CODE </td><td> Bad activation code </td></tr>
     * <tr><td> UNKNOW </td><td> Unknown activation error </td></tr>
     * </table>
     *
     * @apiError (Error 422 Unprocesable entity) {String} msg Error Description
     *
     * @apiErrorExample {json} Error Response Exmaple:
     *  HTTP/1.1 422 Unprocesable entity
     *  {
     *      "err": "BAD_CODE",
     *      "msg": "Bad activation code"
     *  }
     */
    public function Activation(){
        $regMan = $this->manager->GetRegistrationManager();

        $userid = $this->GetPostParam('userid');
        $code = $this->GetPostParam('code');
        $login = $this->GetPostParam('login');
        $password = $this->GetPostParam('password');

        $result = $regMan->Activate($userid, $code, $login, $password);

        if (is_object($result)){
            return $result;
        }

        $error = $result;

        $response = new AbricosAPIResponseError(array(
            1 => array("USER_NOT_FOUND", "User not found"),
            2 => array("ALREADY_ACTIVATED", "User is already activated"),
            3 => array("BAD_CODE", "Bad activation code"),
            "unknow" => array("UNKNOW", "Unknown activation error"),
        ));

        if (is_integer($error) && $error > 0){
            $response->SetError($error);
        }
        return $response;
    }
}

?>