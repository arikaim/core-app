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

/**
 * Enable driver command
 */
class EnableCommand extends ConsoleCommand
{  
    /**
     * Configure command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('drivers:enable')->setDescription('Enable driver');
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
        global $arikaim;
        $this->showTitle(); 

        $name = $input->getArgument('name');
        if (empty($name) == true) {
            $error = $arikaim->get('errors')->getError('ARGUMENT_ERROR',['name' => 'name']);
            $this->showError($error);
            return;
        }
        
        $this->writeFieldLn('Name',$name);
        
        if ($arikaim->get('driver')->has($name) == false) {
            $error = $arikaim->get('errors')->getError('DRIVER_NOT_EXISTS_ERROR',['name' => $name]);
            $this->showError($error);
            return;
        }

        $result = $arikaim->get('driver')->enable($name);
        if ($result == false) {
            $error = $arikaim->get('errors')->getError('DRIVER_ENABLE_ERROR');
            $this->showError($error);           
            return;
        }
       
        $this->showCompleted();
    }
}
