<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\App\Commands\Template;

use Arikaim\Core\Console\ConsoleCommand;

/**
 * Install tempolate command
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
        $this->setName('template:install')->setDescription('Install template');
        $this->addOptionalArgument('name','Template Name');
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
            $this->showError('Template name required!');
            return;
        }
        
        $this->writeFieldLn('Name',$name);
        
        $manager = $arikaim->get('packages')->create('template');
        $package = $manager->createPackage($name);
        if ($package == false) {
            $this->showError('Template ' . $name . ' not exists!');
            return;
        }

        $result = $package->install();
     
        $arikaim->get('cache')->clear();
        
        if ($result === false) {
            $this->showError("Can't install template!");
            return;
        }

        $this->showCompleted();
    }
}
