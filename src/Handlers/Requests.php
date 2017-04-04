<?php

namespace Handler;

use Silex\Application;
use Symfony\Component\HttpFoundation\{JsonResponse,RedirectResponse,Response};
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Main\User;
use Main\App;
use Symfony\Component\HttpFoundation\Request;
use Database\DBDataMapper;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Ramsey\Uuid\Uuid;

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

        //fixme needs new route {} as param just accepts all, name irrelevant (not in query string)
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

    public function verifyToken(App $app, $token){
        $result = $this->db->verifyToken($token);
        if (false !== $result){
            //Success - future log them in? result has their userID  --- $user = $app->user(); ?

            return new RedirectResponse($app->url('login'));
        }

        //Failure  future improve this?
        return new Response('Error - Token not found');
    }

    public function registerNewUser(Request $request, App $app){
        $username = $request->get('username');
        $email = $request->get('email');
        $password = $request->get('password');

        //todo min password length

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(array('error' => 'email invalid'));
        }

        if (!$user = $this->db->getUserByUsername($username)) {
            $encoded = $app['security.default_encoder']->encodePassword($password,null);

        } else {
            return new RedirectResponse($app->url('user')); //future different failures or messages or raise exceptions
//            throw new \RuntimeException(sprintf('Can\'t create user %s', $username)); //note just database error or?
        }

        if ($this->db->addNewUser($username,$encoded,null,$email)) {
            $user = $app['user.provider']->loadUserByUsername($username);
            $token = new UsernamePasswordToken($username, null, 'main', $user->getRoles());
            $app['security.token_storage']->setToken($token);  //note doesnt work?
            $app['session']->set('_security_main', serialize($token));

            $this->sendVerifyToken($app, $user->getID());

            return new RedirectResponse($app->path('user'));
        }

        throw new RuntimeException(sprintf('Cant create user %s', $username)); //note just database error or?
        
    }

    public function sendVerifyToken(App $app, $userid){
        $bytes = bin2hex(random_bytes(32));
        $this->db->addToken($userid,$bytes);
        //todo only for USER_BASIC
        if($email = $this->db->getEmailByID($userid)){
            //future link from env setting or similar?
            //fixme disabled as requires your own email domain

            $message = \Swift_Message::newInstance()
                ->setSubject('Verify your Food Inc. account')
                ->setFrom(array('noreply@foodinc.com'))
                ->setTo(array($email))
                ->setBody('Your verification link: https://gpmain.herokuapp.com/register/validatemail/' . $bytes); //Future tidy up (twig template or whatever)

            $app['mailer']->send($message);

            return new Response('Token Sent!', 201);
        }

        throw new RuntimeException(sprintf('Cant find email for user %s', $userid)); //note just database error or?
    }

    public function foodItemPost(Request $request, Application $app)
    {
        //fixme yeah dont think this works. Check it, fix it
        $toEncode = array("error" => "failed to add");

        die("Disabled");

        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $userID = $token->getUser()->getID();
            $name = $request->get('name');
            $expirDate = $request->get('expiredate');
            $category = $request->get('category');
            $desc = $request->get('description');
            $lat = $request->get('latitude');
            $long = $request->get('longitude');
            $amount = $request->get('amount');
            $weight = $request->get('weight');
            $imageuri = $request->get('image');
//            $imagedir = "none";//note ???

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
            if ($imageuri === "") {
                $filename = null;
            } else {
                $uriPhp = 'data://' . substr($imageuri, 5);
                $binary = file_get_contents($uriPhp);
                $filename = Uuid::uuid4()->getHex() . '.png';
                file_put_contents('images/food/' . $filename, $binary);
            }

            if ($this->db->addNewFoodItem($name, $expirDate, $category, $userID, $desc, $lat, $long, $amount, $weight, $filename)) {
                $toEncode = array("success" => "topic added");
            }
        }

        //echo json_encode($toEncode);

        return new RedirectResponse($app->path('user')); //note change redirect on failure/success

    }

    public function getRequestsSentByUserID(Request $request, App $app) {
        $toEncode = null;
        $token = $app['security.token_storage']->getToken();

        if (null !== $token) {
            $userID = $token->getUser()->getID();
            $toEncode = $this->db->getRequestsSentByUserID($userID);
            if ($toEncode === null) {
                $toEncode = array('error' => 'failed');
            }
        }

        return new JsonResponse($toEncode);
    }

    public function getRequestsReceivedByUserID(Request $request, App $app) {
        $toEncode = null;
        $token = $app['security.token_storage']->getToken();

        if (null !== $token) {
            $userID = $token->getUser()->getID();
            $toEncode = $this->db->getRequestsReceivedByUserID($userID);
            if ($toEncode === null) {
                $toEncode = array('error' => 'failed');
            }
        }

        return new JsonResponse($toEncode);
    }

    public function getUserMessagesByRequestID(Request $request, App $app, $requestID) {
        $toEncode = null;
        $token = $app['security.token_storage']->getToken();

        if (null !== $token) {
            $userID = $token->getUser()->getID();
            $toEncode = $this->db->getUserMessagesByRequestID($userID, $requestID);
            if ($toEncode === null) {
                $toEncode = array('error' => 'failed');
              }
        }

        return new JsonResponse($toEncode);
    }

    public function getFoodBetween(Request $request, App $app, $start, $num){
        $toEncode = $this->db->getFoodBetween($start, $num);
        if ($toEncode === null) {
            $toEncode = array('error' => 'failed');
        }

        return new JsonResponse($toEncode);
    }

    public function mainSearch(Request $request, App $app, $category, $search)
    {
        $toEncode = $this->db->mainSearch($category, $search);
        if ($toEncode === null) {
            $toEncode = array('error' => 'failed');
        }

        return new JsonResponse($toEncode);
    }

    public function searchExtra(Request $request, App $app, $category, $search, $latit, $longit, $radius, $minAmount, $maxAmount, $minWeight, $maxWeight, $sort)
    {
      $toEncode = $this->db->searchExtra($category, $search, $latit, $longit, $radius, $minAmount, $maxAmount, $minWeight, $maxWeight, $sort);
      if ($toEncode === null) {
          $toEncode = array('error' => 'failed');
      }

      return new JsonResponse($toEncode);
    }

}
