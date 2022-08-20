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

use Twig\TwigFunction;
use Twig\TwigTest;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

use Arikaim\Core\View\Html\Page;
use Arikaim\Core\System\System;
use Arikaim\Core\App\ArikaimStore;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Path;

/**
 *  Template engine control panel functions, filters and tests.
 */
class AdminTwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * Rempate engine global variables
     *
     * @return array
     */
    public function getGlobals(): array 
    {
        return [
            'system_template_name'  => Page::SYSTEM_TEMPLATE_NAME
        ];
    }

    /**
     * Template engine functions
     *
     * @return array
     */
    public function getFunctions() 
    {
        return [
            new TwigFunction('getSystemRequirements',['Arikaim\\Core\\App\\Install','checkSystemRequirements']),                      
            new TwigFunction('package',[$this,'createPackageManager']),       
            new TwigFunction('system',[$this,'system']),           
            new TwigFunction('hasModule',[$this,'hasModule']),
            new TwigFunction('showSql',['Arikaim\\Core\\Db\\Model','getSql']),
            new TwigFunction('hasTable',['Arikaim\\Core\\Db\\Schema','hasTable']),
            new TwigFunction('relationsMap',[$this,'getRelationsMap']),
            new TwigFunction('arikaimStore',[$this,'arikaimStore']),
            new TwigFunction('getConfigOption',[$this,'getConfigOption']),   
            new TwigFunction('loadConfig',[$this,'loadJosnConfigFile']),  
            new TwigFunction('sessionInfo',['Arikaim\\Core\\Http\\Session','getParams']),  
            // macros
            new TwigFunction('macro',['Arikaim\\Core\\Utils\\Path','getMacroPath']),         
            new TwigFunction('systemMacro',[$this,'getSystemMacroPath'])
        ];          
    }

    /**
     * Get system macro path
     *
     * @param string $macroName
     * @return string
     */
    public function getSystemMacroPath(string $macroName): string
    {
        return (string)Path::getMacroPath($macroName,'system');
    }

    /**
     * Template engine filters
     *
     * @return array
     */
    public function getFilters() 
    {      
        return [];
    }

    /**
     * Template engine tests
     *
     * @return array
     */
    public function getTests() 
    {
        return [
            new TwigTest('versionCompare',['Arikaim\\Core\\View\\Template\\Tests','versionCompare'])
        ];
    }

    /**
     * Get relatins type map (morph map)
     *
     * @return array|null
     */
    public function getRelationsMap(): ?array
    {
        global $container;

        return $container->get('db')->getRelationsMap();
    }

    /**
     * Template engine tags
     *
     * @return array
     */
    public function getTokenParsers()
    {
        return [];
    }   

    /**
     * Create arikaim store instance
     *
     * @return ArikaimStore|null
     */
    public function arikaimStore()
    {
        global $container;

        return ($container->get('access')->hasControlPanelAccess() == false) ? null : new ArikaimStore();         
    }

    /**
     * Get install config data
     *
     * @param string $key
     * @param mixed $default
     * @return mixed|null|false
     */
    public function getConfigOption(string $key, $default = null)
    {
        global $container;

        if ($container->get('config')->hasReadAccess($key) == false) {
            // access denied 
            return false;
        }
        $container->get('config')->reloadConfig();

        return $container->get('config')->getByPath($key,$default);         
    }

    /**
     * Load json config file
     *
     * @param string $fileName
     * @param string|null $packageName
     * @param string|null $packageType
     * @return array|null
     */
    public function loadJosnConfigFile(string $fileName, ?string $packageName = null, ?string $packageType = null)
    {
        global $container;

        if ($container->get('access')->hasControlPanelAccess() == false) {
            return null;
        }
        $fileName = Path::CONFIG_PATH . $fileName;
        if (empty($packageName) == false) { 
            if ($packageType == 'extension') {
                $fileName = Path::getExtensionConfigPath($packageName) . $fileName;
            }  
            if ($packageType == 'module') {
                $fileName = Path::getModuleConfigPath($packageName) . $fileName; 
            }
        } 

        $data = File::readJsonFile($fileName);

        return ($data === false) ? null : $data;
    }

    /**
     * Create package manager
     *
     * @param string $packageType
     * @return PackageManagerInterface|false
     */
    public function createPackageManager($packageType)
    {
        global $container;

        // Control Panel only
        return ($container->get('access')->hasControlPanelAccess() == false) ? false : $container->get('packages')->create($packageType);        
    }

    /**
     * Return true if module exists
     *
     * @param string $name
     * @return boolean
     */
    public function hasModule(string $name): bool
    {
        global $container;

        return $container->get('modules')->hasModule($name);              
    }

    /**
     * Get system info ( control panel access only )
     *
     * @return System
     */
    public function system()
    { 
        global $container;

        return ($container->get('access')->hasControlPanelAccess() == true) ? new System() : null;
    }
}
