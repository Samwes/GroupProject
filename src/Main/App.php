<?php

namespace Main;

use Database\DBDataMapper;
use Handler\Requests;
use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\RememberMeServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\VarDumperServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Umpirsky\Twig\Extension\PhpFunctionExtension;

class App extends Application
{
	use Application\TwigTrait;
	use Application\SecurityTrait;
	use Application\FormTrait;
	use Application\UrlGeneratorTrait;
	use Application\SwiftmailerTrait;
	use Application\MonologTrait;

	//future smaller item cards (for smaller screens) with bare essentials
	//future image upload - remove button??

	public function __construct(array $values = array()) {
		parent::__construct($values);

		$this->registerServices();

		$this->registerSecurity();

		$this['route_class'] = 'Main\SecureRouter';

		$this->defineBasicRoutes();

		$this->accountRoutes();

		$this->restAPI();

		$this->errorHandling();

		//$api = new \Cloudinary\Api();
		//$result = $api->resource("food/bdk5uhvjuookqcqkdyzp");
		//die(dump($result));
		//die(dump(cloudinary_url_folder('k0vx5tenfq9ugtzmcjya','food')));

		//$result = \Cloudinary\Uploader::explicit("food/ede4681c3ebe4753af067e26a4dacc45", $options = array('invalidate' => true, "type" => "upload" ));
		//die(dump($result));

		// Register web profiler if in debug mode
		if ($this['debug']) {
			$this->register(new VarDumperServiceProvider());
			$this->register(new WebProfilerServiceProvider(), array(
				'profiler.cache_dir'    => ROOT.'/../cache/profiler',
				'profiler.mount_prefix' => '/_profiler', // this is the default
			));
		}
	}

	private function registerServices() {
		// Register the monolog logging service
		$this->register(new MonologServiceProvider(), array(
			'monolog.logfile' => 'php://stderr',
			'monolog.level'   => 'WARNING',  //note change to debug if you want messages everywhere
		));

		// Register view rendering
		$this->register(new TwigServiceProvider(), array(
			'twig.path' =>
				array(
					ROOT.'/../src/Views/',
					ROOT.'/../src/Views/components',
				),
		));

		$this->extend('twig', function ($twig) {
			$extension = new PhpFunctionExtension(array('cloudinary_url_folder'));
			$twig->addExtension($extension);
			return $twig;
		});

		// Registering service controllers
		$this->register(new ServiceControllerServiceProvider());

		// Register session storage for between request data store
		$this->register(new SessionServiceProvider());

		// Register security service
		$this->register(new SecurityServiceProvider());
		$this->register(new RememberMeServiceProvider());

		// Generate urls from bound names
		$this->register(new RoutingServiceProvider());

		$this->register(new HttpFragmentServiceProvider());

		// Register asset rerouting
		$this->register(new AssetServiceProvider(), array(
			'assets.version'        => 'v1',
			'assets.version_format' => '%s?version=%s',
			'assets.named_packages' => array(
				'css'        => array('version' => 'css3', 'base_path' => 'stylesheets/'),
				'images'     => array('base_urls' => array('https://res.cloudinary.com/hxovetfvu/misc')),
				'users'      => array('base_urls' => array('https://res.cloudinary.com/hxovetfvu/people')),
				'javascript' => array('base_path' => 'js/'),
			),
		));

		$this->register(new SwiftmailerServiceProvider(), array(
			'swiftmailer.options' => array(
				'host'       => getenv('EMAIL_SMTP_HOST'),
				'port'       => getenv('EMAIL_SMTP_PORT'),
				'username'   => getenv('EMAIL_SMTP_USERNAME'),
				'password'   => getenv('EMAIL_SMTP_PASSWORD'),
				'encryption' => 'tls',
				'auth_mode'  => 'cram-md5',
			),
		));

		// Register DB provider service
		$this['DB'] = function () {
			return new DBDataMapper($debug = $this['debug']);
		};

		// Register our routing controllers
		$this['rest.handler'] = function () {
			return new Requests($this['DB']);
		};

		// Register the user provider for security authentication
		$this['user.provider'] = function () {
			return new UserProvider($this['DB']);
		};
	}

	private function registerSecurity() {
		$this['security.firewalls'] = array(
			'main' => array(
				'anonymous'   => true,
				'form'        => array('login_path' => '/login', 'check_path' => '/account/userprofile'),
				'logout'      => array('logout_path' => '/account/logout', 'invalidate_session' => true),
				'switch_user' => array('parameter' => '_switch_user', 'role' => 'ROLE_ALLOWED_TO_SWITCH'),
				'remember_me' => array(
					'key'      => '801876fdda6972348a4a0f7c7c07e7ee88557f696f34d00a14ffa0ad97e519c',
					'lifetime' => 604800, // 1 week in seconds
				),
				'users'       => $this['user.provider'],
			),
		);

		$this['security.role_hierarchy'] = array(
			'ROLE_ADMIN' => array('ROLE_BASIC', 'ROLE_ALLOWED_TO_SWITCH'),
			'ROLE_USER'  => array('ROLE_BASIC'),
		);

		$this['security.access_rules'] = array(
			array('^/admin', 'ROLE_ADMIN', 'https'),
			array('^/account', 'ROLE_BASIC', 'https'),
		);
	}

