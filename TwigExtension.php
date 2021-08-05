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

use Arikaim\Core\Arikaim;
use Arikaim\Core\View\Html\Page;
use Arikaim\Core\Utils\Factory;
use Arikaim\Core\Db\Model;
use Arikaim\Core\Http\Url;
use Arikaim\Core\Http\Session;
use Arikaim\Core\Routes\Route;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\View\Template\Tags\ComponentTagParser;
use Arikaim\Core\View\Template\Tags\MdTagParser;
use Arikaim\Core\View\Template\Tags\CacheTagParser;

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
     * Markdown parser
     *
     * @var object
     */
    protected $markdownParser;

    /**
     * Rempate engine global variables
     *
     * @return array
     */
    public function getGlobals(): array 
    {
        return [
            'system_template_name'  => Page::SYSTEM_TEMPLATE_NAME,
            'domain'                => (\defined('DOMAIN') == true) ? DOMAIN : null,
            'base_url'              => Url::BASE_URL,     
            'base_path'             => BASE_PATH,     
            'templates_path'        => Path::TEMPLATES_PATH,   
            'DIRECTORY_SEPARATOR'   => DIRECTORY_SEPARATOR,        
            'ui_path'               => BASE_PATH . Path::VIEW_PATH,   
        ];
    }

    /**
     * Template engine functions
     *
     * @return array
     */
    public function getFunctions() 
    {
        $functions = [
            // html components
            new TwigFunction('component',[$this,'loadComponent'],[
                'needs_environment' => false,
                'needs_context'     => false,
                'is_safe'           => ['html']
            ]),                    
            // page              
            new TwigFunction('url',[Page::class,'getUrl']),        
            new TwigFunction('currentUrl',[$this,'getCurrentUrl']),
            // template           
            new TwigFunction('loadLibraryFile',[$this,'loadLibraryFile']),    
            new TwigFunction('getLanguage',[$this,'getLanguage']),
            new TwigFunction('sessionInfo',['Arikaim\\Core\\Http\\Session','getParams']),                   
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
            new TwigFunction('getFileType',[$this,'getFileType']),           
            new TwigFunction('service',[$this,'getService']),    
            new TwigFunction('content',['Arikaim\\Core\\Arikaim','content']),     
            new TwigFunction('access',[Arikaim::class,'access']),   
            new TwigFunction('getCurrentLanguage',[$this,'getCurrentLanguage']),                          
            new TwigFunction('create',[$this,'create']),  
            new TwigFunction('hasExtension',[$this,'hasExtension']),
            
            // session vars
            new TwigFunction('getSessionVar',[$this,'getSessionVar']),
            new TwigFunction('setSessionVar',[$this,'setSessionVar']),
            // 
            new TwigFunction('getOption',[$this,'getOption']),
            new TwigFunction('getOptions',[$this,'getOptions']),                 
            new TwigFunction('fetch',[$this,'fetch']),
            new TwigFunction('extractArray',[$this,'extractArray'],['needs_context' => true]),          
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
            new TwigFunction('currentYear',['Arikaim\\Core\\Utils\\DateTime','getCurrentYear']),
            new TwigFunction('today',['Arikaim\\Core\\Utils\\DateTime','getTimestamp']),
            // unique Id
            new TwigFunction('createUuid',['Arikaim\\Core\\Utils\\Uuid','create']),
            new TwigFunction('createToken',['Arikaim\\Core\\Utils\\Utils','createToken']),
            // collections
            new TwigFunction('createCollection',['Arikaim\\Core\\Collection\\Collection','create']),
            new TwigFunction('createProperties',['Arikaim\\Core\\Collection\\PropertiesFactory','createFromArray']),
        ];    
     
        return $functions;
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
     * Return true if extension exists
     *
     * @param string $extension
     * @return boolean
     */
    public function hasExtension(string $extension): bool
    {
        $model = Model::Extensions()->where('name','=',$extension)->first();  

        return \is_object($model);          
    }

    /**
     * Get cache
     *
     * @return CacheInterface
     */
    public function getCache()
    {
        return Arikaim::get('cache');
    } 

    /**
     * Get relatins type map (morph map)
     *
     * @return array|null
     */
    public function getRelationsMap(): ?array
    {
        return Arikaim::get('db')->getRelationsMap();
    }

    /**
     * Return url link with current language code
     *
     * @param boolean $full
     * @return string
    */
    public function getCurrentUrl(bool $full = true): string
    {
        $curentPath = BASE_PATH . $_SERVER['REQUEST_URI'];

        return ($full == true) ? DOMAIN . $curentPath : $curentPath;           
    }

    /**
     * Load component
     *
     * @param string $name
     * @param array|null $params
     * @param string|null $type
     * @return string
     */
    public function loadComponent(string $name, $params = [], ?string $type = null)
    {              
        $params = (\is_array($params) == false) ? [] : $params;
        $component = Arikaim::get('page')->renderHtmlComponent($name,$params,null,$type);
        
        return $component->getHtmlCode(); 
    }

    /**
     * Get current page language
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return Arikaim::get('page')->getLanguage();
    }

    /**
     * Load Ui library file
     *
     * @param string $library
     * @param string $fileName
     * @return string
     */
    public function loadLibraryFile(string $library, string $fileName): string
    {      
        $file = Path::VIEW_PATH . 'library' . DIRECTORY_SEPARATOR . $library . DIRECTORY_SEPARATOR . $fileName;
       
        return (\file_exists($file) == false) ? '' : \file_get_contents($file);
    }

    /**
     * Get session var
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getSessionVar(string $name, $default = null)
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
    public function setSessionVar(string $name, $value): void
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
        $route = Arikaim::get('routes')->getRoutes([
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
     * Template engine filters
     *
     * @return array
     */
    public function getFilters() 
    {      
       $filters = [
            // Html
            new TwigFilter('attr',['Arikaim\\Core\\View\\Template\\Filters','attr'],['is_safe' => ['html']]),           
            new TwigFilter('getAttr',['Arikaim\\Core\\Utils\\Html','getAttributes'],['is_safe' => ['html']]),
            new TwigFilter('decode',['Arikaim\\Core\\Utils\\Html','specialcharsDecode'],['is_safe' => ['html']]),
            new TwigFilter('createHtmlId',['Arikaim\\Core\\Utils\\Html','createId'],['is_safe' => ['html']]),
            // other
            new TwigFilter('ifthen',['Arikaim\\Core\\View\\Template\\Filters','is']),
            new TwigFilter('dump',['Arikaim\\Core\\View\\Template\\Filters','dump']),
            new TwigFilter('string',['Arikaim\\Core\\View\\Template\\Filters','convertToString']),
            new TwigFilter('emptyLabel',['Arikaim\\Core\\View\\Template\\Filters','emptyLabel']),
            new TwigFilter('sliceLabel',['Arikaim\\Core\\View\\Template\\Filters','sliceLabel']),
            new TwigFilter('baseClass',['Arikaim\\Core\\Utils\\Utils','getBaseClassName']),                        
            // text
            new TwigFilter('renderText',['Arikaim\\Core\\Utils\\Text','render']),
            new TwigFilter('renderArray',['Arikaim\\Core\\Utils\\Text','renderMultiple']),
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
       
        return $filters;
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
            new MdTagParser(Self::class),
            new CacheTagParser(Self::class)
        ];
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
            return (Arikaim::get('access')->hasControlPanelAccess() == true) ? Arikaim::get($name) : false;           
        }

        if (\in_array($name,$this->userProtectedServices) == true) {
            return (Arikaim::get('access')->isLogged() == true) ? Arikaim::get($name) : false;           
        }

        if (Arikaim::has($name) == false) {
            // try from service container
            return Arikaim::get('service')->get($name);
        }

        return Arikaim::get($name);
    }

    /**
     * Get directory contents
     *
     * @param string $path
     * @param boolean $recursive
     * @param string $fileSystemName
     * @return array|false
     */
    public function getDirectoryFiles(string $path, bool $recursive = false, string $fileSystemName = 'storage')
    {
        // Control Panel only
        if (Arikaim::get('access')->isLogged() == false) {
            return false;
        }

        return Arikaim::get('storage')->listContents($path,$recursive,$fileSystemName);
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
    public function createModel(?string $modelClass, ?string $extension = null, bool $showError = false)
    {
        if (\in_array($modelClass,$this->protectedModels) == true) {
            return (Arikaim::get('access')->hasControlPanelAccess() == true) ? Model::create($modelClass,$extension) : false;           
        }

        return Model::create($modelClass,$extension,null,$showError);
    }

    /**
     * Return file type
     *
     * @param string $fileName
     * @return string|null
     */
    public function getFileType(?string $fileName): ?string 
    {
        return (empty($fileName) == true) ? null : (string)\pathinfo($fileName,PATHINFO_EXTENSION);
    }

    /**
     * Return current language
     *
     * @return array|null
     */
    public function getCurrentLanguage() 
    {
        $language = Arikaim::get('page')->getLanguage();
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
        return Arikaim::get('options')->get($name,$default);          
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
        return Arikaim::get('options')->searchOptions($searchKey,$compactKeys);       
    }

    /**
     * Create obj
     *
     * @param string $class
     * @param string $extension
     * @return object|null
     */
    public function create(string $class, ?string $extension = null)
    {
        $class = Factory::getExtensionClassName($extension,$class);
        
        return Factory::createInstance($class);            
    }
    
    /**
     * Fetch url
     *
     * @param string $url
     * @return Response|null
     */
    public function fetch($url)
    {
        $response = Arikaim::get('http')->get($url);
        
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
}
