<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\App;

use Arikaim\Container\Container;
use Arikaim\Core\Events\EventsManager;
use Arikaim\Core\Db\Model;
use Arikaim\Core\Cache\Cache;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\App\TwigExtension;
use Arikaim\Core\Packages\PackageManagerFactory;
use Arikaim\Core\Routes\Routes;
use Arikaim\Core\App\Install;
use Arikaim\Core\View\Html\Page;
use Arikaim\Core\Utils\Number;
use Arikaim\Core\Utils\DateTime;
use PDOException;

/**
 * Create system services
 */
class AppContainer
{
    /**
     * Init default services
     *
     * @param boolean $cosole
     * @return Container
     */
    public static function create(bool $console = false)
    {
        $container = new Container();

        // Config
        $container['config'] = function($container) {                            
            $config = new \Arikaim\Core\System\Config('config.php',null,Path::CONFIG_PATH);
            $config->setWriteProtectedVars([
                'settings/jwtKey',
                'settings/defaultLanguage',
                'settings/disableInstallPage',
                'settings/demoMode',
                'db/username',
                'db/password',
                'db/driver',
                'db/host',
                'db/database'
            ]);         
            $config->setReadProtectedVars([
                'settings/jwtKey',
                'db/username',
                'db/password'
            ]);  

            return $config;
        }; 

        // Cache 
        $container['cache'] = function($container) {                    
            $routeCacheFile = Path::CACHE_PATH . '/routes.cache.php';  
            $driver = $container['config']['settings']['cacheDriver'] ?? Cache::VOID_DRIVER;
            $enabled = $container['config']['settings']['cache'] ?? false;    
            $saveTime = $container['config']['settings']['cacheSaveTime'] ?? 7;    

            return new Cache(Path::CACHE_PATH,$routeCacheFile,$driver,$enabled,$saveTime);
        };
      
        // Storage
        $container['storage'] = function($container) {
            return new \Arikaim\Core\Storage\Storage();
        };
        // Http client  
        $container['http'] = function() {
            return new \Arikaim\Core\Http\HttpClient();
        }; 
        // Package manager factory
        $container['packages'] = function($container) {     
            return new PackageManagerFactory($container['cache'],$container['storage'],$container['http']);          
        };
        // Access
        $container['access'] = function($container) {
            $user = Model::Users();  
            $permissions = Model::PermissionRelations();    
            $jwtKey = $container['config']['settings']['jwtKey'] ?? 'jwtKey';

            return new \Arikaim\Core\Access\Access($permissions,$user,null,['key' => $jwtKey]);          
        };
        // Init template view. 
        $container['view'] = function ($container) {      
            $cacheStatus = $container['config']['settings']['cache'] ?? false;                          
            $cache = ($cacheStatus == true) ? Path::VIEW_CACHE_PATH : false;
            $debug = $container['config']['settings']['debug'] ?? false;
            $demoMode = $container['config']['settings']['demoMode'] ?? false;
            $primaryTemplate = $container['config']['settings']['primaryTemplate'] ?? Page::SYSTEM_TEMPLATE_NAME;
            $templateTheme = $container['config']['settings']['templateTheme'] ?? null;

            $view = new \Arikaim\Core\View\View(
                $container['cache'],
                [
                    'access' => $container['access']
                ],
                Path::VIEW_PATH,
                Path::EXTENSIONS_PATH, 
                Path::TEMPLATES_PATH,
                Path::COMPONENTS_PATH,[
                    'cache'      => $cache,
                    'debug'      => $debug,
                    'demo_mode'  => $demoMode,
                    'autoescape' => false
                ],
                $primaryTemplate,
                $templateTheme
            );           

            // Add twig extension
            $twigExtension = new TwigExtension(BASE_PATH,Path::VIEW_PATH);
            $view->addExtension($twigExtension);
            $view->setPrimaryTemplate($primaryTemplate); 

            // Set date and time, number formats   
            Number::setFormat($container['config']['settings']['numberFormat'] ?? null);        
            DateTime::setTimeZone($container['config']['settings']['timeZone'] ?? DateTime::getTimeZoneName());                 
            DateTime::setDateFormat($container['config']['settings']['dateFormat'] ?? null);           
            DateTime::setTimeFormat($container['config']['settings']['timeFormat'] ?? null);  

            return $view;
        };    
        // Init page view.
        $container['page'] = function($container) {                     
            $libraryPrams = $container->get('options')->get('library.params',[]);
            $defaultLanguage = $container['config']['settings']['defaultLanguage'] ?? 'en';     
                      
            return new Page($container->get('view'),$defaultLanguage,$libraryPrams);
        }; 

        // Errors  
        $container['errors'] = function() use ($console) {
            return new \Arikaim\Core\System\Error\Errors(
                Path::CONFIG_PATH . 'errors.json',
                Path::CONFIG_PATH . 'console-errors.json',
                $console
            );          
        };
      
        // Init Eloquent ORM
        $container['db'] = function($container) {  
            try {  
                $relations = $container->get('config')->load('relations.php',false);
                $db = new \Arikaim\Core\Db\Db($container['config']['db'],$relations);
            } catch(PDOException $e) {
                if (Install::isInstalled() == false) {
                    // not installed
                }                
            }      
            return $db;
        };     

        // boot db
        $container['db'];

        // Routes
        $container['routes'] = function($container) {            
            return new Routes(Model::Routes(),$container['cache']);  
        };
        // Options
        $container['options'] = function($container) { 
            $optionsStorage = ($container['db']->hasError() == false) ? Model::Options() : null;                    
            return new \Arikaim\Core\Options\Options($container->get('cache'),$optionsStorage);               
        };     
       
        // Drivers
        $container['driver'] = function() {   
            return new \Arikaim\Core\Driver\DriverManager(Model::Drivers());  
        };

        // Logger
        $container['logger'] = function($container) {   
            return new \Arikaim\Core\Logger\Logger(
                Path::LOGS_PATH . 'errors.log',
                $container['config']['settings']['logger'] ?? false,
                $container['config']['settings']['loggerHandler'] ?? 'file'
            );           
        };      

        // Init email view.
        $container['email'] = function($container) {                     
            $defaultLanguage = $container['config']['settings']['defaultLanguage'] ?? 'en';     
                    
            return new \Arikaim\Core\View\Html\EmailView($container->get('view'),$defaultLanguage);
        }; 

        // Mailer
        $container['mailer'] = function($container) {
            $mailerOptions = [
                'from_email' => $container['options']->getString('mailer.from.email',''),
                'from_name'  => $container['options']->getString('mailer.from.name',''),
                'log'        => $container['options']->get('mailer.log',false),
                'log_error'  => $container['options']->get('mailer.log.error',false),                
                'compillers' => $container['options']->get('mailer.email.compillers',[])
            ];

            $driverName = $container['options']->getString('mailer.driver',null);
            $driver = (empty($driverName) == false) ? $container['driver']->create($driverName) : null;
            if ($driver === false) {
                $driver = null;
            }
            
            return new \Arikaim\Core\Mail\Mailer($mailerOptions,$container['email'],$driver,$container['logger']);
        };

        // Events manager 
        $container['event'] = function($container) {
            return new EventsManager(Model::Events(),Model::EventSubscribers(),$container['logger'],[
                'log' => $container['config']['settings']['logEvents'] ?? false 
            ]);
        };
        // Jobs queue
        $container['queue'] = function($container) {           
            $jobs = Model::Jobs();
            return new \Arikaim\Core\Queue\QueueManager($jobs,$container['logger']);          
        };          
        // Modules manager
        $container['modules'] = function($container) {           
            return new \Arikaim\Core\Extension\Modules($container->get('cache'));
        };         
        // Service manager
        $container['service'] = function() {           
            return new \Arikaim\Core\Service\ServiceContainer();
        }; 

        return $container;
    }
}
