<?php
use \PhpStrict\CooperativeWorker\CooperativeWorker;

class CooperativeWorkerRunTest extends \Codeception\Test\Unit
{
    public function testJobsNotExists()
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
                protected function jobsExists(): bool
                {
                    return false;
                }
                protected function createJobs(array $jobs): void
                {
                    //do nothing
                }
                protected function extractJob(): ?string
                {
                    echo 'unreachable area';
                }
            };
        ob_start();
        $cw->run();
        $runResult = ob_get_clean();
        
        $this->assertStringNotContainsString('unreachable area', $runResult);
    }
    
    public function testMyJobEmpty()
    {
        $cw = 
            new class(
                function() {
                    return [];
                },
                function(string $job) {
                    echo 'unreachable area';
                },
                '/path-to-jobs-storage/jobs-storage.txt'
            ) extends CooperativeWorker {
                protected function jobsExists(): bool
                {
                    return true;
                }
                protected function createJobs(array $jobs): void
                {
                    //do nothing
                }
                protected function extractJob(): ?string
                {
                    return null;
                }
            };
        ob_start();
        $cw->run();
        $runResult = ob_get_clean();
        
        $this->assertStringNotContainsString('unreachable area', $runResult);
    }
    
    public function testRunMyJob()
    {
        $cw = 
            new class(
                function() {
                    return ['job1', 'job2'];
                },
                function(string $job) {
                    echo 'run job: ' . $job;
                },
                '/path-to-jobs-storage/jobs-storage.txt'
            ) extends CooperativeWorker {
                protected $jobs = [];
                protected function jobsExists(): bool
                {
                    return 0 != count($this->jobs);
                }
                protected function createJobs(array $jobs): void
                {
                    $this->jobs = $jobs;
                }
                protected function extractJob(): ?string
                {
                    $job = array_shift($this->jobs);
                    return $job;
                }
            };
        ob_start();
        $cw->run();
        $runResult = ob_get_clean();
        
        $this->assertStringContainsString('run job: job1run job: job2', $runResult);
    }
    
    public function testSecondRun()
    {
        $cw = 
            new class(
                function() {
                    return ['job1', 'job2', 'job3'];
                },
                function(string $job) {
                    ob_clean();
                    echo 'run job: ' . $job;
                },
                '/path-to-jobs-storage/jobs-storage.txt'
            ) extends CooperativeWorker {
                protected $jobs = [];
                protected function jobsExists(): bool
                {
                    return 0 != count($this->jobs);
                }
                protected function createJobs(array $jobs): void
                {
                    $this->jobs = $jobs;
                }
                protected function extractJob(): ?string
                {
                    $job = array_shift($this->jobs);
                    return $job;
                }
            };
        ob_start();
        $cw->run();
        $runResult = ob_get_clean();
        
        $this->assertStringNotContainsString('run job: job1', $runResult);
        $this->assertStringNotContainsString('run job: job2', $runResult);
        $this->assertStringContainsString('run job: job3', $runResult);
    }
}
