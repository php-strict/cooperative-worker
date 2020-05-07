<?php
use \PhpStrict\CooperativeWorker\CooperativeWorker;

class CooperativeWorkerDeleteJobsTest extends \Codeception\Test\Unit
{
    /**
     * @param callable $call
     * @param ...$args
     * @return bool
     */
    protected function expectedNotException(callable $call, ...$args): bool
    {
        try {
            $call(...$args);
        } catch (\Exception $e) {
            $this->fail('Exception throwed: ' . $e->getMessage());
            return false;
        }
        return true;
    }
    
    protected function createJobsWithStorage(string $storage)//: object PHP 7.2
    {
        return new class(
                function() {
                    return ['job1'];
                },
                function(string $job) {
                },
                $storage
            ) extends CooperativeWorker {
                protected function jobsExists(): bool
                {
                    return false;
                }
                public function deleteJobs(): void
                {
                    parent::deleteJobs();
                }
                public function setJobsStorage(string $jobsStorage): void
                {
                    $this->jobsStorage = $jobsStorage;
                }
                public function initFilePointer(): void
                {
                    $this->jobsFilePointer = fopen($this->jobsStorage, 'w');
                }
            };
    }
    
    public function testDeleted()
    {
        $cw = $this->createJobsWithStorage('/tmp/test-jobs.txt');
        $cw->initFilePointer();
        $this->expectedNotException(
            [$cw, 'deleteJobs']
        );
    }
}
