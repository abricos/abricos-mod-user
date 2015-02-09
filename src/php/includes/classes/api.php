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
     * @apiError (Error 403 Forbidden) {String} err Error Code:
     * <table>
     * <tr><td>BAD_USERNAME</td><td>Error in the username</td></tr>
     * <tr><td>INVALID_USERNAME_PASSWORD</td><td>Invalid user name or password</td></tr>
     * <tr><td>EMPTY_PARAMS</td><td>Do not fill in the required fields</td></tr>
     * <tr><td>USER_BLOCKED</td><td>User is blocked</td></tr>
     * <tr><td>NOT_ACTIVATE</td><td>User has not passed the activation procedure</td></tr>
     * <tr><td>UNKNOW</td><td>Unknown authorization error</td></tr>
     * </table>
     *
     * @apiError (Error 403 Forbidden) {String} msg Error Description
     *
     * @apiErrorExample {json} Error Response Exmaple:
     *  HTTP/1.1 403 Forbidden
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
            $response->headers['status'] = 'HTTP/1.1 403 Forbidden';
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

    public function Logout(){
        $authMan = $this->manager->GetAuthManager();
        $authMan->Logout();

        return $this->Current();
    }

}

?>