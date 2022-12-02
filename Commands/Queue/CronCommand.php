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

use Arikaim\Core\Console\ConsoleCommand;
use Arikaim\Core\Console\ConsoleHelper;
use Arikaim\Core\System\System;
use Arikaim\Core\Interfaces\Job\RecurringJobInterface;
use Arikaim\Core\Interfaces\Job\ScheduledJobInterface;
use Exception;

/**
 * Process cron jobs
 */
class CronCommand extends ConsoleCommand
{  
    /**
     * Configure command
     *
     * @return void
     */
    protected function configure(): void 
    {
        $this->setName('scheduler');
        $this->setDescription('Cron scheduler');
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
        global $container;

        // unlimited execution time
        System::setTimeLimit(0); 
       
        $this->showTitle();

        $jobs = $container->get('queue')->getJobsDue();
        $jobsDue = \count($jobs);

        $this->writeFieldLn('Jobs due ',$jobsDue); 
        $this->writeLn('...');
        $executed = 0;  

        if ($jobsDue > 0) {
            $executed = $this->runJobs($jobs);          
        }

        $this->writeFieldLn('Executed jobs ',$executed);
        $this->showCompleted(); 
    }

    /**
     * Run jobs due
     *
     * @param array $jobs
     * @return integer
     */
    protected function runJobs(array $jobs): int
    {
        global $container;
        
        $executed = 0;  
        
        foreach ($jobs as $item) {
            $job = $container->get('queue')->createJobFromArray($item);
            $isDue = true;
            if (($job instanceof RecurringJobInterface) || ($job instanceof ScheduledJobInterface)) {
                $isDue = $job->isDue();
            } 
               
            if ($isDue == false) {             
                continue;
            }
          
            $name = (empty($job->getName()) == true) ? $job->getId() : $job->getName();
            try {
                $this->writeLn(ConsoleHelper::checkMark() . $name);
                $job = $container->get('queue')->executeJob($job,
                    function($mesasge) {
                        $this->writeLn('  ' . ConsoleHelper::checkMark() . $mesasge);
                    },function($error) {
                        $this->writeLn('  ' . ConsoleHelper::errorMark() . ' Error ' . $error);
                    }
                );

                if ($job->hasSuccess() == true) {                                         
                    $executed++;                       
                } else {
                    $this->writeLn(ConsoleHelper::errorMark() . ' Error executing job ' . $name);
                    $container->get('logger')->error('Failed to execute cron job,',['errors' => $job->getErrors()]);
                }
                
            } catch (Exception $e) {
                $container->get('logger')->error('Failed to execute cron job,',['error' => $e->getMessage()]);
            }           
        }

        return $executed;
    }
}
