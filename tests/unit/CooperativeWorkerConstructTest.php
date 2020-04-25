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
}
