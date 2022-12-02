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
 * Disable cache command
 * 
 */
class DisableCommand extends ConsoleCommand
{  
    /**
     * Command config
     * name cache:clear 
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('cache:disable')->setDescription('Disable cache');
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
        $container->get('cache')->clear();
        
        $container->get('config')->setBooleanValue('settings/cache',false);
        $result = $container->get('config')->save();

        if ($result !== true) {
            $error = $container->get('errors')->getError('CACHE_DISABLE_ERROR');
            $this->showError($error);
            return;
        } 

        $this->showCompleted();
    }
}
