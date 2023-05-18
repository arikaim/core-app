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
use Arikaim\Core\Actions\Actions;

/**
 * Clear cache command
 * 
 */
class ClearCommand extends ConsoleCommand
{  
    /**
     * Command config
     * name cache:clear 
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('cache:clear')->setDescription('Clear cache');
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

        $this->showCompleted();
    }
}
