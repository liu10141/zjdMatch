<?php


namespace app\admin\model;


use think\Model;

class YckLog extends Model
{
    protected $autoWriteTimestamp = 'datetime';

    protected $table = 'yck_log';
}