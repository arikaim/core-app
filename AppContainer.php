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
        // Cache 
        $container['cache'] = function($container) {                    
            $routeCacheFile = Path::CACHE_PATH . '/routes.cache.php';                   
            return new Cache(Path::CACHE_PATH,$routeCacheFile,Cache::ARRAY_DRIVER,true);
        };
        // Config
        $container['config'] = function($container) {    
            $cache = $container->get('cache');                         
            $config = new \Arikaim\Core\System\Config('config.php',$cache,Path::CONFIG_PATH);         
            return $config;
        }; 
        $cacheStatus = (bool)$container->get('config')->getByPath('settings/cache',false);

        // init cache status
        $container->get('cache')->setStatus($cacheStatus);
        $container->get('cache')->setDriver($container->get('config')->getByPath('settings/cacheDriver',Cache::FILESYSTEM_DRIVER));

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
        // Init template view. 
        $container['view'] = function ($container) use($cacheStatus) {                            
            $cache = ($cacheStatus == true) ? Path::VIEW_CACHE_PATH : false;
            $debug = $container->get('config')['settings']['debug'] ?? true;
            $demoMode = $container->get('config')['settings']['demo_mode'] ?? false;
            $primaryTemplate = $container->get('config')->getByPath('settings/primaryTemplate',Page::SYSTEM_TEMPLATE_NAME);
           
            $view = new \Arikaim\Core\View\View(
                $container['cache'],
                Path::VIEW_PATH,
                Path::EXTENSIONS_PATH, 
                Path::TEMPLATES_PATH,
                Path::COMPONENTS_PATH,[
                    'cache'      => $cache,
                    'debug'      => $debug,
                    'demo_mode'  => $demoMode,
                    'autoescape' => false
                ],
                $primaryTemplate
            );           

            // Add twig extension
            $twigExtension = new TwigExtension(BASE_PATH,Path::VIEW_PATH,$container);
            $view->addExtension($twigExtension);
            $view->setPrimaryTemplate($primaryTemplate); 

            return $view;
        };    
        // Init page components.
        $container['page'] = function($container) {                     
            $libraryPrams = $container->get('options')->get('library.params',[]);
            $defaultLanguage = $container->get('options')->get('default.language','en');     
                      
            return new Page($container->get('view'),$defaultLanguage,$libraryPrams);
        }; 
        // Errors  
        $container['errors'] = function($container) use ($console) {
            return new \Arikaim\Core\System\Error\Errors(
                Path::CONFIG_PATH . 'errors.json',
                Path::CONFIG_PATH . 'console-errors.json',
                $console
            );          
        };
        // Access
        $container['access'] = function($container) {
            $user = Model::Users();  
            $permissins = Model::PermissionRelations();    
            $jwtKey = $container['config']->getByPath('settings/jwtKey','jwtKey');

            return new \Arikaim\Core\Access\Access($permissins,$user,null,['key' => $jwtKey]);          
        };
        // Init Eloquent ORM
        $container['db'] = function($container) {  
            try {  
                $relations = $container->get('config')->load('relations.php');
                $db = new \Arikaim\Core\Db\Db($container->get('config')['db'],$relations);
            } catch(PDOException $e) {
                if (Install::isInstalled() == false) {
                    // not installed
                }                
            }      
            return $db;
        };     

        $container['db'];

        // Routes
        $container['routes'] = function($container) {            
            return new Routes(Model::Routes(),$container['cache']);  
        };
        // Options
        $container['options'] = function($container) { 
            $optionsStorage = ($container['db']->hasError() == false) ? Model::Options(): null;                    
            $options = new \Arikaim\Core\Options\Options($container->get('cache'),$optionsStorage);    
          
            Number::setFormat($options->getString('number.format',null));
            // Set time zone
            DateTime::setTimeZone($options->getString('time.zone',null));
            // Set date and time formats          
            DateTime::setDateFormat($options->getString('date.format',null));           
            DateTime::setTimeFormat($options->getString('time.format',null));  
            
            return $options;
        };     
       
        // Drivers
        $container['driver'] = function() {   
            return new \Arikaim\Core\Driver\DriverManager(Model::Drivers());  
        };

        // Logger
        $container['logger'] = function($container) {   
            $enabled = $container->get('options')->get('logger',true); 
            $handlerName = $container->get('options')->get('logger.handler','file'); 

            $logger = new \Arikaim\Core\Logger\Logger(Path::LOGS_PATH . 'errors.log',$enabled,$handlerName);
            return $logger;
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
            return new \Arikaim\Core\Mail\Mailer($mailerOptions,$container['page'],$driver,$container['logger']);
        };

        // Events manager 
        $container['event'] = function($container) {
            $options = [
                'log' => $container['config']->getByPath('settings/logEvents',false) 
            ];

            return new EventsManager(Model::Events(),Model::EventSubscribers(),$container['logger'],$options);
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
