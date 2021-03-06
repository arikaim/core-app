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
use Arikaim\Core\Db\Model;
use Arikaim\Core\Cache\Cache;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\App\TwigExtension;
use Arikaim\Core\Routes\Routes;
use Arikaim\Core\View\Html\Page;
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
     * @param array $config
     * @return Container
     */
    public static function create(bool $console = false, $config = [])
    {
        $container = new Container();

        // Config
        $container['config'] = function() {                            
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
        $container['cache'] = function() use($config) {                    
            return new Cache(
                Path::CACHE_PATH,
                Path::CACHE_PATH . '/routes.cache.php',
                $config['settings']['cacheDriver'] ?? Cache::VOID_DRIVER,
                $config['settings']['cache'] ?? false,
                $config['settings']['cacheSaveTime'] ?? 7
            );
        };
      
        // Storage
        $container['storage'] = function() {
            return new \Arikaim\Core\Storage\Storage();
        };
        // Http client  
        $container['http'] = function() {
            return new \Arikaim\Core\Http\HttpClient();
        }; 
        // Package manager factory
        $container['packages'] = function($container) {     
            return new \Arikaim\Core\Packages\PackageManagerFactory(
                $container['cache'],
                $container['storage'],
                $container['http']
            );          
        };
        // Access
        $container['access'] = function() use($config) {
            return new \Arikaim\Core\Access\Access(
                Model::PermissionRelations(),
                Model::Users(),
                null,
                ['key' => $config['settings']['jwtKey'] ?? 'jwtKey']
            );          
        };
        // Init template view. 
        $container['view'] = function ($container)  use($config) {      
            $cacheStatus = $config['settings']['cache'] ?? false;                                            
           
            $view = new \Arikaim\Core\View\View(
                $container['cache'],
                [
                    'access' => $container['access']
                ],
                Path::VIEW_PATH,
                Path::EXTENSIONS_PATH, 
                Path::TEMPLATES_PATH,
                Path::COMPONENTS_PATH,[
                    'cache'      => ($cacheStatus == true) ? Path::VIEW_CACHE_PATH : false,
                    'debug'      => $config['settings']['debug'] ?? false,
                    'demo_mode'  => $config['settings']['demoMode'] ?? false,
                    'autoescape' => false
                ],
                $config['settings']['primaryTemplate'] ?? Page::SYSTEM_TEMPLATE_NAME,
                $config['settings']['templateTheme'] ?? null
            );           

            // Add twig extension         
            $view->addExtension(new TwigExtension());
           
            return $view;
        };    
        // Init page view.
        $container['page'] = function($container) {                     
            $libraryPrams = $container->get('config')->load('ui-library.php',false);
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
        $container['db'] = function() use($config) {  
            try {               
                $relations = include (Path::CONFIG_PATH . 'relations.php');
                $db = new \Arikaim\Core\Db\Db($config['db'],$relations);
            } catch(PDOException $e) {                            
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
            return new \Arikaim\Core\Options\Options($container->get('cache'), Model::Options());               
        };     
       
        // Drivers
        $container['driver'] = function() {   
            return new \Arikaim\Core\Driver\DriverManager(Model::Drivers());  
        };

        // Logger
        $container['logger'] = function() use($config) {   
            return new \Arikaim\Core\Logger\Logger(
                Path::LOGS_PATH . 'errors.log',
                $config['settings']['logger'] ?? false,
                $config['settings']['loggerHandler'] ?? 'file'
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
        $container['event'] = function($container) use($config) {
            return new \Arikaim\Core\Events\EventsManager(
                Model::Events(),Model::EventSubscribers(),
                $container['logger'],
                [
                    'log' => $config['settings']['logEvents'] ?? false 
                ]
            );
        };
        // Jobs queue
        $container['queue'] = function($container) {                     
            return new \Arikaim\Core\Queue\QueueManager(Model::Jobs(),$container['logger']);          
        };          
        // Modules manager
        $container['modules'] = function($container) {           
            return new \Arikaim\Core\Extension\Modules($container->get('cache'));
        };         
        // Service manager
        $container['service'] = function() {           
            return new \Arikaim\Core\Service\ServiceContainer();
        }; 
        // Content providers manager
        $container['content'] = function() {           
            return new \Arikaim\Core\Content\ContentManager();
        }; 

        return $container;
    }
}
