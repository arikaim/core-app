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

/**
 * Routes
 */
class SystemRoutes 
{
    /**
     * Install api routes
     *
     * @var array
     */
    public static $installRoutes = [
        'POST' => [
            // Install
            [
                'pattern'    => '/core/api/install/',
                'handler'    => 'Arikaim\Core\Api\Install:install',
                'middleware' => null         
            ]
        ],
        'PUT' => [
            // Install
            [
                'pattern'    => '/core/api/install/extensions',
                'handler'    => 'Arikaim\Core\Api\Install:installExtensions',
                'middleware' => null         
            ],
            [
                'pattern'    => '/core/api/install/modules',
                'handler'    => 'Arikaim\Core\Api\Install:installModules',
                'middleware' => null         
            ],
            [
                'pattern'    => '/core/api/install/actions',
                'handler'    => 'Arikaim\Core\Api\Install:postInstallActions',
                'middleware' => null         
            ]
        ]
    ];

    /**
     * System routes
     *
     * @var array
     */
    public static $routes = [
        'GET' => [           
            // Ui component
            [
                'pattern'    => '/core/api/ui/component/properties/{name}[/{params:.*}]',
                'handler'    => 'Arikaim\Core\Api\Ui\Component:componentProperties',
                'middleware' => null               
            ],
            [
                'pattern'    => '/core/api/ui/component/details/{name}[/{params:.*}]',
                'handler'    => 'Arikaim\Core\Api\Ui\Component:componentDetails',
                'middleware' => 'session'               
            ],
            [
                'pattern'    => '/core/api/ui/component/{name}[/{params:.*}]',
                'handler'    => 'Arikaim\Core\Api\Ui\Component:loadComponent',
                'middleware' => null            
            ],
            // UI Page
            [
                'pattern'    => '/core/api/ui/page/{name}',
                'handler'    => 'Arikaim\Core\Api\Ui\Page:loadPageHtml',
                'middleware' => null            
            ],
            [
                'pattern'    => '/core/api/ui/page/properties/',
                'handler'    => 'Arikaim\Core\Api\Ui\Page:loadPageProperties',
                'middleware' => null            
            ],
            // UI Library
            [
                'pattern'    => '/core/api/ui/library/{name}',
                'handler'    => 'Arikaim\Core\Api\Ui\Page:loadLibraryDetails',
                'middleware' => null            
            ], 
            // Paginator 
            [
                'pattern'    => '/core/api/ui/paginator/[{namespace}]',
                'handler'    => 'Arikaim\Core\Api\Ui\Paginator:getPage',
                'middleware' => null            
            ],
            [
                'pattern'    => '/core/api/ui/paginator/view/type/[{namespace}]',
                'handler'    => 'Arikaim\Core\Api\Ui\Paginator:getViewType',
                'middleware' => null            
            ],
            // Order by column     
            [
                'pattern'    => '/core/api/ui/order/[{namespace}]',
                'handler'    => 'Arikaim\Core\Api\Ui\OrderBy:getOrderBy',
                'middleware' => 'session'            
            ],
            // Options 
            [
                'pattern'    => '/core/api/options/{key}',
                'handler'    => 'Arikaim\Core\Api\Options:get',
                'middleware' => 'session'            
            ],          
            // Update
            [
                'pattern'    => '/core/api/update/check/version',
                'handler'    => 'Arikaim\Core\Api\Update:checkVersion',
                'middleware' => 'session'            
            ],
            // Mailer 
            [
                'pattern'    => '/core/api/mailer/test/email',
                'handler'    => 'Arikaim\Core\Api\Mailer:sendTestEmail',
                'middleware' => 'session'            
            ],
            // Session
            [
                'pattern'    => '/core/api/session/',
                'handler'    => 'Arikaim\Core\Api\Session:getInfo',
                'middleware' => null       
            ],
            // Logout
            [
                'pattern'    => '/core/api/user/logout',
                'handler'    => 'Arikaim\Core\Api\Users:logout',
                'middleware' => null            
            ]
        ],
        'POST' => [
            // Arikaim Store
            [
                'pattern'    => '/core/api/store/product',
                'handler'    => 'Arikaim\Core\Api\Store:saveOrder',
                'middleware' => null                
            ],
            // Api routes
            [
                'pattern'    => '/core/api/create/token/',
                'handler'    => 'Arikaim\Core\Api\Client:createToken',
                'middleware' => null                
            ],
            [
                'pattern'    => '/core/api/verify/request/',
                'handler'    => 'Arikaim\Core\Api\Client:verifyRequest',
                'middleware' => null                
            ],
            // Ui component
            [
                'pattern'    => '/core/api/ui/component/{name}[/{params:.*}]',
                'handler'    => 'Arikaim\Core\Api\Ui\Component:loadComponent',
                'middleware' => null            
            ],
            // User
            [
                'pattern'    => '/core/api/user/login',
                'handler'    => 'Arikaim\Core\Api\Users:adminLogin',
                'middleware' => null            
            ],
            [
                'pattern'    => '/core/api/user/details',
                'handler'    => 'Arikaim\Core\Api\Users:changeDetails',
                'middleware' => null            
            ],
            [
                'pattern'    => '/core/api/user/password',
                'handler'    => 'Arikaim\Core\Api\Users:changePassword',
                'middleware' => null            
            ],
            // Languages  
            [
                'pattern'    => '/core/api/language/add',
                'handler'    => 'Arikaim\Core\Api\Language:add',
                'middleware' => 'session'            
            ],
            // Options 
            [
                'pattern'    => '/core/api/options/',
                'handler'    => 'Arikaim\Core\Api\Options:saveOptions',
                'middleware' => 'session'            
            ],
            // Options and relations used for all extensions  
            [
                'pattern'    => '/core/api/orm/relation',
                'handler'    => 'Arikaim\Core\Api\Orm\Relations:addRelation',
                'middleware' => 'session'            
            ],
            // Packages
            [
                'pattern'    => '/core/api/packages/upload',
                'handler'    => 'Arikaim\Core\Api\UploadPackages:upload',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/packages/config',
                'handler'    => 'Arikaim\Core\Api\Packages:saveConfig',
                'middleware' => 'session'            
            ],
            // Install
            [
                'pattern'    => '/core/api/install/',
                'handler'    => 'Arikaim\Core\Api\Install:install',
                'middleware' => null         
            ]
        ],
        'PUT' => [
            // Arikaim Store remove order
            [
                'pattern'    => '/core/api/store/product/remove',
                'handler'    => 'Arikaim\Core\Api\Store:removeOrder',
                'middleware' => null                
            ],
            // Paginator 
            [
                'pattern'    => '/core/api/ui/paginator/page-size',
                'handler'    => 'Arikaim\Core\Api\Ui\Paginator:setPageSize',
                'middleware' => null            
            ],
            [
                'pattern'    => '/core/api/ui/paginator/page',
                'handler'    => 'Arikaim\Core\Api\Ui\Paginator:setPage',
                'middleware' => null            
            ],
            [
                'pattern'    => '/core/api/ui/paginator/view/type',
                'handler'    => 'Arikaim\Core\Api\Ui\Paginator:setViewType',
                'middleware' => null            
            ],     
            // Search
            [
                'pattern'    => '/core/api/ui/search/',
                'handler'    => 'Arikaim\Core\Api\Ui\Search:setSearch',
                'middleware' => null            
            ],
            [
                'pattern'    => '/core/api/ui/search/condition/[{namespace}]',
                'handler'    => 'Arikaim\Core\Api\Ui\Search:setSearchCondition',
                'middleware' => null            
            ],
            // Order by column     
            [
                'pattern'    => '/core/api/ui/order/[{namespace}]',
                'handler'    => 'Arikaim\Core\Api\Ui\OrderBy:setOrderBy',
                'middleware' => 'session'            
            ],
            // Position
            [
                'pattern'    => '/core/api/ui/position/shift',
                'handler'    => 'Arikaim\Core\Api\Ui\Position:shift',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/ui/position/swap',
                'handler'    => 'Arikaim\Core\Api\Ui\Position:swap',
                'middleware' => 'session'            
            ],
            // Languages
            [
                'pattern'    => '/core/api/language/update',
                'handler'    => 'Arikaim\Core\Api\Language:update',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/language/change/{language_code}',
                'handler'    => 'Arikaim\Core\Api\Language:changeLanguage',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/language/status',
                'handler'    => 'Arikaim\Core\Api\Language:setStatus',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/language/default',
                'handler'    => 'Arikaim\Core\Api\Language:setDefault',
                'middleware' => 'session'            
            ],
            // Options 
            [
                'pattern'    => '/core/api/options/',
                'handler'    => 'Arikaim\Core\Api\Options:save',
                'middleware' => 'session'            
            ],
            // Cron  
            [
                'pattern'    => '/core/api/queue/cron/install',
                'handler'    => 'Arikaim\Core\Api\CronApi:installCron',
                'middleware' => 'session'            
            ],
            // Jobs
            [
                'pattern'    => '/core/api/jobs/status',
                'handler'    => 'Arikaim\Core\Api\Jobs:setStatus',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/jobs/config',
                'handler'    => 'Arikaim\Core\Api\Jobs:saveConfig',
                'middleware' => 'session'            
            ],
            // Drivers 
            [
                'pattern'    => '/core/api/driver/status',
                'handler'    => 'Arikaim\Core\Api\Drivers:setStatus',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/driver/config',
                'handler'    => 'Arikaim\Core\Api\Drivers:saveConfig',
                'middleware' => 'session'            
            ],
            // Update  
            [
                'pattern'    => '/core/api/update/',
                'handler'    => 'Arikaim\Core\Api\Update:update',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/update/last/version',
                'handler'    => 'Arikaim\Core\Api\Update:getLastVersion',
                'middleware' => 'session'            
            ],
            // Session
            [
                'pattern'    => '/core/api/session/recreate',
                'handler'    => 'Arikaim\Core\Api\SessionApi:recreate',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/session/restart',
                'handler'    => 'Arikaim\Core\Api\SessionApi:restart',
                'middleware' => 'session'            
            ],
            // Settings
            [
                'pattern'    => '/core/api/settings/install-page',
                'handler'    => 'Arikaim\Core\Api\Settings:disableInstallPage',
                'middleware' => 'session'            
            ],
            // Cache
            [
                'pattern'    => '/core/api/cache/enable',
                'handler'    => 'Arikaim\Core\Api\Cache:enable',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/cache/disable',
                'handler'    => 'Arikaim\Core\Api\Cache:disable',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/cache/driver',
                'handler'    => 'Arikaim\Core\Api\Cache:setDriver',
                'middleware' => 'session'            
            ],
            // Options and relations used for all extensions
            [
                'pattern'    => '/core/api/orm/relation/delete',
                'handler'    => 'Arikaim\Core\Api\Orm\Relations:deleteRelation',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/orm/options',
                'handler'    => 'Arikaim\Core\Api\Orm\Options:saveOptions',
                'middleware' => 'session'            
            ],
            // Packages
            [
                'pattern'    => '/core/api/packages/upload/confirm',
                'handler'    => 'Arikaim\Core\Api\UploadPackages:confirmUpload',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/packages/install',
                'handler'    => 'Arikaim\Core\Api\Packages:install',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/packages/composer/update',
                'handler'    => 'Arikaim\Core\Api\Packages:updateComposerPackages',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/packages/repository/download',
                'handler'    => 'Arikaim\Core\Api\Repository:repositoryDownload',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/packages/status',
                'handler'    => 'Arikaim\Core\Api\Packages:setStatus',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/packages/uninstall',
                'handler'    => 'Arikaim\Core\Api\Packages:unInstall',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/packages/update',
                'handler'    => 'Arikaim\Core\Api\Packages:update',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/packages/primary',
                'handler'    => 'Arikaim\Core\Api\Packages:setPrimary',
                'middleware' => 'session'            
            ],
            // Ui library
            [
                'pattern'    => '/core/api/packages/library/params',
                'handler'    => 'Arikaim\Core\Api\Packages:setLibraryParams',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/packages/library/status',
                'handler'    => 'Arikaim\Core\Api\Packages:setLibraryStatus',
                'middleware' => 'session'            
            ],
            // Install
            [
                'pattern'    => '/core/api/install/extensions',
                'handler'    => 'Arikaim\Core\Api\Install:installExtensions',
                'middleware' => null         
            ],
            [
                'pattern'    => '/core/api/install/modules',
                'handler'    => 'Arikaim\Core\Api\Install:installModules',
                'middleware' => null         
            ],
            [
                'pattern'    => '/core/api/install/actions',
                'handler'    => 'Arikaim\Core\Api\Install:postInstallActions',
                'middleware' => null         
            ],
            [
                'pattern'    => '/core/api/install/repair',
                'handler'    => 'Arikaim\Core\Api\Install:repair',
                'middleware' => 'session'         
            ]
        ],
        'DELETE' => [
            // Paginator 
            [
                'pattern'    => '/core/api/ui/paginator/{namespace}',
                'handler'    => 'Arikaim\Core\Api\Ui\Paginator:remove',
                'middleware' => null            
            ],
            // Search
            [
                'pattern'    => '/core/api/ui/search/condition/{field}/[{namespace}]',
                'handler'    => 'Arikaim\Core\Api\Ui\Search:deleteSearchCondition',
                'middleware' => null            
            ],
            [
                'pattern'    => '/core/api/ui/search/[{namespace}]',
                'handler'    => 'Arikaim\Core\Api\Ui\Search:clearSearch',
                'middleware' => null            
            ],
            // Order by column     
            [
                'pattern'    => '/core/api/ui/order/[{namespace}]',
                'handler'    => 'Arikaim\Core\Api\Ui\OrderBy:deleteOrderBy',
                'middleware' => 'session'            
            ],
            // Languages
            [
                'pattern'    => '/core/api/language/{uuid}',
                'handler'    => 'Arikaim\Core\Api\Language:remove',
                'middleware' => 'session'            
            ],
            // Cron  
            [
                'pattern'    => '/core/api/queue/cron/uninstall',
                'handler'    => 'Arikaim\Core\Api\CronApi:unInstallCron',
                'middleware' => 'session'            
            ],
            // Jobs
            [
                'pattern'    => '/core/api/jobs/delete/{uuid}',
                'handler'    => 'Arikaim\Core\Api\Jobs:deleteJob',
                'middleware' => 'session'            
            ],
            // Access tokens 
            [
                'pattern'    => '/core/api/tokens/delete/{uuid}',
                'handler'    => 'Arikaim\Core\Api\AccessTokens:delete',
                'middleware' => 'session'            
            ],
            [
                'pattern'    => '/core/api/tokens/delete/expired/{uuid}',
                'handler'    => 'Arikaim\Core\Api\AccessTokens:deleteExpired',
                'middleware' => 'session'            
            ],
            // Cache
            [
                'pattern'    => '/core/api/cache/clear',
                'handler'    => 'Arikaim\Core\Api\Cache:clear',
                'middleware' => 'session'            
            ],
            // Logs  
            [
                'pattern'    => '/core/api/logs/clear',
                'handler'    => 'Arikaim\Core\Api\Logger:clear',
                'middleware' => 'session'            
            ]    
        ]      
    ];
}
