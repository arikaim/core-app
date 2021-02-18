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

use Arikaim\Core\Interfaces\Access\AccessInterface;
use Arikaim\Core\Utils\DateTime;
use Arikaim\Core\Arikaim;
use Arikaim\Core\Db\Schema;
use Arikaim\Core\Db\Model;
use Arikaim\Core\System\System;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Path;
use Exception;
use Closure;

/**
 * Arikaim install
 */
class Install 
{
    /**
     * Set config files writable
     *
     * @return bool
     */
    public static function setConfigFilesWritable(): bool
    {
        $result = true;
        $configFile = Arikaim::config()->getConfigFile();
        if (File::isWritable($configFile) == false) {
            $result = (File::setWritable($configFile) == false) ? false : $result;
        }
        $relationsFile = PAth::CONFIG_PATH . 'relations.php';
        if (File::isWritable($relationsFile) == false) {
            $result = (File::setWritable($relationsFile) == false) ? false : $result;
        }

        return $result;
    }

    /**
     * Prepare install
     *
     * @param Closure|null $onError
     * @param Closure|null $onProgress
     * @param array|null $requirements
     * @return bool
     */
    public function prepare(?Closure $onProgress = null, ?Closure $onError = null, ?array $requirements = null): bool
    {
        $status = true;
        // check requirments
        $requirements = $requirements ?? Self::checkSystemRequirements();
        foreach ($requirements['errors'] as $error) {
            $this->callback($onError,$error);
        }
        if (\count($requirements['errors']) > 0) {
            $status = false;
        }

        // cache dir
        File::makeDir(Path::CACHE_PATH,0777);
        $result = File::isWritable(Path::CACHE_PATH); 
        if ($result == false) {
            $this->callback($onError,"Can't set cache dir writable.");
            $status = false;
        } else {
            $this->callback($onProgress,"Cache directory set writable.");
        }
        // set config files writable
        $result = Self::setConfigFilesWritable();
        if ($result == false) {
            $this->callback($onError,"Can't set config files writable.");
            $status = false;
        } else {
            $this->callback($onProgress,"Config files set writable.");
        }

        return $status;
    }

    /**
     * Call closure
     *
     * @param Closure|null $closure
     * @param string $message
     * @return void
     */
    protected function callback($closure, $message): void
    {
        if (\is_callable($closure) == true) {
            $closure($message);
        }
    }

