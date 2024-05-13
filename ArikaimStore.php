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

use Arikaim\Core\Utils\Curl;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\System\Config;

/**
 * Arikaim store
*/
class ArikaimStore 
{       
    const HOST                 = 'http://arikaim.com';
    const SIGNUP_URL           = Self::HOST . '/signup';  
    const LOGIN_API_URL        = Self::HOST . '/api/users/login';
    const RESET_PASSWORD_URL   = Self::HOST . '/login';

    /**
     * Data config file name
     *
     * @var string
     */
    protected $configFile;

    /**
     * Config
     *
     * @var Config
     */
    protected $config;

    /**
     * Constructor
     * 
     * @param string $configfileName
     */
    public function __construct(string $configfileName = 'arikaim-store.php')
    {         
        $this->configFile = Path::CONFIG_PATH . $configfileName;
      
        $this->config = new Config($configfileName,Path::CONFIG_PATH);
        if ($this->config->hasConfigFile($configfileName) == false) {
            $this->clear();
            $this->config->save();
        }

        $this->config->reloadConfig();
    }

    /**
     * Create obj
     *
     * @return Self
     */
    public static function create()
    {
        return new Self();
    }

    /**
     * Get config refernce
     *
     * @return Collection
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get package key
     *
     * @param string|null $repository
     * @return string|null
     */
    public function getPackageKey(?string $repository): ?string
    {
        if (empty($repository) == true) {
            return null;
        }

        $packages = $this->getPackages();
        foreach($packages as $package) {
            if ($package['repository'] == $repository) {
                return $package['key'] ?? null;
            }
        }

        return null;
    }

    /**
     * Return true if cust have account token
     *
     * @return boolean
     */
    public function isLogged(): bool
    {
        return (empty($this->config->getByPath('account/token',null)) == false);
    }

    /**
     * Get orders
     *
     * @return array
     */
    public function getProduct(): array
    {
        return $this->config->get('product',[]);
    }

    /**
     * Get packages
     *
     * @return array
     */
    public function getPackages(): array
    {
        return $this->config->get('packages',[]);
    }

    /**
     * Init data
     *
     * @return void
     */
    public function clear(): void
    {
        $this->config->withData([
            'account'  => [],
            'packages' => [],
            'product'  => []
        ]);
    }

    /**
     * Logout (deletes user token)
     *
     * @return boolean
     */
    public function logout(): bool
    { 
        return true;
    }

    /**
     * Convert config data to array
     *
     * @return array
     */
    protected function toArray(): array
    {
        return $this->config->toArray();
    }

    /**
     * Is curl installed
     *
     * @return boolean
     */
    public function hasCurl(): bool
    {
        return Curl::isInsatlled();
    }

    /**
     * Fetch packages list 
     *
     * @param string $type
     * @param string|null $page
     * @param string $search
     * @return mixed
     */
    public function fetchPackages(?string $type, ?string $page = '1', string $search = '')
    {
        $page = (empty($search) == true) ? $page : '/' . $page;
        $url = Self::HOST . '/api/store/product/list/' . $search . $page;
         
        return Curl::get($url);
    }

    /**
     * Fetch package details 
     *
     * @param string $uuid   
     * @return mixed
     */
    public function fetchPackageDetails(string $uuid)
    {
        $url = $this->getPackageDetailsUrl($uuid);
                
        return Curl::get($url);
    }

    /**
     * Gte package details requets url
     *
     * @param string $uuid
     * @return string
     */
    public function getPackageDetailsUrl(string $uuid): string
    {
        return Self::HOST . '/api/products/product/details/' . $uuid;
    }

    /**
     * Get package version url
     *
     * @param string $packageName
     * @return string
     */
    public function getPackageVersionUrl(string $packageName): string
    {
        return Self::PACKAGE_VERSION_URL . $packageName;        
    }    

    /**
     * Get signup url
     */
    public function getSignupUrl(): string
    {
        return Self::SIGNUP_URL;
    }

    /**
     * Get reset password url
     */
    public function getResetPasswordUrl(): string
    {
        return Self::RESET_PASSWORD_URL;
    }

    /**
     * Get login url
    */
    public function getLoginUrl(): string
    {
        return Self::LOGIN_API_URL;
    }
}
