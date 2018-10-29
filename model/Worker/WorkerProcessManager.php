<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoTaskQueue\model\Worker;

use Exception;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use React\ChildProcess\Process;

class WorkerProcessManager extends ConfigurableService
{
    use LoggerAwareTrait;

    const SERVICE_ID = 'taoTaskQueue/WorkerProcessManager';

    const OPTION_TASK_COMMAND = 'task_command';

    /** @var Process[] */
    private $processes;

    /** @var integer */
    private $limitOfCpu;

    /** @var integer */
    private $limitOfMemory;

    /**
     * @param Process $process
     */
    public function addProcess(Process $process)
    {
        $pid = $process->getPid();

        $process->stdout->on('data', function ($status) use ($pid)  {
            $this->logInfo('Process: '. $pid .' status:'. $status);
        });

        $process->stdout->on('end', function () use ($pid)  {
            $this->logInfo('Process: '. $pid .' ended');
        });

        $process->stdout->on('error', function (Exception $e) use ($pid)  {
            $this->logError('Process: '. $pid .' error. ' . $e->getMessage());
        });

        $process->stdout->on('close', function () use ($pid)  {
            $this->logInfo('Process: '. $pid .' closed.');

            unset($this->processes[$pid]);
        });

        $process->stdin->end($data = null);

        $this->processes[$pid] = $process;
    }

    public function canRun()
    {
        $this->logInfo('No of process workers running: '. count($this->processes));

        $memoryUsage = $this->getMemoryUsage();
        $cpuUsage    = $this->getCpuUsage();

        if ($memoryUsage < $this->limitOfMemory
            && $cpuUsage < $this->limitOfCpu
        ) {
            return true;
        }

        $this->logInfo('Limit Of memory and Cpu exceeded waiting for task to finish.
        Current memory usage:'.$memoryUsage.' Cpu usage:'.$cpuUsage);
    }

    /**
     * @return mixed
     */
    public function getCommand()
    {
        return $this->getOption(static::OPTION_TASK_COMMAND);
    }

    /**
     * @return float|int
     */
    public function getMemoryUsage()
    {
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        $memory_usage = $mem[2]/$mem[1]*100;

        return $memory_usage;
    }

    /**
     * @return mixed
     */
    public function getCpuUsage()
    {
        $load = sys_getloadavg();

        return $load[0];
    }

    /**
     * @return int
     */
    public function getLimitOfCpu()
    {
        return $this->limitOfCpu;
    }

    /**
     * @param int $limitOfCpu
     */
    public function setLimitOfCpu($limitOfCpu)
    {
        $this->limitOfCpu = $limitOfCpu;
    }

    /**
     * @return int
     */
    public function getLimitOfMemory()
    {
        return $this->limitOfMemory;
    }

    /**
     * @param int $limitOfMemory
     */
    public function setLimitOfMemory($limitOfMemory)
    {
        $this->limitOfMemory = $limitOfMemory;
    }
}