    /**
     * Install Arikaim
     *
     * @param Closeure|null $onProgress
     * @param Closeure|null $onProgressError
     * @param Closeure|null $onProgressCompleted
     * @return boolean
     */
    public function install($onProgress = null, $onProgressError = null): bool 
    {         
        System::setTimeLimit(0);

        // create database if not exists  
        $databaseName = Arikaim::config()->getByPath('db/database');
      
        if (Arikaim::db()->has($databaseName) == false) {
            $charset = Arikaim::config()->getByPath('db/charset'); 
            $collation = Arikaim::config()->getByPath('db/collation');
            $result = Arikaim::db()->createDb($databaseName,$charset,$collation); 
            if ($result == false) {
                $this->callback($onProgressError,'Error create database ' . $databaseName);              
                return false;                
            }         
            $this->callback($onProgress,'Database created.');    
            // reboot db    
            Arikaim::db()->reboot(Arikaim::config()->get('db')); 
        }          

        // check db
        if (Arikaim::db()->has($databaseName) == false) {
            $error = Arikaim::errors()->getError('DB_DATABASE_ERROR');          
            $this->callback($onProgressError,$error); 
            return false;
        }
        $this->callback($onProgress,'Database status ok.');
       
        Arikaim::options()->setStorageAdapter(Model::Options());

        // Create Arikaim DB tables
        $result = $this->createDbTables(function($class) use ($onProgress) {
            $this->callback($onProgress,'Db table model created ' . $class);
        },function($class) use ($onProgressError) {
            $this->callback($onProgressError,'Error creatinng db table model ' . $class);
        });      

        if ($result !== true) {    
            $this->callback($onProgressError,"Error creating system db tables.");        
            return false;
        } 
        $this->callback($onProgress,'System db tables created.'); 

        // Add control panel permisison item       
        $result = Arikaim::access()->addPermission(
            AccessInterface::CONTROL_PANEL,
            AccessInterface::CONTROL_PANEL,
            'Arikaim control panel access.'
        );
        if ($result == false) {    
            if (Model::Permissions()->has(AccessInterface::CONTROL_PANEL) == false) {
                $error = Arikaim::errors()->getError('REGISTER_PERMISSION_ERROR',['name' => 'ContorlPanel']);
                $this->callback($onProgressError,$error);
                return false;
            }           
        } else {
            $this->callback($onProgress,'Control panel permission added.');
        }

        // register core events
        $this->registerCoreEvents();
        $this->callback($onProgress,'Register system events');      

        // reload seystem options
        Arikaim::options()->load();
      
        // create admin user if not exists       
       
        $result = $this->createDefaultAdminUser();
        if ($result === false) {
            $this->callback($onProgressError,'Error creating control panel user');
            return false;
        }
        $this->callback($onProgress,'Control panel user created.');      
       
        // add date, time, number format items     
        $this->initDefaultOptions();
        $this->callback($onProgress,'Default system options saved.');   

        // install drivers
        $result = $this->installDrivers();
        if ($result === false) {
            $this->callback($onProgressError,'Error register cache driver');
        }

        // set storage folders              
        $this->initStorage();
        $this->callback($onProgress,'Storage folders created.'); 

        return true;
    } 

    /**
     * Install all modules
     *     
     * @param Closure|null $onProgress
     * @param Closure|null $onProgressError
     * @return boolean
     */
    public function installModules($onProgress = null, $onProgressError = null)
    {      
        System::setTimeLimit(0);

        try {
            // Install modules
            $modulesManager = Arikaim::packages()->create('module');
            $result = $modulesManager->installAllPackages($onProgress,$onProgressError);
        } catch (Exception $e) {
            return false;
        }
     
        return $result;  
    }

    /**
     * Install all extensions packages
     *   
     * @param Closure|null $onProgress
     * @param Closure|null $onProgressError
     * @return boolean
     */
    public function installExtensions($onProgress = null, $onProgressError = null)
    {      
        System::setTimeLimit(0);
        
        try {
            // Install extensions      
            $extensionManager = Arikaim::packages()->create('extension');
            $result = $extensionManager->installAllPackages($onProgress,$onProgressError);
        } catch (Exception $e) {
            return false;
        }

        return $result;
    }

    /**
     * Create storage folders
     *
     * @return boolean
     */
    public function initStorage(): bool
    {   
        if (Arikaim::storage()->has('bin') == false) {          
            Arikaim::storage()->createDir('bin');
        } 

        if (Arikaim::storage()->has('public') == false) {          
            Arikaim::storage()->createDir('public');
        } 
        // delete symlink
        $linkPath = ROOT_PATH . BASE_PATH . DIRECTORY_SEPARATOR . 'public';
        $linkTarget = Arikaim::storage()->getFullPath('public') . DIRECTORY_SEPARATOR;
      
        if (@\is_link($linkPath) == false) {
            // create symlink 
            @\symlink($linkTarget,$linkPath); 
        }
      
        return true;     
    }

    /**
     * Register code events
     *
     * @return void
     */
    private function registerCoreEvents(): void
    {
        Arikaim::event()->registerEvent('core.extension.update','After update extension.');
        Arikaim::event()->registerEvent('core.extension.download','After download extension.');
        // Routes
        Arikaim::event()->registerEvent('core.route.disable','After disable route.');
        // UI Library
        Arikaim::event()->registerEvent('core.library.download','After download UI Library.');
        // System       
        Arikaim::event()->registerEvent('core.update','After update.'); 
    } 

