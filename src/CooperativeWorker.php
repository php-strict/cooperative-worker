<?php
/**
 * PHP Strict.
 * 
 * @copyright   Copyright (C) 2018 - 2020 Enikeishik <enikeishik@gmail.com>. All rights reserved.
 * @author      Enikeishik <enikeishik@gmail.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace PhpStrict\CooperativeWorker;

/**
 * Class for executing jobs from one list in several processes.
 * Class not have mechanism to create processes, consumer must create it by self (see readme): 
 * - start command on Windows systems; 
 * - & at the end of command on Linux systems; 
 * - also may be used https://github.com/php-strict/script-runner.
 * Each separate process can create instance of class wich will work with shared storage (queue) without collisions.
 * Temporary storage of jobs (queue) will be created in first instance of class and it be use all of instances.
 */
class CooperativeWorker
{
    /**
     * @var string
     */
    protected $jobsStorage = 'jobs.txt';
    
    /**
     * @var callable
     */
    protected $jobsFilePointer;
    
    /**
     * @var callable
     */
    protected $jobsRunner;
    
    /**
     * Creates jobs storage if needed, initiates state.
     * @param callable $jobsCreator         returns an array of strings (jobs)
     * @param callable $jobsRunner          takes one string parameter as job to run
     * @param ?string $jobsStorage = null   user defined path to file to store jobs
     */
    public function __construct(callable $jobsCreator, callable $jobsRunner, ?string $jobsStorage = null)
    {
        if (null !== $jobsStorage) {
            $this->jobsStorage = $jobsStorage;
        }
        
        if (!$this->jobsExists()) {
            $this->createJobs($jobsCreator());
        }
        
        $this->jobsRunner = $jobsRunner;
    }
    
    /**
     * Performs one (extracted first from jobs list) job using external jobs runner, and runs self again.
     * @return void
     */
    public function run(): void
    {
        if (!$this->jobsExists()) {
            return;
        }
        
        $myJob = $this->extractJob();
        if (null === $myJob) {
            return;
        }
        
        ($this->jobsRunner)($myJob);
        
        $this->run();
    }
    
    /**
     * Extracts first job from jobs and save reduced jobs list.
     * @return ?string
     */
    protected function extractJob(): ?string
    {
        $jobs = $this->getJobs();
        $myJob = array_shift($jobs);
        if (null === $myJob) {
            $this->deleteJobs();
            return null;
        }
        $this->saveJobs($jobs);
        
        return $myJob;
    }
    
    /**
     * Checks if jobs storage (file) exists.
     * @return bool
     */
    protected function jobsExists(): bool
    {
        return file_exists($this->jobsStorage);
    }
    
    /**
     * Creates new jobs storage (file), lock it and fill it with jobs.
     * @param array $jobs
     * @return void
     */
    protected function createJobs(array $jobs): void
    {
        $this->jobsFilePointer = @fopen($this->jobsStorage, 'w'); //use try..catch instead of @ to hide warning?
        if ($this->jobsFilePointer === false) {
            throw new \Exception('Error creating jobs');
        }
        
        if (!flock($this->jobsFilePointer, LOCK_EX)) {
            throw new \Exception('Error locking jobs');
        }
        
        $this->saveJobs($jobs);
    }
    
    /**
     * Locks jobs storage and gets jobs from it.
     * @return array
     */
    protected function getJobs(): array
    {
        $this->jobsFilePointer = @fopen($this->jobsStorage, 'r+'); //use try..catch instead of @ to hide warning?
        if ($this->jobsFilePointer === false) {
            throw new \Exception('Error opening jobs');
        }
        
        if (!flock($this->jobsFilePointer, LOCK_EX)) {
            throw new \Exception('Error locking jobs');
        }
        
        $data = fread($this->jobsFilePointer, filesize($this->jobsStorage));
        if ($this->jobsFilePointer === false) {
            throw new \Exception('Error reading jobs');
        }
        
        return explode(PHP_EOL, $data);
    }
    
    /**
     * Clears jobs storage, fill it with jobs and unlocks it.
     * @param array $jobs
     * @return void
     */
    protected function saveJobs(array $jobs): void
    {
        if (0 == count($jobs)) {
            $this->deleteJobs();
            return;
        }
        
        rewind($this->jobsFilePointer);
        ftruncate($this->jobsFilePointer, 0);
        fwrite($this->jobsFilePointer, implode(PHP_EOL, $jobs));
        fflush($this->jobsFilePointer);
        flock($this->jobsFilePointer, LOCK_UN);
        fclose($this->jobsFilePointer);
    }
    
    /**
     * Clears jobs storage, unlocks and delete it.
     * @return void
     */
    protected function deleteJobs(): void
    {
        rewind($this->jobsFilePointer);
        ftruncate($this->jobsFilePointer, 0);
        flock($this->jobsFilePointer, LOCK_UN);
        fclose($this->jobsFilePointer);
        unlink($this->jobsStorage);
    }
}
