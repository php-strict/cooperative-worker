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
 * Class for starting separate processes with CooperativeWorker instances.
 */
class ScriptRunner
{
    protected const CPU_CORES_COUNT_DEFAULT = 2;
    
    protected const PROCESSES_COUNT_LIMIT = 128;
    
    protected const PROCESS_COMMAND = 'php -f %s';
    
    protected const PROCESS_READ_LENGTH = 256;
    
    protected $procCount = 0;
    
    protected $procHandles = [];
    
    protected $runScript = '';
    
    public function __construct(string $runScript, int $procCount = 0)
    {
        $this->procCount = $procCount;
        if (0 == $procCount) {
            $this->procCount = $this->getSystemCpuCoresCount();
        }
        $this->limitProcCount();
        
        if (!file_exists($runScript)) {
            throw Exception('Run script not exists');
        }
        $this->runScript = $runScript;
    }
    
    public function run(bool $silent = false): void
    {
        for ($i = 0; $i < $this->procCount; $i++) {
            echo 'Run script #' . $i . '... ';
            $this->procHandles[] = popen(sprintf(self::PROCESS_COMMAND, $this->runScript), 'r');
            echo 'OK' . PHP_EOL;
        }
        
        do {
            for ($i = 0, $cnt = count($this->procHandles); $i < $cnt; $i++) {
                $out = fread($this->procHandles[$i], self::PROCESS_READ_LENGTH);
                
                if (!$silent) {
                    echo $out;
                }
                
                if (feof($this->procHandles[$i])) {
                    echo 'Close script #' . $i . '... ';
                    pclose($this->procHandles[$i]);
                    echo 'OK' . PHP_EOL;
                    
                    unset($this->procHandles[$i]);
                    $this->procHandles = array_values($this->procHandles);
                    
                    break;
                }
            }
        } while (0 < count($this->procHandles));
    }
    
    protected function limitProcCount(): void
    {
        if (0 >= $this->procCount || self::PROCESSES_COUNT_LIMIT < $this->procCount) {
            $this->procCount = self::CPU_CORES_COUNT_DEFAULT;
        }
    }
    
    protected function getSystemCpuCoresCount(): int
    {
        if (PHP_OS_FAMILY == 'Windows') {
            return (int) shell_exec('echo %NUMBER_OF_PROCESSORS%');
        }
        return (int) shell_exec('nproc');
    }
}