    /**
     * Create default control panel user
     *
     * @return boolean
     */
    private function createDefaultAdminUser(): bool
    {
        $user = Model::Users()->getControlPanelUser();
        if ($user == false) {
            $user = Model::Users()->createUser('admin','admin');  
            if (empty($user->id) == true) {
                $error = Arikaim::errors()->getError('CONTROL_PANEL_USER_ERROR','Error create control panel user.');
                return false;
            }    
        }
    
        $result = Model::PermissionRelations()->setUserPermission(
            AccessInterface::CONTROL_PANEL,
            AccessInterface::FULL,
            $user->id
        );

        return (\is_object($result) == true) ? true : $result;
    }

    /**
     * Set default options
     *
     * @return void
     */
    private function initDefaultOptions(): void
    {
        // add date formats options
        Arikaim::options()->createOption('date.format',null,true);
        Arikaim::options()->createOption('time.format',null,true);
        Arikaim::options()->createOption('number.format',null,true);
        Arikaim::options()->createOption('time.zone',DateTime::getTimeZoneName(),true);        
        // mailer
        Arikaim::options()->createOption('mailer.driver',null,true);
        Arikaim::options()->createOption('mailer.email.compillers',[],true);
        Arikaim::options()->createOption('mailer.log',false,true);
        Arikaim::options()->createOption('mailer.log.error',false,true);
        Arikaim::options()->createOption('mailer.from.email','',true);
        Arikaim::options()->createOption('mailer.from.name','',true);
        // logger
        Arikaim::options()->createOption('logger',true,true);     
        Arikaim::options()->createOption('logger.handler','db',true);
        // session
        Arikaim::options()->createOption('session.recreation.interval',0,false);
        // library params
        Arikaim::options()->createOption('library.params',[],true);
        // language
        Arikaim::options()->createOption('current.language','en',true);        
        Arikaim::options()->createOption('default.language','en',true); 
        // page
        Arikaim::options()->createOption('current.page','',true); 
        Arikaim::options()->createOption('current.path','',true);      
    }

    /**
     * Install drivers
     *
     * @return bool
     */
    public function installDrivers(): bool
    {
        // cache
        return Arikaim::driver()->install(
            'filesystem',
            'Doctrine\\Common\\Cache\\FilesystemCache',
            'cache',
            'Filesystem cache',
            'Filesystem cache driver',
            '1.8.0',
            []
        );
    }

    /**
     * Create core db tables
     *
     * @param Closure|null $onProgress
     * @param Closure|null $onError
     * @param bool $stopOnError
     * @return string|false
     */
    private function createDbTables($onProgress = null, $onError = null, $stopOnError = true)
    {                        
        $classes = $this->getSystemSchemaClasses();
        $result = true;
        try {
            foreach ($classes as $class) {     
                $installed = Schema::install($class);                  
                if ($installed === false) {                                            
                    $this->callback($onError,$class);
                    if ($stopOnError == true) {
                        return false;
                    }
                    $result = false;       
                } else {
                    $this->callback($onProgress,$class);   
                }
            }      
        } catch (Exception $e) {
            $this->callback($onError,$e->getMessage());
            if ($stopOnError == true) {
                return false;
            }
            $result = false;    
        }
      
        return $result;
    }

    /**
     * Set system tables rows format to dynamic
     *
     * @return bool
     */
    public function systemTablesRowFormat(): bool
    {
        $classes = $this->getSystemSchemaClasses();
       
        foreach ($classes as $class) { 
            $tableName = Schema::getTable($class);
            if ($tableName !== true) {
                $format = Arikaim::db()->getRowFormat($tableName);
                if (\strtolower($format) != 'dynamic') {
                    Schema::setRowFormat($tableName);
                }            
            }
        }
        
        return true;
    }

