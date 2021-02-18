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
use Arikaim\Core\Arikaim;

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
        $this->showTitle();
        
        Arikaim::config()->setBooleanValue('settings/cache',true);
        $result = Arikaim::config()->save();

        Arikaim::cache()->clear();
        
        if ($result !== true) {
            $error = Arikaim::errors()->getError('CACHE_ENABLE_ERROR');
            $this->showError($error);
            return;
        } 
      
        $this->showCompleted();        
    }
}
