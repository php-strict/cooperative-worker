<?php
use \PhpStrict\CooperativeWorker\CooperativeWorker;

class CooperativeWorkerExtractJobTest extends \Codeception\Test\Unit
{
    public function testExtractJob()
    {
        $cw = 
            new class(
                function() {
                    return ['job1', 'job2'];
                },
                function(string $job) {
                }
            ) extends CooperativeWorker {
                protected $jobs;
                public function __construct(callable $jobsCreator, callable $jobsRunner, ?string $jobsStorage = null)
                {
                    parent::__construct($jobsCreator, $jobsRunner, $jobsStorage);
                    $this->jobs = $jobsCreator();
                }
                protected function jobsExists(): bool
                {
                    return true;
                }
                public function extractJob(): ?string
                {
                    return parent::extractJob();
                }
                protected function getJobs(): array
                {
                    return $this->jobs;
                }
                protected function saveJobs(array $jobs): void
                {
                    $this->jobs = $jobs;
                }
                protected function deleteJobs(): void
                {
                    //do nothing
                }
            };
        
        $this->assertEquals($cw->extractJob(), 'job1');
        $this->assertEquals($cw->extractJob(), 'job2');
        $this->assertNull($cw->extractJob());
    }
}