	private function defineBasicRoutes() {
		$this->get('/', function () {
			return $this['twig']->render('index.twig');
		})->bind('index');

		$this->get('/index', function () {
			return new RedirectResponse($this->url('index'));
		});

		$this->get('/login', function (Request $request) {
			if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
				return new RedirectResponse($this->url('index'));
			}

			return $this['twig']->render('login.twig', array(
				'error'         => $this['security.last_error']($request),
				'last_username' => $this['session']->get('_security.last_username'),
			));
		})->bind('login');

		$this->get('/register', function () {
			return $this['twig']->render('signup.twig');
		})->bind('registerPage')->requireHttps();
	}

	private function accountRoutes() {
		$account = $this['controllers_factory'];

		$account->get('/addItem', function () {
			$userdata = $this['DB']->getUserByUsername((string) $this['security.token_storage']->getToken()->getUser());
			return $this['twig']->render('scanner.twig', array('userData' => $userdata));
		})->bind('additem')->secure('ROLE_BASIC');

		$account->get('/addItem/{foodID}', function ($foodID) {
			$userdata = $this['DB']->getUserByUsername((string) $this['security.token_storage']->getToken()->getUser());
			$fooddata = $this['DB']->getFoodItemByID($foodID);
			return $this['twig']->render('update.twig', array('userData' => $userdata, 'foodData' => $fooddata, 'foodID' => $foodID));
		})->assert('foodID', '\d+')->bind('update')->secure('ROLE_BASIC');

		$account->post('/getItem', function (Request $request) {
			return $this['twig']->render('aUserItem.twig', array('request' => $request));
		})->bind('getItem')->secure('ROLE_BASIC');

		$account->get('/userprofile', function () {
			$userdata = $this['DB']->getUserByUsername((string) $this['security.token_storage']->getToken()->getUser());
			return $this['twig']->render('userProfile.twig', array('userData' => $userdata));
		})->bind('user')->secure('ROLE_BASIC');  //Double secured so dont check token exists

		$account->get('/userprofiletest', function () {
			return $this['twig']->render('userProfileTest.twig');
		})->bind('usertest');

		$account->get('/useritems', function () {
			return $this['twig']->render('userItems.twig');
		})->bind('useritems');

		$account->get('/messenger', function () {
			return $this['twig']->render('messenger.twig');
		})->bind('messenger');

		$account->post('/update/fullname', 'rest.handler:updateName')
				->bind('updatename')
				->secure('IS_AUTHENTICATED_FULLY');

		$account->post('/update/password', 'rest.handler:updatePass')
				->bind('updatepass')
				->secure('IS_AUTHENTICATED_FULLY');

		$account->post('/request/accept', 'rest.handler:acceptRequest')
				->assert('requestid', '\d+')
				->assert('foodid', '\d+')
				->secure('ROLE_BASIC');

		$account->post('/request/reject', 'rest.handler:rejectRequest')
				->assert('requestid', '\d+')
				->secure('ROLE_BASIC');

		$account->get('/request/status/{requestid}', 'rest.handler:requestStatus')
				->assert('requestid', '\d+')
				->secure('ROLE_BASIC');

		$account->get('/user/notifications', 'rest.handler:getNumberNotifications')
				->secure('ROLE_BASIC');

		$account->post('/user/review', 'rest.handler:reviewUser')
				->secure('ROLE_BASIC');

		$this->mount('/account', $account);
	}

	private function restAPI() {
		$this->get('/food/{foodID}', 'rest.handler:foodItemGet')
			 ->assert('foodID', '\d+');

		$this->get('/food/html/{foodID}', function ($foodID) {
			$foodData = $this['DB']->getFoodItemByID($foodID);
			return $this->renderView('foodcard.twig', array('foodData' => $foodData, 'foodID' => $foodID));
		})->assert('foodID', '\d+');

		$this->get('/food/request/{foodid}', 'rest.handler:addNewRequest')
			 ->assert('foodid', '\d+')->secure('ROLE_BASIC')->bind('foodRequest');

		$this->get('/item/{id}', function ($id) {
			$foodData = $this['DB']->getFoodItemByID($id);
			$userData = $this['DB']->getUserByID($foodData['userid']);
			if (($foodData === false) || ($userData === false)) {
				throw new Exception('An error occured');
			}
			return $this['twig']->render('itemPage.twig', array('foodData' => $foodData, 'userData' => $userData, 'foodID' => $id));
		});

		$this->get('/food/likelihood/{foodid}', 'rest.handler:foodLikelihood')
			 ->assert('foodid', '\d+');

		$this->get('/foodItems', 'rest.handler:foodItemsGet')
			 ->secure('ROLE_BASIC');

		$this->get('/user/rating/{userid}', 'rest.handler:getUserRating')
			 ->assert('userid', '\d+');

		$this->get('/request/sent', 'rest.handler:getRequestsSentByUserID')
			 ->secure('ROLE_BASIC');

		$this->get('/request/received', 'rest.handler:getRequestsReceivedByUserID')
			 ->secure('ROLE_BASIC');

		$this->get('/request/messages/{requestID}', 'rest.handler:getUserMessagesByRequestID')
			 ->secure('ROLE_BASIC')
			 ->assert('requestID', '\d+');

		//note deprecated?
		$this->get('/food/{start}/{num}', 'rest.handler:getFoodBetween')
			 ->assert('start', '[0-9]*')
			 ->assert('num', '[0-9]*');

		//note deprecated?
		$this->get('/search/{category}/{search}', 'rest.handler:mainSearch')
			 ->assert('category', '[a-zA-Z0-9_ ]*')
			 ->assert('search', '[a-zA-Z0-9_ ]*');

		$this->get('/search/location/{minLat}/{maxLat}/{minLong}/{maxLong}/{category}/{search}/{minAmount}/{maxAmount}/{minWeight}/{maxWeight}/{start}/{count}', 'rest.handler:searchLocation')
			 ->assert('minLat', '[-+]?[0-9]*\.?[0-9]+')
			 ->assert('maxLat', '[-+]?[0-9]*\.?[0-9]+')
			 ->assert('minLong', '[-+]?[0-9]*\.?[0-9]+')
			 ->assert('maxLong', '[-+]?[0-9]*\.?[0-9]+')
			 ->assert('category', '[a-zA-Z0-9_ ]*')
			 ->assert('search', '[a-zA-Z0-9_ ]*')
			 ->assert('minAmount', '[0-9]*')
			 ->assert('maxAmount', '[0-9]*')
			 ->assert('minWeight', '[0-9]*')
			 ->assert('maxWeight', '[0-9]*')
			 ->value('start', 0)->assert('start', '[0-9]*')
			 ->value('count', 12)->assert('count', '[0-9]*');

		//todo add sorting to slider (remove right 3 buttons) add remove button for each slider
		$this->get('/search/{category}/{search}/{latit}/{longit}/{radius}/{minAmount}/{maxAmount}/{minWeight}/{maxWeight}/{sort}/{start}/{count}', 'rest.handler:searchExtra')
			 ->assert('category', '[a-zA-Z0-9_ ]*')
			 ->assert('search', '[a-zA-Z0-9_ ]*')
			 ->assert('latit', '[-+]?[0-9]*\.?[0-9]+')
			 ->assert('longit', '[-+]?[0-9]*\.?[0-9]+')
			 ->assert('radius', '[0-9]*')
			 ->assert('minAmount', '[0-9]*')
			 ->assert('maxAmount', '[0-9]*')
			 ->assert('minWeight', '[0-9]*')
			 ->assert('maxWeight', '[0-9]*')
			 ->assert('sort', '[a-z\-]*')
			 ->value('start', 0)->assert('start', '[0-9]*')
			 ->value('count', 12)->assert('count', '[0-9]*');

		$this->get('/messenger/userid', 'rest.handler:userID')
			 ->secure('ROLE_BASIC');

			 $this->get('/messenger/userfood/{userid}/{foodid}/{requestid}', 'rest.handler:getUserFoodInfo')
	 			 ->assert('userid', '\d+')
	 			 ->assert('foodid', '\d+')
	 			 ->assert('requestid', '\d+') // Should probably by under account
	 			 ->secure('ROLE_BASIC');

		$this->get('/user/analysis', 'rest.handler:wastageAnalysis')
			 ->secure('ROLE_BASIC');

		//todo default food picture per category
		$this->post('/food', 'rest.handler:foodItemPost')
			 ->secure('ROLE_BASIC');

		$this->post('/food/update', 'rest.handler:foodItemUpdate')
			 ->secure('ROLE_BASIC');

		$this->get('/food/remove/{foodid}', 'rest.handler:removeFoodItem')
			 ->assert('foodid', '\d+')->secure('ROLE_BASIC');

		//todo registration failure page
		$this->post('/register/user', 'rest.handler:registerNewUser')
			 ->requireHttps()->bind('register')
			 ->assert('username', '^[a-zA-Z0-9_]+$')// fixme this needed?
			 ->assert('password', '^[\w]+$');

		$this->post('/messenger/message', 'rest.handler:messageUser')
			 ->requireHttps()
			 ->assert('message', '[\s\S]*')
			 ->assert('fromid', '\d+')
			 ->assert('toid', '\d+')
			 ->assert('requestid', '\d+');

		//future send token + resend option
		$this->get('/register/validatemail/{token}', 'rest.handler:verifyToken');
	}

	private function errorHandling() {
		//future handle authentication errors with redirects and messages
		//note includes admin pages (which raise AccessDeniedHttpException)
		//note include posting food items
		//future resend auth token to email

		//note need better error handling here
		$this->error(function (\Exception $e, Request $request, $code):?Response {
			if ($this['debug']) {
				// in debug mode we want to get the regular error message

				return null;
			}
			switch ($code) {
				case 404:
					$message = 'The requested page could not be found.';
					break;
				default:
					$message = 'We are sorry, but something went terribly wrong.';
			}
			return new Response($message);
		});
	}

	//future admin routes
	//todo improved error messages throughout
}
