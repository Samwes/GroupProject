<?php

namespace Handler;

use Database\DBDataMapper;
use Main\App;
use Ramsey\Uuid\Uuid;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class Requests
{

	/**
	 * @var DBDataMapper
	 */
	protected $db;

	public function __construct(DBDataMapper $db) {
		$this->db = $db;
	}

	//note: Request handling functions go here

	public function foodItemsGet(Request $request, App $app) {

		$token = $app['security.token_storage']->getToken(); //future refactor this into its own func?

		if (null !== $token) {
			$user = $token->getUser();
			$userID = $user->getID();

			$toEncode = $this->db->getFoodItemsByUserID($userID);
			if ($toEncode === null) {
				$toEncode = array('error' => 'failed');
			}
		}

		return new JsonResponse($toEncode);
	}

	public function foodItemGet(Request $request, App $app, $foodID) {
		$toEncode = $this->db->getFoodItemByID($foodID);
		if ($toEncode === null) {
			$toEncode = array('error' => 'failed');
		}

		return new JsonResponse($toEncode);
	}

	public function verifyToken(App $app, $token) {
		$result = $this->db->verifyToken($token);
		if (false !== $result) {
			//Success - future log them in? result has their userID  --- $user = $app->user(); ?

			return new RedirectResponse($app->url('login'));
		}

		//Failure  future improve this?
		return new Response('Error - Token not found');
	}

	public function registerNewUser(Request $request, App $app) {
		$username = $request->get('username');
		$email = $request->get('email');
		$password = $request->get('password');

		//todo min password length

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return new JsonResponse(array('error' => 'email invalid'));
		}

		if (!$user = $this->db->getUserByUsername($username)) {
			$encoded = $app['security.default_encoder']->encodePassword($password, null);
		} else {
			return new RedirectResponse($app->url('user')); //future different failures or messages or raise exceptions
			//            throw new \RuntimeException(sprintf('Can\'t create user %s', $username)); //note just database error or?
		}

		if ($this->db->addNewUser($username, $encoded, null, $email)) {
			$user = $app['user.provider']->loadUserByUsername($username);
			$token = new UsernamePasswordToken($username, null, 'main', $user->getRoles());
			$app['security.token_storage']->setToken($token);  //note doesnt work?
			$app['session']->set('_security_main', serialize($token));

			$this->sendVerifyToken($app, $user->getID());

			return new RedirectResponse($app->path('user'));
		}

		throw new RuntimeException(sprintf('Cant create user %s', $username)); //note just database error or?
	}

	public function sendVerifyToken(App $app, $userid) {
		//note maybe broke with this if
		if ($this->db->getRoles($userid) === 'ROLE_BASIC') {
			$bytes = bin2hex(random_bytes(32));
			$this->db->addToken($userid, $bytes);
			if ($email = $this->db->getEmailByID($userid)) {
				//future link from env setting or similar?

				$message = \Swift_Message::newInstance()
										 ->setSubject('Verify your Food Inc. account')
										 ->setFrom(array('noreply@foodinc.com'))
										 ->setTo(array($email))
										 ->setBody('Your verification link: https://gpmain.herokuapp.com/register/validatemail/'.$bytes); //Future tidy up (twig template or whatever)

				$app['mailer']->send($message);

				return new Response('Token Sent!', 201);
			}
			throw new RuntimeException(sprintf('Cant find email for user %s', $userid)); //note just database error or?
		}
	}

	public function foodItemUpdate(Request $request, Application $app) {
		$toEncode = array("error" => "failed to add");

		$token = $app['security.token_storage']->getToken();
		if (null !== $token) {
			$userID = $token->getUser()->getID();
			$foodID = $request->get('foodID');
			$name = $request->get('name');
			$expirDate = $request->get('expiredate');
			$category = $request->get('category');
			$desc = $request->get('description');
			$lat = $request->get('latitude');
			$long = $request->get('longitude');
			$amount = $request->get('amount');
			$weight = $request->get('weight');
			$imageuri = $request->get('image');

			//Check Vars
			if (!is_numeric($userID)) {
				die(json_encode(array("error" => "userID incorrectly defined")));
			} elseif (!is_numeric($foodID)) {
				die(json_encode(array("error" => "foodID incorrectly defined")));
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
				$uriPhp = 'data://'.substr($imageuri, 5);
				$binary = file_get_contents($uriPhp);
				$filename = Uuid::uuid4()->getHex().'.png';
				file_put_contents('images/food/'.$filename, $binary);
			}

			if ($this->db->updateFoodItem($name, $expirDate, $category, $userID, $desc, $lat, $long, $amount, $weight, $filename)) {
				$toEncode = array("success" => "topic added");
			}
		}

		return new RedirectResponse($app->path('user')); //note change redirect on failure/success
	}

	public function foodItemPost(Request $request, Application $app) {
		$toEncode = array("error" => "failed to add");

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
				$uriPhp = 'data://'.substr($imageuri, 5);
				$binary = file_get_contents($uriPhp);
				$filename = Uuid::uuid4()->getHex().'.png';
				file_put_contents('images/food/'.$filename, $binary);
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

	public function getFoodBetween(Request $request, App $app, $start, $num) {
		$toEncode = $this->db->getFoodBetween($start, $num);
		if ($toEncode === null) {
			$toEncode = array('error' => 'failed');
		}

		return new JsonResponse($toEncode);
	}

	public function mainSearch(Request $request, App $app, $category, $search) {
		$toEncode = $this->db->mainSearch($category, $search);
		if ($toEncode === null) {
			$toEncode = array('error' => 'failed');
		}

		return new JsonResponse($toEncode);
	}

	public function searchExtra(Request $request, App $app, $category, $search, $latit, $longit, $radius, $minAmount, $maxAmount, $minWeight, $maxWeight, $sort, $start, $count) {
		$toEncode = $this->db->searchExtra($category, $search, $latit, $longit, $radius, $minAmount, $maxAmount, $minWeight, $maxWeight, $sort, $start, $count);
		if ($toEncode === null) {
			$toEncode = array('error' => 'failed');
		}

		return new JsonResponse($toEncode);
	}

	public function searchLocation(Request $request, App $app, $minLat, $maxLat, $minLong, $maxLong, $category, $search, $minAmount, $maxAmount, $minWeight, $maxWeight, $start, $count){
		$toEncode = $this->db->searchLocation($minLat, $maxLat, $minLong, $maxLong, $category, $search, $minAmount, $maxAmount, $minWeight, $maxWeight, $start, $count);
		if ($toEncode === null) {
			$toEncode = array('error' => 'failed');
		}

		return new JsonResponse($toEncode);
	}

	public function messageUser(Request $request, App $app) {
		$message = $request->get('message');
		$fromid = $request->get('fromid');
		$toid = $request->get('toid');
		$requestid = $request->get('requestid');
		$date = date('Y-m-d h:i:s', time());

		$toEncode = $this->db->addNewUserMessage($message, $fromid, $toid, $requestid);
		$toEncode = $toEncode && $this->db->setMessagesSeen($requestid, $fromid); // For now, set seen when another message is sent

		$url = 'https://gpmainmessaging.herokuapp.com/message';
		$data = array('message' => $message, 'fromid' => $fromid, 'toid' => $toid, 'date' => $date, 'requestid' => $requestid);

		// use key 'http' even if you send the request to https://...
		$options = array(
			'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => http_build_query($data),
			),
		);
		$context = stream_context_create($options);
		$result = file_get_contents($url, false, $context);
		// Change to check for 200 OK response
		if ($result === false || $toEncode === false) {
			return new JsonResponse(array("success" => false));
		} else {
			return new JsonResponse(array("success" => true));
		}
	}

	public function updateName(Request $request, App $app) {
		$response = array("success" => false);
		if ($request->get('name') === 'newname') {
			$newname = $request->get('value');

			//Check Vars
			if (!is_string($newname)) {
				$response['error'] = 'newname incorrectly defined';
				die(json_encode($response));
			}

			$token = $app['security.token_storage']->getToken(); //future refactor this into its own func?

			if (null !== $token) {
				$userID = $token->getUser()->getID();
				if ($this->db->updateFullName($userID, $newname)) {
					$response['success'] = true;
				}
			}
		} else {
			$response['error'] = 'Incorrect sender';
		};

		return new JsonResponse($response);
	}

	public function updatePass(Request $request, App $app) {
		$oldpass = $request->get('oldPassword');
		$newpass = $request->get('password');
		$response = array("success" => false);

		//Check Vars
		if (!is_string($newpass)) {
			$response['error'] = 'newpass incorrectly defined';
			die(json_encode($response));
		}
		if (!is_string($oldpass)) {
			$response['error'] = 'oldpass incorrectly defined';
			die(json_encode($response));
		}

		$token = $app['security.token_storage']->getToken(); //future refactor this into its own func?

		if (null !== $token) {
			$user = $token->getUser();
			$userID = $user->getID();
			$password = $user->getPassword();
			$encoded = $app['security.default_encoder']->encodePassword($newpass, null);
			if (password_verify($oldpass, $password)) {
				if ($this->db->updatePass($userID, $encoded)) {
					$response['success'] = true;
				} else {
					$response['error'] = 'Database error encountered';
				}
			} else {
				$response['error'] = 'Incorret password entered';
			}
		} else {
			$response['error'] = 'Unknown error occured';
		}

		return new JsonResponse($response);
	}

	public function userID(Request $request, App $app) {
		$token = $app['security.token_storage']->getToken();
		$toEncode = array('userID' => 'error');

		if (null !== $token) {
			$userID = $token->getUser()->getID();
			$toEncode['userID'] = $userID;
		}

		return new JsonResponse($toEncode);
	}

	public function getUserFoodInfo(Request $request, App $app, $userid, $foodid, $requestid) {
		$userFoodInfo = $this->db->getUserFoodInfo($userid, $foodid);
		$numUnseenMessages = $this->db->getNumberUnseenMessages($requestid);
		$toEncode = $userFoodInfo + $numUnseenMessages;
		if ($toEncode === null) {
			$toEncode = array('error' => 'failed');
		}

		return new JsonResponse($toEncode);
	}

	public function removeFoodItem(Request $request, App $app, $foodid) {
		$token = $app['security.token_storage']->getToken();
		$toEncode = array('error' => 'foodID or userID incorrect');

		if (null !== $token) {
			$userID = $token->getUser()->getID();
			if ($this->db->removeFoodItem($foodid, $userID)) {
				$toEncode = array('success' => 'Food Item Removed');
			}
		}

		return new RedirectResponse($app->path('user')); //note change redirect on failure/success
	}

	public function addNewRequest(Request $request, App $app, $foodid) {
		// Add Request to database
		$token = $app['security.token_storage']->getToken();
		$toEncode = array('error' => 'foodID or userID incorrect');

		if (null !== $token) {
			$userID = $token->getUser()->getID();
			if ($this->db->addNewRequest($userID, $foodid)) {
				$toEncode = array('success' => 'Food Item requested');
			}
		}

		//return new RedirectResponse($app->path('messenger'));
		return new JsonResponse($toEncode);
	}

	public function foodLikelihood(Request $request, App $app, $foodid) {
		$foodItem = $this->db->getFoodItemByID($foodid);
		// of form [`expirydate` => ...,`category` => ...,`foodid` => ...,`name` => ...,`description` => ...,`latit` => ...,`longit` => ...,`amount` => ...,`weight` => ...,`image` => ...,`active` => ...,`hidden` => ...]

		// Content Here

		return new JsonResponse(array("likelihood" => '80%')); // Temporary Return
	}

	public function wastageAnalysis(Request $request, App $app) {
		$token = $app['security.token_storage']->getToken();
		$toEncode = array('userID' => 'error');

		if (null !== $token) {
			// Ignore all above
			$userID = $token->getUser()->getID(); // <-- Users id

			$foodItems = $this->db->getFoodItemsByUserID($userID); // <-- Array of food items
			// of form [[0] => [...], [1] => [...], [2] => [...]] or something like that
			// each [...] as in foodLikelihood(...)

			// Content Here

		}
	}
}
