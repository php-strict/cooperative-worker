<?php
use \PhpStrict\CooperativeWorker\CooperativeWorker;

class CooperativeWorkerJobsExistsTest extends \Codeception\Test\Unit
{
    public function testJobsExists()
    {
        $cw = 
            new class(
                function() {
                    return ['job1', 'job2'];
                },
                function(string $job) {
                },
                '/tmp/test-jobs-storage.txt'
            ) extends CooperativeWorker {
                public function jobsExists(): bool
                {
                    return parent::jobsExists();
                }
                public function extractJob(): ?string
                {
                    return parent::extractJob();
                }
            };
        
        $this->assertTrue($cw->jobsExists());
        $cw->extractJob();
        $this->assertTrue($cw->jobsExists());
        $cw->extractJob();
        $this->assertFalse($cw->jobsExists());
    }
}
