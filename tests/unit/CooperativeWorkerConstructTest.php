<?php
use \PhpStrict\CooperativeWorker\CooperativeWorker;

class CooperativeWorkerConstructTest extends \Codeception\Test\Unit
{
    public function testJobsStorageAssignment()
    {
        $cw = 
            new class(
                function() {
                    return [];
                },
                function(string $job) {
                },
                '/path-to-jobs-storage/jobs-storage.txt'
            ) extends CooperativeWorker {
                protected function createJobs(array $jobs): void
                {
                    //do nothing
                }
                public function getJobsStorage(): string
                {
                    return $this->jobsStorage;
                }
            };
        
        $this->assertEquals('/path-to-jobs-storage/jobs-storage.txt', $cw->getJobsStorage());
    }
    
    public function testCreateJobs()
    {
        ob_start();
        $cw = 
            new class(
                function() {
                    return ['job1', 'job2'];
                },
                function(string $job) {
                },
                '/path-to-jobs-storage/jobs-storage.txt'
            ) extends CooperativeWorker {
                protected function createJobs(array $jobs): void
                {
                    echo $this->jobsStorage;
                }
            };
        $createdStorage = ob_get_clean();
        $this->assertEquals('/path-to-jobs-storage/jobs-storage.txt', $createdStorage);
        
        ob_start();
        $cw = 
            new class(
                function() {
                    return ['job1', 'job2'];
                },
                function(string $job) {
                },
                '/path-to-jobs-storage/jobs-storage.txt'
            ) extends CooperativeWorker {
                protected function createJobs(array $jobs): void
                {
                    echo implode(',', $jobs);
                }
            };
        $createdJobs = ob_get_clean();
        $this->assertEquals('job1,job2', $createdJobs);
    }
    
    public function testCreateJobsRunner()
    {
        $jobsRunner = function(string $job) {
            echo $job . '[' . strlen($job) . ']';
        };
        
        $cw = 
            new class(
                function() {
                    return [];
                },
                $jobsRunner
            ) extends CooperativeWorker {
                protected function createJobs(array $jobs): void
                {
                    //do nothing
                }
                public function getJobsRunner()//: object PHP 7.2
                {
                    return $this->jobsRunner;
                }
            };
        
        $this->assertSame($jobsRunner, $cw->getJobsRunner());
        
        ob_start();
        ($cw->getJobsRunner())('job1');
        $jobsRunnerResult = ob_get_clean();
        
        $this->assertEquals('job1[4]', $jobsRunnerResult);
    }
}
