<?php


namespace Main;

use Silex\Application;
use Silex\Provider\{TwigServiceProvider, UrlGeneratorServiceProvider, SessionServiceProvider, ValidatorServiceProvider};
use Silex\Provider\{FormServiceProvider,HttpCacheServiceProvider,HttpFragmentServiceProvider,SecurityServiceProvider};
use Silex\Provider\{RememberMeServiceProvider,SwiftmailerServiceProvider,MonologServiceProvider,RoutingServiceProvider};
use Silex\Provider\{DoctrineServiceProvider,ServiceControllerServiceProvider,AssetServiceProvider,WebProfilerServiceProvider};
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Handler\Requests;
use Database\DBDataMapper;

//fixme use some\namespace\{ClassA, ClassB, ClassC as C}; todo this

class App extends Application{
    //TODO: Make use of these. all of them
    use Application\TwigTrait;
    use Application\SecurityTrait;
    use Application\FormTrait;
    use Application\UrlGeneratorTrait;
    use Application\SwiftmailerTrait;
    use Application\MonologTrait;

    public function __construct(array $values = array()) {
        parent::__construct($values);

        //future YAML config files? We need configs...

        $this->registerServices();

        $this->registerSecurity();

        $this['route_class'] = 'Main\SecureRouter';

        $this->defineBasicRoutes();

        $this->accountRoutes();

        $this->restAPI();

        $this->errorHandling();

    }

    private function registerServices(){
        // Register the monolog logging service
        $this->register(new MonologServiceProvider(), array(
            'monolog.logfile' => 'php://stderr',
            'monolog.level' => 'WARNING',  //note change to debug if you want messages everywhere
        ));

        // Register view rendering
        $this->register(new TwigServiceProvider(), array(
            'twig.path' =>
                array(
                    ROOT . '/../src/Views/html/',
                    ROOT . '/../src/Views/html/components',
                )
        ));
        // Registering service controllers
        $this->register(new ServiceControllerServiceProvider());

        // Register session storage for between request data store
        $this->register(new SessionServiceProvider());

        // Register security service
        $this->register(new SecurityServiceProvider());
        //TODO: remember me
        //TODO: ValidatorServiceProvider

        // Generate urls from bound names
        $this->register(new RoutingServiceProvider());

        //note what is this
        $this->register(new HttpFragmentServiceProvider());

        //future: 2 new services, validator and form service? bootstrap forms?
        //TODO: emailing and account validation

        // Register web profiler if in debug mode
        if ($this['debug']) {
            $this->register(new WebProfilerServiceProvider(), array(
                'profiler.cache_dir' => ROOT . '/../cache/profiler',
                'profiler.mount_prefix' => '/_profiler', // this is the default
            ));
        }

        // Register asset rerouting
        $this->register(new AssetServiceProvider(), array(
            'assets.version' => 'v1',
            'assets.version_format' => '%s?version=%s',
            'assets.named_packages' => array(
                'css' => array('version' => 'css3', 'base_path' => 'stylesheets/'),
                'images' => array('base_path' => 'images/'),
                'food' => array('base_path' => 'images/food/'),
                'javascript' => array('base_path' => 'js/'),
            ),
        ));

        // Register DB provider service
        $this['DB'] = function() {
            return new DBDataMapper($debug = $this['debug']);
        };

        // Register our routing controllers
        $this['rest.handler'] = function() {
            return new Requests($this['DB']);
        };

        // Register the user provider for security authentication
        //future use converters that input username and output a user class, either security or otherwise
        $this['user.provider'] = function () {
            return new UserProvider($this['DB']);
        };

    }

    private function registerSecurity(){
        //future look into the entire security package, make use of it all

        //future logout

        //fixme only one firewall? cant authenticate to two
        $this['security.firewalls'] = array(
            'main' => array(
                'anonymous' => true,
                'form' => array('login_path' => '/login', 'check_path' => '/account/login/check'),
                'logout' => array('logout_path' => '/account/logout', 'invalidate_session' => true),
                'switch_user' => array('parameter' => '_switch_user', 'role' => 'ROLE_ALLOWED_TO_SWITCH'),
                'users' => $this['user.provider'],
            ),
        );

        $this['security.role_hierarchy'] = array(
            'ROLE_ADMIN' => array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'),
        );

        $this['security.access_rules'] = array(
            array('^/admin', 'ROLE_ADMIN', 'https'),
            array('^/account', 'ROLE_USER', 'https'),
        );

    }

    private function errorHandling() {
        //future handle authentication errors with redirects and messages
        //future includes admin pages (which raise AccessDeniedHttpException)

        //note need better error handling here
        $this->error(function (\Exception $e, $code) :?Response {
            if ($this['debug']) {
                // in debug mode we want to get the regular error message
                return null; //fixme check works, could be cause of problems(remove null)
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

    private function defineBasicRoutes() {
        $this->get('/', function() {
            return $this['twig']->render('index.twig');
        })->bind('index');

        $this->get('/index', function()  {
            return new RedirectResponse($this->url('index'));
        });

        $this->get('/login', function(Request $request) {
            if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
                return new RedirectResponse($this->url('index'));
            } else {
            return $this['twig']->render('login.twig', array(
                'error'         => $this['security.last_error']($request),
                'last_username' => $this['session']->get('_security.last_username'),
            ));}
        })->bind('login');

        $this->get('/register', function() {
            return $this['twig']->render('signup.twig');
        })->bind('register')->requireHttps();

        $this->get('/item/{id}', function($id) {
            return $this['twig']->render('itemPage.twig', array (
                'itemid' => $id,
                )
            );
        });
        //})->bind('item');

    }

    private function accountRoutes(){
        $account = $this['controllers_factory'];

        $account->get('/scanner', function() {
            return $this['twig']->render('scanner.twig');
        })->bind('scanner');

        $account->get('/userprofile', function(){
            return $this['twig']->render('userProfile.twig');
        })->bind('user');

        $this->mount('/account', $account);
    }

    private function restAPI(){
        $this->get('/food/{foodID}', 'rest.handler:foodItemGet')
            -> assert('foodID', '\d+');

        $this->get('/food/html/{foodID}', function($foodID) {
          $foodData = $this['DB']->getFoodItemByID($foodID);
          return $this->renderView('foodcard.twig', array (
              'name' => foodData['name'],
              'description' => foodData['description'],
              'expiry' => foodData['expiry'],
              'amount' => foodData['amount'],
              'weight' => foodData['weight']
              )
          );
        }) -> assert('foodID', '\d+');

        $this->get('/food/{userID}', 'rest.handler:foodItemsGet')
            -> assert('userID', '\d+');

        $this->get('/search/{category}/{search}', 'rest.handler:mainSearch')
            -> assert('category', '[a-zA-Z0-9_ ]*')
            -> assert('search', '[a-zA-Z0-9_ ]*');

        //future Secure post for registered users only
        $this->post('/food', 'rest.handler:foodItemPost')
            -> secure('ROLE_USER');

        $this->post('/register/user', 'rest.handler:registerNewUser')
            -> requireHttps()
            -> assert('username', '^[a-zA-Z0-9_]+$')
            -> assert('password','^[\w]+$');

    }

    //future admin routes

}
