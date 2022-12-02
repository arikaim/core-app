<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\App\Commands\Cache;

use Arikaim\Core\Console\ConsoleCommand;

/**
 * Enable cache command
 * 
 */
class EnableCommand extends ConsoleCommand
{  
    /**
     * Command config
     * name cache:clear 
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('cache:enable')->setDescription('Enable cache');
    }

    /**
     * Command code
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    protected function executeCommand($input, $output)
    {
        global $container;
        
        $this->showTitle();
        
        $container->get('config')->setBooleanValue('settings/cache',true);
        $result = $container->get('config')->save();

        $container->get('cache')->clear();
        
        if ($result !== true) {
            $error = $container->get('errors')->getError('CACHE_ENABLE_ERROR');
            $this->showError($error);
            return;
        } 
      
        $this->showCompleted();        
    }
}
