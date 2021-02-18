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
use Arikaim\Core\Arikaim;
use Arikaim\Core\Utils\DateTime;
use Arikaim\Core\Interfaces\Job\JobLogInterface;
use Arikaim\Core\Interfaces\Job\RecuringJobInterface;
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
        // unlimited execution time
        System::setTimeLimit(0); 
        // Set time zone
        DateTime::setTimeZone(Arikaim::options()->get('time.zone'));

        $this->showTitle();

        $jobs = Arikaim::queue()->getJobsDue();
        $this->writeLn('...');
        $executed = 0;  

        if (empty($jobs) == false) {
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
        $executed = 0;  
        foreach ($jobs as $item) {
            $job = Arikaim::queue()->createJobFromArray($item);
        
            $isDue = ($job instanceof RecuringJobInterface || $job instanceof ScheduledJobInterface) ? $job->isDue() : true;            
            if ($isDue == false) {             
                continue;
            }
          
            $name = (empty($job->getName()) == true) ? $job->getId() : $job->getName();
            try {
                $this->writeLn(ConsoleHelper::checkMark() . $name);
                $job = Arikaim::queue()->executeJob($job,
                    function($mesasge) {
                        $this->writeLn('  ' . ConsoleHelper::checkMark() . $mesasge);
                    },function($error) {
                        $this->writeLn('  ' .ConsoleHelper::errorMark() . ' Error ' . $error);
                    }
                );

                if ($job->hasSuccess() == true) {                                         
                    $executed++;    
                    if ($job instanceof JobLogInterface) {
                        Arikaim::logger()->info($job->getLogMessage(),$job->getLogContext());
                    }
                } else {
                    $this->writeLn(ConsoleHelper::errorMark() . ' Error executing job ' . $name);
                    Arikaim::logger()->error('Failed to execute cron job,',['errors' => $job->getErrors()]);
                }
                
            } catch (Exception $e) {
                Arikaim::logger()->error('Failed to execute cron job,',['error' => $e->getMessage()]);
            }           
        }

        return $executed;
    }
}
