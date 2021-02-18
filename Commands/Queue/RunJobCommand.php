<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
 */
namespace Arikaim\Core\App\Commands\Queue;

use Arikaim\Core\Console\ConsoleHelper;
use Arikaim\Core\Console\ConsoleCommand;
use Arikaim\Core\Arikaim;
use Arikaim\Core\Interfaces\Job\JobLogInterface;

/**
 * Run job command
 */
class RunJobCommand extends ConsoleCommand
{  
    /**
     * Configure command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('job:run')->setDescription('Run job.');
        $this->addOptionalArgument('name','Job Name');
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
        $this->showTitle();

        $name = $input->getArgument('name');
        if (empty($name) == true) {
            $this->showError('Job name required!');
            return;
        }
        if (Arikaim::queue()->has($name) == false) {
            $this->showError('Not valid job name!');
            return;
        } 
    
        $this->writeFieldLn('Name',$name);
   
        $job = Arikaim::queue()->run($name,
            function($mesasge) {
                $this->writeLn('  ' . ConsoleHelper::checkMark() . $mesasge);
            },function($error) {
                $this->writeLn('  ' . ConsoleHelper::errorMark() . ' Error ' . $error);
            }
        );
        
        if ($job->hasSuccess() == true) {                                         
            if ($job instanceof JobLogInterface) {
                Arikaim::logger()->info($job->getLogMessage(),$job->getLogContext());
            }
            $this->showCompleted();    
        } else {
            // error
            $this->showError('Error');
            $this->showErrorDetails($job->getErrors());
            if ($job instanceof JobLogInterface) {
                Arikaim::logger()->error('Failed to execute cron job,',['errors' => $job->getErrors()]);
            }
        }                 
    }    
}
