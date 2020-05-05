<?php
use \PhpStrict\CooperativeWorker\CooperativeWorker;

class CooperativeWorkerCreateJobsTest extends \Codeception\Test\Unit
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
    
    protected function createJobsWithStorage(string $storage): void
    {
        new class(
                function() {
                    return [];
                },
                function(string $job) {
                },
                $storage
            ) extends CooperativeWorker {
                protected function jobsExists(): bool
                {
                    return false;
                }
            };
    }
    
    public function testJobsNotExists()
    {
        $this->expectedExceptionContains(
            'Error creating jobs', 
            [$this, 'createJobsWithStorage'], 
            '/not-existence-dir/test-jobs.txt'
        );
        
        $this->expectedExceptionContains(
            'Error locking jobs', 
            [$this, 'createJobsWithStorage'], 
            'php://memory'
        );
        
        $this->assertTrue($this->expectedNotException(
            [$this, 'createJobsWithStorage'], 
            '/tmp/test-jobs.txt'
        ));
    }
}
