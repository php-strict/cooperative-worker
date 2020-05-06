<?php
use \PhpStrict\CooperativeWorker\CooperativeWorker;

class CooperativeWorkerGetJobsTest extends \Codeception\Test\Unit
{
    /**
     * @param string $expectedExceptionMessage
     * @param callable $call
     * @param ...$args
     */
    protected function expectedExceptionContains(string $expectedExceptionMessage, callable $call, ...$args): void
    {
        try {
            $call(...$args);
        } catch (\Exception $e) {
            $this->assertStringContainsString($expectedExceptionMessage, $e->getMessage());
            return;
        }
        $this->fail('Expected exception not throwed');
    }
    
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
                public function getJobs(): array
                {
                    return parent::getJobs();
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
    
    public function testFailOpening()
    {
        $cw = $this->createJobsWithStorage('/tmp/test-jobs.txt');
        $cw->initFilePointer();
        $cw->deleteJobs();
        $this->expectedExceptionContains(
            'Error opening jobs', 
            [$cw, 'getJobs']
        );
    }
    
    public function testFailLocking()
    {
        $cw = $this->createJobsWithStorage('/tmp/test-jobs.txt');
        $cw->initFilePointer();
        $cw->deleteJobs();
        $cw->setJobsStorage('php://memory');
        $this->expectedExceptionContains(
            'Error locking jobs', 
            [$cw, 'getJobs']
        );
    }
    
    //how to successfully open and lock file, but fail to read it?
    // public function testFailReading()
    // {
        // file_put_contents('/tmp/test-jobs-big.txt', str_pad('', 32000000));
        // ini_set('memory_limit','1M');
        // $cw = $this->createJobsWithStorage('/tmp/test-jobs-big.txt');
        // $this->expectedExceptionContains(
            // 'Error reading jobs', 
            // [$cw, 'getJobs']
        // );
        // $cw->deleteJobs();
    // }
    
    public function testGet()
    {
        $cw = $this->createJobsWithStorage('/tmp/test-jobs.txt');
        $this->assertTrue($this->expectedNotException(
            [$cw, 'getJobs']
        ));
        $cw->deleteJobs();
    }
}