    /**
     * Check if system is installed.
     *
     * @return boolean
     */
    public static function isInstalled(): bool 
    {
        $errors = 0;      
        try {
            // check db
            $errors += Arikaim::db()->has(Arikaim::config()->getByPath('db/database')) ? 0 : 1;
            if ($errors > 0) {
                return false;
            }
            // check db tables
            $tables = Self::getSystemDbTableNames();
            foreach ($tables as $tableName) {
                $errors += Schema::hasTable($tableName) ? 0 : 1;
            }
                    
            $result = Model::Users()->hasControlPanelUser();                          
            if ($result == false) {
                $errors++;
            }          

        } catch(Exception $e) {
            $errors++;
        }

        return ($errors == 0);   
    }

    /**
     * Verify system requirements
     * status   1 - ok, 2 - warning, 0 - error
     * 
     * @return array
     */
    public static function checkSystemRequirements()
    {
        $info['items'] = [];
        $info['errors']['messages'] = '';
        $errors = [];

        // php 5.6 or above
        $phpVersion = System::getPhpVersion();
        $item['message'] = 'PHP ' . $phpVersion;
        $item['status'] = 0; // error   
        if (\version_compare($phpVersion,'7.1','>=') == true) {               
            $item['status'] = 1; // ok                    
        } else {
            \array_push($errors,Arikaim::errors()->getError('PHP_VERSION_ERROR'));
        }
        \array_push($info['items'],$item);

        // PDO extension
        $item['message'] = 'PDO php extension';     
        $item['status'] = (System::hasPhpExtension('PDO') == true) ? 1 : 0;
        \array_push($info['items'],$item);

        // PDO driver
        $pdoDriver = Arikaim::config()->getByPath('db/driver');
       
        $item['message'] = $pdoDriver . 'PDO driver';
        $item['status'] = 0; // error
        if (System::hasPdoDriver($pdoDriver) == true) {
            $item['status'] = 1; // ok
        } else {
            \array_push($errors,Arikaim::errors()->getError('PDO_ERROR'));         
        }
        \array_push($info['items'],$item);

        // curl extension
        $item['message'] = 'Curl PHP extension';
        $item['status'] = (System::hasPhpExtension('curl') == true) ? 1 : 2;
           
        \array_push($info['items'],$item);

        // zip extension
        $item['message'] = 'Zip PHP extension';    
        $item['status'] = (System::hasPhpExtension('zip') == true) ? 1 : 2;

        \array_push($info['items'],$item);
        
        // GD extension 
        $item['message'] = 'GD PHP extension';      
        $item['status'] = (System::hasPhpExtension('gd') == true) ? 1 : 2;
          
        \array_push($info['items'],$item);

        // fileinfo php extension
        $item['message'] = 'fileinfo PHP extension';      
        $item['status'] = (System::hasPhpExtension('fileinfo') == true) ? 1 : 2;
          
        \array_push($info['items'],$item);

        $info['errors'] = $errors;
        
        return $info;
    }  

    /**
     * Return core migration classes
     *
     * @return array
     */
    private function getSystemSchemaClasses()
    {
        return [
            'RoutesSchema',
            'UsersSchema',
            'PermissionsSchema',
            'PermissionRelationsSchema',
            'UserGroupsSchema',
            'UserGroupMembersSchema',
            'EventsSchema',
            'EventSubscribersSchema',
            'ExtensionsSchema',
            'ModulesSchema',
            'JobsSchema',
            'LanguageSchema',
            'OptionsSchema',
            'PermissionsSchema',
            'AccessTokensSchema',
            'DriversSchema',
            'LogsSchema'
        ];
    }

    /**
     * Get core db table names
     *
     * @return array
     */
    private static function getSystemDbTableNames()
    {
        return [
            'options',         
            'extensions',
            'modules',
            'permissions',
            'permission_relations',
            'users',
            'user_groups',
            'user_group_members',
            'routes',
            'event_subscribers',
            'events',
            'language',
            'jobs',
            'access_tokens',
            'drivers'
        ];
    } 
}
