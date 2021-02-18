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

use ParsedownExtra;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\TwigTest;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Psr\Container\ContainerInterface;

use Arikaim\Core\View\Html\Page;
use Arikaim\Core\Utils\Factory;
use Arikaim\Core\Db\Model;
use Arikaim\Core\Access\Csrf;
use Arikaim\Core\System\System;
use Arikaim\Core\Packages\Composer;
use Arikaim\Core\App\Install;
use Arikaim\Core\Http\Url;
use Arikaim\Core\Http\Session;
use Arikaim\Core\App\ArikaimStore;
use Arikaim\Core\Routes\Route;
use Arikaim\Core\Db\Schema;
use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\View\Template\Tags\ComponentTagParser;
use Arikaim\Core\View\Template\Tags\MdTagParser;

/**
 *  Template engine functions, filters and tests.
 */
class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * Model classes requires control panel access 
     *
     * @var array
     */
    protected $protectedModels = [
        'PermissionRelations',
        'Permissions',
        'Routes',
        'Modules',
        'Events',
        'Drivers',
        'Extensions',
        'Jobs',
        'EventSubscribers'
    ];

    /**
     * Protected services requires control panel access  
     *
     * @var array
     */
    protected $protectedServices = [
        'config',       
        'packages'
    ];

    /**
     * Protected services requires logged user
     *
     * @var array
     */
    protected $userProtectedServices = [      
        'storage'      
    ];

    /**
     * Container
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Base path
     *
     * @var string
     */
    protected $basePath;

    /**
     * View path
     *
     * @var string
     */
    protected $viewpath;

    /**
     * Markdown parser
     *
     * @var object
     */
    protected $markdownParser;

    /**
     * Constructor
     *
     * @param string $basePath
     * @param string $viewpath
     * @param ContainerInterface $container
     */
    public function __construct($basePath, $viewPath, ContainerInterface $container)
    {       
        $this->basePath = $basePath;
        $this->viewPath = $viewPath;
        $this->container = $container;
    }

    /**
     * Get service container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Rempate engine global variables
     *
     * @return array
     */
    public function getGlobals() 
    {
        return [
            'system_template_name'  => Page::SYSTEM_TEMPLATE_NAME,
            'domain'                => (\defined('DOMAIN') == true) ? DOMAIN : null,
            'base_url'              => Url::BASE_URL,     
            'base_path'             => $this->basePath,        
            'DIRECTORY_SEPARATOR'   => DIRECTORY_SEPARATOR,        
            'ui_path'               => $this->basePath . $this->viewPath,   
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
            // html components
            new TwigFunction('component',[$this,'loadComponent'],[
                'needs_environment' => false,
                'needs_context' => true,
                'is_safe' => ['html']
            ]),
            new TwigFunction('componentProperties',[$this,'getProperties']),
            new TwigFunction('componentOptions',[$this,'getComponentOptions']),         
            new TwigFunction('currentTemplate',[$this,'getCurrentTemplate']),
            // page              
            new TwigFunction('url',[$this,'getUrl']),        
            new TwigFunction('currentUrl',[$this,'getCurrentUrl']),
            // template          
            new TwigFunction('getPrimaryTemplate',[$this,'getPrimaryTemplate']),
            new TwigFunction('loadLibraryFile',[$this,'loadLibraryFile']),    
            new TwigFunction('loadComponentCssFile',[$this,'loadComponentCssFile']),  
            new TwigFunction('getLanguage',[$this,'getLanguage']),
            new TwigFunction('sessionInfo',['Arikaim\\Core\\Http\\Session','getParams']),         
            // macros
            new TwigFunction('macro',['Arikaim\\Core\\Utils\\Path','getMacroPath']),         
            new TwigFunction('systemMacro',[$this,'getSystemMacroPath']),
            // mobile
            new TwigFunction('isMobile',['Arikaim\\Core\\Utils\\Mobile','mobile']),
            // paginator
            new TwigFunction('paginate',['Arikaim\\Core\\Paginator\\SessionPaginator','create']),
            new TwigFunction('paginatorUrl',[$this,'getPaginatorUrl']),
            new TwigFunction('clearPaginator',['Arikaim\\Core\\Paginator\\SessionPaginator','clearPaginator']),            
            new TwigFunction('getPaginator',['Arikaim\\Core\\Paginator\\SessionPaginator','getPaginator']),
            new TwigFunction('getRowsPerPage',['Arikaim\\Core\\Paginator\\SessionPaginator','getRowsPerPage']),
            new TwigFunction('getViewType',['Arikaim\\Core\\Paginator\\SessionPaginator','getViewType']),
            // database            
            new TwigFunction('applySearch',['Arikaim\\Core\\Db\\Search','apply']),
            new TwigFunction('createSearch',['Arikaim\\Core\\Db\\Search','setSearchCondition']),
            new TwigFunction('searchValue',['Arikaim\\Core\\Db\\Search','getSearchValue']),
            new TwigFunction('getSearch',['Arikaim\\Core\\Db\\Search','getSearch']),
            new TwigFunction('getOrderBy',['Arikaim\\Core\\Db\\OrderBy','getOrderBy']),
            new TwigFunction('applyOrderBy',['Arikaim\\Core\\Db\\OrderBy','apply']),
            new TwigFunction('createModel',[$this,'createModel']),
            new TwigFunction('showSql',['Arikaim\\Core\\Db\\Model','getSql']),
            new TwigFunction('relationsMap',[$this,'getRelationsMap']),
            // other
            new TwigFunction('getConstant',['Arikaim\\Core\\Db\\Model','getConstant']),
            new TwigFunction('hasExtension',[$this,'hasExtension']),
            new TwigFunction('hasModule',[$this,'hasModule']),
            new TwigFunction('getFileType',[$this,'getFileType']),         
            new TwigFunction('system',[$this,'system']),  
            new TwigFunction('getSystemRequirements',[$this,'getSystemRequirements']),                      
            new TwigFunction('package',[$this,'createPackageManager']),       
            new TwigFunction('service',[$this,'getService']),     
            new TwigFunction('installConfig',[$this,'getInstallConfig']),   
            new TwigFunction('loadConfig',[$this,'loadJosnConfigFile']),     
            new TwigFunction('access',[$this,'getAccess']),   
            new TwigFunction('getCurrentLanguage',[$this,'getCurrentLanguage']),
            new TwigFunction('getVersion',[$this,'getVersion']),
            new TwigFunction('getLastVersion',[$this,'getLastVersion']),          
            new TwigFunction('modules',[$this,'getModulesService']),
            new TwigFunction('create',[$this,'create']),  

            // session vars
            new TwigFunction('getSessionVar',[$this,'getSessionVar']),
            new TwigFunction('setSessionVar',[$this,'setSessionVar']),

            new TwigFunction('getOption',[$this,'getOption']),
            new TwigFunction('getOptions',[$this,'getOptions']),
            new TwigFunction('csrfToken',[$this,'csrfToken']),                
            new TwigFunction('fetch',[$this,'fetch']),
            new TwigFunction('extractArray',[$this,'extractArray'],['needs_context' => true]),
            new TwigFunction('arikaimStore',[$this,'arikaimStore']),
            // url
            new TwigFunction('getPageUrl',[$this,'getPageUrl']),         
            new TwigFunction('getTemplateUrl',['Arikaim\\Core\\Http\\Url','getTemplateUrl']),     
            new TwigFunction('getLibraryUrl',['Arikaim\\Core\\Http\\Url','getLibraryFileUrl']),  
            new TwigFunction('getExtensionViewUrl',['Arikaim\\Core\\Http\\Url','getExtensionViewUrl']),     
            // files
            new TwigFunction('getDirectoryFiles',[$this,'getDirectoryFiles']),
            new TwigFunction('isImage',['Arikaim\\Core\\Utils\\File','isImageMimeType']),
            // date and time
            new TwigFunction('getTimeZonesList',['Arikaim\\Core\\Utils\\DateTime','getTimeZonesList']),
            new TwigFunction('timeInterval',['Arikaim\\Core\\Utils\\TimeInterval','getInterval']),          
            new TwigFunction('currentYear',[$this,'currentYear']),
            new TwigFunction('today',['Arikaim\\Core\\Utils\\DateTime','getTimestamp']),
            // unique Id
            new TwigFunction('createUuid',['Arikaim\\Core\\Utils\\Uuid','create']),
            new TwigFunction('createToken',['Arikaim\\Core\\Utils\\Utils','createToken']),
            // collections
            new TwigFunction('createCollection',['Arikaim\\Core\\Collection\\Collection','create']),
            new TwigFunction('createProperties',['Arikaim\\Core\\Collection\\PropertiesFactory','createFromArray']),
        ];       
    }

    /**
     * Get relatins type map (morph map)
     *
     * @return array|null
     */
    public function getRelationsMap(): ?array
    {
        return $this->container->get('db')->getRelationsMap();
    }

    /**
     * Return url link with current language code
     *
     * @param string $path
     * @param boolean $full
     * @param string|null $language
     * @return string
     */
    public function getUrl($path = '', $full = false, $language = null)
    {
        return Page::getUrl($path,$full,$language);
    }

    /**
     * Return url link with current language code
     *
     * @param boolean $full
     * @return string
    */
    public function getCurrentUrl($full = true)
    {
        $curentPath = $this->container->get('options')->get('current.path','');

        return ($full == true) ? DOMAIN . $curentPath : $curentPath;           
    }

    /**
     * Load component
     *
     * @param string $name
     * @param array|null $params
     * @return string
     */
    public function loadComponent(&$context, $name, $params = [])
    {        
        $language = $context['current_language'] ?? null;
    
        return $this->container->get('page')->createHtmlComponent($name,$params,$language,true)->load();     
    }

    /**
     * Get system macro path
     *
     * @param string $macroName
     * @return string
     */
    public function getSystemMacroPath($macroName)
    {
        return Path::getMacroPath($macroName,'system');
    }

    /**
     * Get current page language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->container->get('page')->getLanguage();
    }

    /**
     * Load component css file
     *
     * @param string $componentName
     * @return string
     */
    public function loadComponentCssFile($componentName)
    {
        $file = $this->container->get('page')->getComponentFiles($componentName,'css');
        $content = (empty($file[0]) == false) ? File::read($file[0]['full_path'] . $file[0]['file_name']) : '';
        
        return $content ?? '';
    }

    /**
     * Load Ui library file
     *
     * @param string $library
     * @param string $fileName
     * @return string
     */
    public function loadLibraryFile($library, $fileName)
    {      
        $file = $this->viewPath . 'library' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . $fileName;
        $content = File::read($file);

        return $content ?? '';
    }

    /**
     * Get current template
     *
     * @return string
     */
    public function getPrimaryTemplate()
    {
        return $this->container->get('page')->getPrimaryTemplate();
    }

    /**
     * Get current template name
     *
     * @return string
     */
    public function getCurrentTemplate()
    {
        return $this->container->get('page')->getCurrentTemplate();
    }

    /**
     * Get comonent options ( control panel access is required)
     *
     * @param string $name
     * @return array|null
     */
    public function getComponentOptions($name)
    {
        return ($this->container->get('access')->hasControlPanelAccess() == true) ? $this->container->get('page')->createHtmlComponent($name)->getOptions() : null;
    }

    /**
     * Get component properties
     *
     * @param string $name
     * @param string|null $language
     * @return array
     */
    public function getProperties($name, $language = null)
    {
        return $this->container->get('page')->createHtmlComponent($name,[],$language)->getProperties();
    }

    /**
     * Get modules service
     *
     * @return object|null
     */
    public function getModulesService()
    {
        return ($this->container->has('modules') == true) ? $this->container->get('modules') : null;
    }

    /**
     * Get session var
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getSessionVar($name, $default = null)
    {
        return Session::get('vars.' . $name,$default);
    }

    /**
     * Set session var
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function setSessionVar($name, $value)
    {
        Session::set('vars.' . $name,$value);
    }

    /**
     * Get page url
     *
     * @param string $routeName
     * @param string|null $extension
     * @param array $params
     * @param boolean $relative
     * @param string|null $language
     * @return string|false
     */
    public function getPageUrl(
        string $routeName, 
        ?string $extension, 
        array $params = [], 
        bool $relative = false, 
        ?string $language = null
    )
    {
        $route = $this->container->get('routes')->getRoutes([
            'name'           => $routeName,
            'extension_name' => $extension
        ]);

        if (isset($route[0]) == false) {
            return false;
        }
        $urlPath = Route::getRouteUrl($route[0]['pattern'],$params);
        
        return Page::getUrl($urlPath,!$relative,$language);
    }

    /**
     * Get paginator url
     *
     * @param string $pageUrl
     * @param integer $page
     * @param boolean $full
     * @param boolean $withLanguagePath
     * @return string
     */
    public function getPaginatorUrl($pageUrl, $page, $full = true, $withLanguagePath = false)
    {
        $path = (empty($pageUrl) == true) ? $page : $pageUrl . '/' . $page;
        
        return Page::getUrl($path,$full,$withLanguagePath);
    }

    /**
     * Template engine filters
     *
     * @return array
     */
    public function getFilters() 
    {      
       return [
            // Html
            new TwigFilter('attr',['Arikaim\\Core\\View\\Template\\Filters','attr'],['is_safe' => ['html']]),           
            new TwigFilter('getAttr',['Arikaim\\Core\\Utils\\Html','getAttributes'],['is_safe' => ['html']]),
            new TwigFilter('decode',['Arikaim\\Core\\Utils\\Html','specialcharsDecode'],['is_safe' => ['html']]),
            // other
            new TwigFilter('ifthen',['Arikaim\\Core\\View\\Template\\Filters','is']),
            new TwigFilter('dump',['Arikaim\\Core\\View\\Template\\Filters','dump']),
            new TwigFilter('string',['Arikaim\\Core\\View\\Template\\Filters','convertToString']),
            new TwigFilter('emptyLabel',['Arikaim\\Core\\View\\Template\\Filters','emptyLabel']),
            new TwigFilter('sliceLabel',['Arikaim\\Core\\View\\Template\\Filters','sliceLabel']),
            new TwigFilter('baseClass',['Arikaim\\Core\\Utils\\Utils','getBaseClassName']),                        
            // text
            new TwigFilter('renderText',['Arikaim\\Core\\Utils\\Text','render']),
            new TwigFilter('sliceText',['Arikaim\\Core\\Utils\\Text','sliceText']),
            new TwigFilter('titleCase',['Arikaim\\Core\\Utils\\Text','convertToTitleCase']),
            new TwigFilter('md',[$this,'parseMarkdown']),

            new TwigFilter('jsonDecode',['Arikaim\\Core\\Utils\\Utils','jsonDecode']),
            // date time
            new TwigFilter('dateFormat',['Arikaim\\Core\\Utils\\DateTime','dateFormat']),
            new TwigFilter('dateTimeFormat',['Arikaim\\Core\\Utils\\DateTime','dateTimeFormat']),
            new TwigFilter('timeFormat',['Arikaim\\Core\\Utils\\DateTime','timeFormat']),
            new TwigFilter('convertDate',['Arikaim\\Core\\Utils\\DateTime','convert']),
            // numbers
            new TwigFilter('numberFormat',['Arikaim\\Core\\Utils\\Number','format']),
            new TwigFilter('toNumber',['Arikaim\\Core\\Utils\\Number','toNumber']),
            // text
            new TwigFilter('mask',['Arikaim\\Core\\Utils\\Text','mask']),
            new TwigFilter('pad',['Arikaim\\Core\\Utils\\Text','pad']),
            new TwigFilter('padLeft',['Arikaim\\Core\\Utils\\Text','padLeft']),
            new TwigFilter('padRight',['Arikaim\\Core\\Utils\\Text','padRight']),
            // files
            new TwigFilter('fileSize',['Arikaim\\Core\\Utils\\File','getSizeText']),
            new TwigFilter('baseName',['Arikaim\\Core\\Utils\\File','baseName']),
            new TwigFilter('relativePath',['Arikaim\\Core\\Utils\\Path','getRelativePath'])
        ];
    }

    /**
     * Template engine tests
     *
     * @return array
     */
    public function getTests() 
    {
        return [
            new TwigTest('haveSubItems',['Arikaim\\Core\\Utils\\Arrays','haveSubItems']),
            new TwigTest('object',['Arikaim\\Core\\View\\Template\\Tests','isObject']),
            new TwigTest('string',['Arikaim\\Core\\View\\Template\\Tests','isString']),
            new TwigTest('access',[$this,'hasAccess']),
            new TwigTest('versionCompare',['Arikaim\\Core\\View\\Template\\Tests','versionCompare'])
        ];
    }

    /**
     * Template engine tags
     *
     * @return array
     */
    public function getTokenParsers()
    {
        return [
            new ComponentTagParser(Self::class),
            new MdTagParser(Self::class)
        ];
    }   

    /**
     * Get accesss
     *
     * @return object
     */
    public function getAccess()
    {
        return $this->container->get('access');
    } 

    /**
     * Create arikaim store instance
     *
     * @return ArikaimStore
     */
    public function arikaimStore()
    {
        return new ArikaimStore();
    }

    /**
     * Get install config data
     *
     * @return array|false
     */
    public function getInstallConfig()
    {
        $daibled = $this->container->get('config')->getByPath('settings/disableInstallPage');
       
        return ($daibled == true) ? false : $this->container->get('config');         
    }

    /**
     * Get system requirements
     *
     * @return array
     */
    public function getSystemRequirements()
    {
        return Install::checkSystemRequirements();
    }

    /**
     * Get composer package current version
     *
     * @param string|null $packageName
     * @return string|false
     */
    public function getVersion($packageName = null)
    {
        $packageName = (empty($packageName) == true) ? ARIKAIM_PACKAGE_NAME : $packageName;       

        return Composer::getInstalledPackageVersion($packageName);     
    }

    /**
     * Get composer package last version
     *
     * @param  string|null $packageName
     * @return string|false
     */
    public function getLastVersion($packageName = null)
    {
        $packageName = (empty($packageName) == true) ? ARIKAIM_PACKAGE_NAME : $packageName;
        $tokens = \explode('/',$packageName);
        
        return Composer::getLastVersion($tokens[0],$tokens[1]);        
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
        if ($this->container->get('access')->hasControlPanelAccess() == false) {
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
     * Get service from container
     *
     * @param string $name
     * @return mixed
     */
    public function getService($name)
    {
        if (\in_array($name,$this->protectedServices) == true) {
            return ($this->container->get('access')->hasControlPanelAccess() == true) ? $this->container->get($name) : false;           
        }

        if (\in_array($name,$this->userProtectedServices) == true) {
            return ($this->container->get('access')->isLogged() == true) ? $this->container->get($name) : false;           
        }

        if ($this->container->has($name) == false) {
            // try from service container
            return $this->container->get('service')->get($name);
        }

        return $this->container->get($name);
    }

    /**
     * Get directory contents
     *
     * @param string $path
     * @param boolean $recursive
     * @param string $fileSystemName
     * @return array|false
     */
    public function getDirectoryFiles($path, $recursive = false, $fileSystemName = 'storage')
    {
        // Control Panel only
        if ($this->container->get('access')->isLogged() == false) {
            return false;
        }

        return$this->container->get('storage')->listContents($path,$recursive,$fileSystemName);
    }

    /**
     * Create package manager
     *
     * @param string $packageType
     * @return PackageManagerInterface|false
     */
    public function createPackageManager($packageType)
    {
        // Control Panel only
        if ($this->container->get('access')->hasControlPanelAccess() == false) {
            return false;
        }
        
        return $this->container->get('packages')->create($packageType);
    }

    /**
     * Create model 
     *
     * @param string $modelClass
     * @param string|null $extension
     * @param boolean $showError
     * @param boolean $checkTable
     * @return Model|false
     */
    public function createModel($modelClass, $extension = null, $showError = false, $checkTable = false)
    {
        if (\in_array($modelClass,$this->protectedModels) == true) {
            return ($this->container->get('access')->hasControlPanelAccess() == true) ? Model::create($modelClass,$extension) : false;           
        }
        $model = Model::create($modelClass,$extension,null,$showError);

        if (\is_object($model) == true && $checkTable == true) {
            // check if table exist
            return (Schema::hasTable($model) == false) ? false : $model;
        }

        return $model;
    }

    /**
     * Return true if extension exists
     *
     * @param string $extension
     * @return boolean
     */
    public function hasExtension($extension)
    {
        $model = Model::Extensions()->where('name','=',$extension)->first();  

        return \is_object($model);          
    }

    /**
     * Return true if module exists
     *
     * @param string $name
     * @return boolean
     */
    public function hasModule($name)
    {
        return $this->container->get('modules')->hasModule($name);              
    }


    /**
     * Return file type
     *
     * @param string $fileName
     * @return string
     */
    public function getFileType($fileName) 
    {
        return \pathinfo($fileName,PATHINFO_EXTENSION);
    }

    /**
     * Return current year
     *
     * @return string
     */
    public function currentYear()
    {
        return \date('Y');
    }
    
    /**
     * Return current language
     *
     * @return array|null
     */
    public function getCurrentLanguage() 
    {
        $language = $this->container->get('page')->getLanguage();
        $model = Model::Language()->where('code','=',$language)->first();

        return (\is_object($model) == true) ? $model->toArray() : null;
    }

    /**
     * Get option
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getOption($name, $default = null) 
    {
        return $this->container->get('options')->get($name,$default);          
    }

    /**
     * Get options
     *
     * @param string $searchKey
     * @param bool $compactKeys
     * @return array
     */
    public function getOptions($searchKey, $compactKeys = false)
    {
        return $this->container->get('options')->searchOptions($searchKey,$compactKeys);       
    }

    /**
     * Create obj
     *
     * @param string $class
     * @param string $extension
     * @return object|null
     */
    public function create(string $class, string $extension)
    {
        $class = Factory::getExtensionClassName($extension,$class);
        
        return Factory::createInstance($class);            
    }
    
    /**
     * Return csrf token field html code
     *
     * @return string
     */
    public function csrfToken()
    {
        $token = Csrf::getToken(true);    

        return "<input type='hidden' name='csrf_token' value='" . $token . "'>";
    }

    /**
     * Fetch url
     *
     * @param string $url
     * @return Response|null
     */
    public function fetch($url)
    {
        $response = $this->container->get('http')->get($url);
        
        return (\is_object($response) == true) ? $response->getBody() : null;
    }

    /**
     * Exctract array as local variables in template
     *
     * @param array $context
     * @param array $data
     * @return void
     */
    public function extractArray(&$context, $data) 
    {
        if (\is_array($data) == false) {
            return;
        }
        foreach($data as $key => $value) {
            $context[$key] = $value;
        }
    }  

    /**
     * Get system info ( control panel access only )
     *
     * @return System
     */
    public function system()
    { 
        return ($this->container->get('access')->hasControlPanelAccess() == true) ? new System() : null;
    }

     /**
     * Parse Markdown
     *
     * @param array $context
     * @param string $content
     * @return string
     */
    public function parseMarkdown($content, $context = [])
    {
        if (empty($this->markdownParser) == true) {
            $this->markdownParser = new ParsedownExtra();
        }

        return $this->markdownParser->text($content);
    }

    /**
     * Check access 
     *
     * @param string $name Permission name
     * @param string|array $type PermissionType (read,write,execute,delete)   
     * @param mixed $authId 
     * @return boolean
     */
    public function hasAccess($name, $type = null, $authId = null)
    {
        return $this->container->get('access')->hasAccess($name,$type,$authId);
    }
}
