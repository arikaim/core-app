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
 * Show cache driver command
 * 
 */
class DriverCommand extends ConsoleCommand
{  
    /**
     * Command config
     * name cache:clear 
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('cache:driver')->setDescription('Cache driver info');
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
         
        $driver = Arikaim::cache()->getDriver();
        $driverClass = \get_class($driver);

        $this->writeFieldLn('Class',$driverClass);
        $this->writeFieldLn('Name',Arikaim::cache()->getDriverName());
      
        $this->showCompleted();
    }
}
