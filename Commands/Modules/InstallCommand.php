<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\App\Commands\Modules;

use Symfony\Component\Console\Output\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Arikaim\Core\Console\ConsoleCommand;

/**
 * Install module command
 */
class InstallCommand extends ConsoleCommand
{  
    /**
     * Configure command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('modules:install')->setDescription('Install module');
        $this->addOptionalArgument('name','Module Name');
    }

    /**
     * Run command
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
            $this->showError('Module name required!');
            return;
        }
        $this->writeFieldLn('Name',$name);

        $manager = $arikaim->get('packages')->create('module');
        $package = $manager->createPackage($name);
        if ($package == false) {
            $this->showError('Module ' . $name . ' not exists!');
            return;
        }

        $result = $package->install();
     
        if ($result == false) {
            $this->showError("Can't install module!");
            return;
        }

        $this->showCompleted();
    }
}
