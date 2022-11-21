<?php

namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class ApiSend extends Command
{
    protected function configure()
    {
        $this->setName('ApiSend')->setDescription("");
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     * 定时任务执行
     */
    protected function execute(Input $input, Output $output)
    {
        file_put_contents('CA.txt', '采集数据时间: ' . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
        die;
    }
}
