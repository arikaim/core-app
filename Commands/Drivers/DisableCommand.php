<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\App\Commands\Drivers;

use Arikaim\Core\Console\ConsoleCommand;
use Arikaim\Core\Arikaim;

/**
 * Disable driver command
 */
class DisableCommand extends ConsoleCommand
{  
    /**
     * Configure command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('drivers:disable')->setDescription('Disable driver');
        $this->addOptionalArgument('name','Driver name');
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function executeCommand($input, $output)
    {       
        $this->showTitle(); 
        $name = $input->getArgument('name');
        if (empty($name) == true) {
            $error = Arikaim::errors()->getError('ARGUMENT_ERROR',['name' => 'name']);
            $this->showError($error);
            return;
        }
        
        $this->writeFieldLn('Name',$name);
      
        if (Arikaim::driver()->has($name) == false) {
            $error = Arikaim::errors()->getError('DRIVER_NOT_EXISTS_ERROR',['name' => $name]);
            $this->showError($error);
            return;
        }
       
        $result = Arikaim::driver()->disable($name);
        if ($result == false) {
            $error = Arikaim::errors()->getError('DRIVER_DISABLE_ERROR');
            $this->showError($error);           
            return;
        }

        $this->showCompleted();
    }
}
