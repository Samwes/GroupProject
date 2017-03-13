<?php

namespace Handler;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Main\User;
use Main\App;
use Symfony\Component\HttpFoundation\Request;
use Database\DBDataMapper;

class Requests
{

    /**
     * @var DBDataMapper
     */
    protected $db;

    public function __construct(DBDataMapper $db)
    {
        $this->db = $db;
    }

    //note: Request handling functions go here

    public function foodItemsGet(Request $request, App $app, $userID) {
        $toEncode = $this->db->getFoodItemsByUserID($userID);
        if ($toEncode === null){
            $toEncode = array('error' => 'failed');}

        return new JsonResponse($toEncode);
    }

    public function foodItemGet(Request $request, App $app, $foodID)
    {
        $toEncode = $this->db->getFoodItemByID($foodID);
        if ($toEncode === null){
            $toEncode = array('error' => 'failed');}

        return new JsonResponse($toEncode);
    }

    public function registerNewUser(Request $request, App $app){
        $username = $request->get('username');
        $email = $request->get('email');
        $password = $request->get('password');

        //todo JS error handling? password length, valid email etc.
        // More JS handling before send off or properly implementing form with symfony form stuff

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(array('error' => 'email invalid'));
        }

        if (!$user = $this->db->getUserByUsername($username)) {
            //todo test this maybe broke it should be k
            $encoded = $app['security.default_encoder']->encodePassword($password,null);

            //todo: now log them in
            //todo: emailing and account validation
        } else {
            return new RedirectResponse($app->path('login')); //future different failures or messages or raise exceptions
        }

        if ($this->db->addNewUser($username,$encoded,null,$email)) {

            $user = $app['user.provider']->loadUserByUsername($username);
            $token = new UsernamePasswordToken($user, $encoded, 'main', $user->getRoles());
            $app['security.token_storage']->setToken($token);
            $app['session']->set('_security_main', serialize($token));

            return new RedirectResponse($app->path('user'));
        } else {
            throw new \RuntimeException(sprintf('Cant create user %s', $username)); //future just database error or?
        }
    }

    public function foodItemPst(Request $request, Application $app)
    {
        //fixme yeah dont think this works. Check it, fix it
        $toEncode = array("error" => "failed to add");
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $userID = $token->getUser()->getID();
            $name = $request->get('name');
            $expirDate = $request->get('expiredate');
            $category = $request->get('category');
            $desc = $request->get('description');
            $lat = $request->get('laitutde');
            $long = $request->get('longitude');
            $amount = $request->get('amount');
            $weight = $request->get('weight');
            $imagedir = null;

            //Check Vars
            if (!is_numeric($userID)) {
                die(json_encode(array("error" => "userID incorrectly defined")));
            } elseif (!is_string($name)) {
                die(json_encode(array("error" => "name incorrectly defined")));
            } elseif (!is_string($expirDate)) {
                die(json_encode(array("error" => "expirey incorrectly defined")));
            } elseif (!is_string($category)) {
                die(json_encode(array("error" => "category incorrectly defined")));
            } elseif (!is_string($desc)) {
                die(json_encode(array("error" => "description incorrectly defined")));
            } elseif (!is_numeric($lat)) {
                die(json_encode(array("error" => "latitude incorrectly defined")));
            } elseif (!is_numeric($long)) {
                die(json_encode(array("error" => "longitude incorrectly defined")));
            } elseif (!is_numeric($amount)) {
                die(json_encode(array("error" => "amount incorrectly defined")));
            } elseif (!is_numeric($weight)) {
                die(json_encode(array("error" => "weight incorrectly defined")));
            }

            if($request->files->has('image')) {
                $target_dir = 'images/food/';
                $GUID = GUIDv4();
                $imagedir = $target_dir . $GUID;
                $uploadOk = 1;

                $file = $request->files->get('image');
                $imageFileType = pathinfo(basename($file['name']),PATHINFO_EXTENSION);

                $check = getimagesize($file['tmp_name']);
                if ($check !== false) {
                    $app['monolog']->debug('File is an image - '. $check["mime"]);
                    $uploadOk = 1;
                } else {
                    if ($app['debug']) echo "File is not an image.";
                    $uploadOk = 0;
                }
                if (file_exists($imagedir)) {
                    if ($app['debug']) echo "Sorry, file already exists.";
                    $uploadOk = 0;
                }
                if ($file["size"] > 500000) {
                    if ($app['debug']) echo "Sorry, your file is too large.";
                    $uploadOk = 0;
                }
                if ($imageFileType != 'jpg' && $imageFileType != "png" && $imageFileType != "jpeg"
                    && $imageFileType != "gif"
                ) {
                    if ($app['debug']) echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                    $uploadOk = 0;
                }
                if ($uploadOk == 0) {
                    if ($app['debug']) echo "Sorry, your file was not uploaded.";
                    $imgdir = null;
                } else {
                    if ($file->move($file["tmp_name"], $imagedir)) {
                        if ($app['debug']) echo "The file " . basename($file["name"]) . " has been uploaded.";
                    } else {
                        if ($app['debug']) echo "Sorry, there was an error uploading your file.";
                    }
                }
            }


            if ($this->db->addNewFoodItem($name, $expirDate, $category, $userID, $desc, $lat, $long, $amount, $weight, $imagedir)) {
                $toEncode = array("success" => "topic added");
            }
        }

        echo json_encode($toEncode);

    }

}