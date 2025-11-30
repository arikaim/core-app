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

use Arikaim\Core\Controllers\Controller;

/**
 * Page loader controller
*/
class ControlPanel extends Controller 
{   
    /**
     * Load control panel page
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Arikaim\Core\Validator\Validator $data
     * @return mixed
    */
    public function loadControlPanel($request, $response, $data) 
    {                 
        $this->get('access')->withProvider('session');

        // Set contorl panel template as primary
        $this->get('view')->setPrimaryTemplate('system');

        return $this->pageLoad($request,$response,$data,'system:admin');       
    }
}
