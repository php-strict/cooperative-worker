<?php
use \PhpStrict\CooperativeWorker\CooperativeWorker;

class CooperativeWorkerSaveJobsTest extends \Codeception\Test\Unit
{
    public function testSaveEmpty()
    {
        $cw = new class(
                function() {
                    return [];
                },
                function(string $job) {
                },
                '/tmp/test-jobs.txt'
            ) extends CooperativeWorker {
                public function jobsExists(): bool
                {
                    return parent::jobsExists();
                }
                public function deleteJobs(): void
                {
                    parent::deleteJobs();
                }
            };
        $this->assertFalse($cw->jobsExists());
    }
    
    public function testSaved()
    {
        $cw = new class(
                function() {
                    return ['job1'];
                },
                function(string $job) {
                },
                '/tmp/test-jobs.txt'
            ) extends CooperativeWorker {
                public function jobsExists(): bool
                {
                    return parent::jobsExists();
                }
                public function deleteJobs(): void
                {
                    parent::deleteJobs();
                }
                public function initFilePointer(): void
                {
                    $this->jobsFilePointer = fopen($this->jobsStorage, 'w');
                }
            };
        $this->assertTrue($cw->jobsExists());
        $cw->initFilePointer();
        $cw->deleteJobs();
    }
}
