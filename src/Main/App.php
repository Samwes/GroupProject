<?php


namespace Main;

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\HttpCacheServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Silex\Provider\RememberMeServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use Handler\Controller;
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

        $this['route_class'] = 'SecureRouter';

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
                    __DIR__ . '/../src/views/html/',
                    __DIR__ . '/../src/views/html/components',
                )
        ));
        // Registering service controllers
        $this->register(new ServiceControllerServiceProvider());

        // Register session storage for between request data store
        $this->register(new SessionServiceProvider());

        // Register security service
        $this->register(new SecurityServiceProvider());
        //TODO: remember me

        // Generate urls from bound names
        $this->register(new RoutingServiceProvider());

        //note what is this
        $this->register(new HttpFragmentServiceProvider());

        //future: 2 new services, validator and form service? bootstrap forms?
        //TODO: emailing and account validation

        // Register web profiler if in debug mode
        if ($this['debug']) {
            $this->register(new WebProfilerServiceProvider(), array(  //note uses original
                'profiler.cache_dir' => __DIR__.'/../cache/profiler',
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
            return new DBDataMapper();
        };

        // Register our routing controllers
        $this['rest.handler'] = function() {
            return new Controller($this['DB']);
        };

        // Register the user provider for security authentication
        //future use converters that input username and output a user class, either security or otherwise
        $this['user.provider'] = function () {
            return new UserProvider($this['DB']);
        };

    }

    private function registerSecurity(){
        //future look into the entire security package, make use of it all
        //example: security.providers for database users and user class instead of users in each firewall
        //note eveyrthing is secured 3 times, maybe overkill? can put in our end notes

        //future logout


        $this['security.firewalls'] = array(
            'login' => array(
                'pattern' => '^/login',  //Match all login pages
            ),
            //future seperate logins or some shit
            'loggedin' => array(
                'pattern' => '^/account',
                'form' => array('login_path' => '/login', 'check_path' => '/account/login/check'),
                'users' => $this['user.provider'],
            ),

            'admin' => array( //note no idea if this works (the login check part for admin accounts)
                'pattern' => '^/admin',
                'form' => array('login_path' => '/login', 'check_path' => '/admin/login/check'),
                'users' => $this['user.provider'],
            ),

            'unsecured' => array(
                'anonymous' => true,
                'switch_user' => array('parameter' => '_switch_user', 'role' => 'ROLE_ALLOWED_TO_SWITCH'),
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
}