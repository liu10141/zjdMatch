<?php

namespace app\index\command;

use app\admin\controller\v1\Index;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SendMsg extends Command
{
    protected function configure()
    {
        $this->setName('SendMsg')->setDescription("处理订单信息");
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     * 定时任务执行
     */
    protected function execute(Input $input, Output $output)
    {
        $Obj = new Index();
        $Obj->queueSycOrder();
        $Obj->SycIngOrder();
        $Obj->SycUserCoupon();
//        file_put_contents('PUSH.txt', '数据处理时间: ' . date('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);
        die;
    }
}
