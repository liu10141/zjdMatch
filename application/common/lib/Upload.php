<?php
/**
 * Created by PhpStorm.
 * User: baidu
 * Date: 17/7/31
 * Time: 上午12:29
 */
namespace app\common\lib;
//引入上传类
use phpDocumentor\Reflection\Types\Null_;
use think\image;
use think\File;
use think\Request;
/**
 * 七牛图片基础类库
 * Class Upload
 * @package app\common\lib
 */
class Upload
{
    /**
     * 图片上传  如果不用骑牛云就用它
     */
    public static function image($imgname, $tmp)
    {
        $imgDir =DS.'uploads'.DS.'admin'.DS.date("Ymd",time()).DS;
        if(!is_dir($imgDir)){
            mkdir($imgDir, 0777, true);
        }
        $imgname='JB'.time().rand(10000,99999).$imgname;
        if(move_uploaded_file($tmp,$imgDir.$imgname)){
            return $imgDir.$imgname;
        }else{
            return false;
        }
//
//        $tmp=$_FILES['image']['tmp_name'];
//        //临时目录
//        //文件移动目录
//        $imgDir ='uploads'.DS.date("Ymd",time()).DS;
//        if(!is_dir($imgDir)){
//              mkdir($imgDir, 0777, true);
//          }
//        $time=time();
//        $filename='QD'.$time.rand(10000,99999);
//        if(move_uploaded_file($tmp,$imgDir.$filename)){
//             return $imgDir.$filename.".jpg";
//        }else{
//            return false;
//        }
////
        //移动到服务器的上传目录 并且使用原文件名
//        =======================================================
//        $file = request()->file('image');
//            if($file==NULL){
//                return false;
//            }
//        // 移动到框架应用根目录/public/uploads/ 目录下
//        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
//    if($info){
//        return  $info->getSaveName();
//    }else{
//        // 上传失败获取错误信息
//        return  $file->getError();
//    }
//      =========================================================
//        dump($image);die;
//        二进制获取
//        if(empty())
//        $image=file_get_contents('php://input');
//        file_put_contents('aaa.txt',$ip.$image);die;
//        $imgDir ='uploads'.DS.date("Ymd",time()).DS;
//      //若果在Linux 需要给与文件夹权限
//          if(!is_dir($imgDir)){
//              mkdir($imgDir, 0777, true);
//          }
//        $time=time();
//        $logtime=date("Ymd/H:m:s",time());
//        $filename='QD'.$time.rand(10000,99999).".jpg";///要生成的图片名字
//        //
//        $xmlstr =file_get_contents('php://input');
//        if(empty($xmlstr))
//        {
//            file_put_contents('ErrorLog.txt',  '当前请求的IP:'.$ip.'当前请求时间:'.$logtime.'没有数据流传入'.PHP_EOL, FILE_APPEND);
//             return false;
//        }
//        file_put_contents('log.txt',  '当前请求的IP:'.$ip.'当前请求时间:'.$logtime.PHP_EOL,FILE_APPEND);
//        $jpg=$xmlstr;//得到post过来的二进制原始数据
//
//        $file=fopen('./'.$imgDir.$filename,"w");//打开文件准备写入
//        fwrite($file,$jpg);//写入
//        fclose($file);//关闭
//        $filePath = $imgDir.$filename;
//        if(!file_exists($filePath))
//        {
//            echo '添加图片失败';
//            exit();
//        }
//           return $filePath;
    }
    /**
     * [user_return_image description]
     * @param  [type] $imgname [description]
     * @param  [type] $tmp     [description]
     * @return [type]          [description]
     */
    public  function user_return_image($imgname, $tmp)
    {
        $imgDir ='uploads'.DS.'api'.DS.'user'.DS.date("Ymd",time()).DS;
        if(!is_dir($imgDir)){
            mkdir($imgDir, 0777, true);
        }
        $imgname='MJ'.time().rand(10000,99999).$imgname;
        if(move_uploaded_file($tmp,$imgDir.$imgname)){
            return $imgDir.$imgname;
        }else{
            return false;
        }
//      
    }
    //活体认证文件保
    public static function image1($imgname, $tmp)
    {
        $imgDir ='live'.DS.date("Ymd",time()).DS;
        if(!is_dir($imgDir)){
            mkdir($imgDir, 0777, true);
        }
        $imgname='MJ'.time().rand(10000,99999).$imgname;
        if(move_uploaded_file($tmp,$imgDir.$imgname)){
            return $imgDir.$imgname;
        }else{
            return false;
        }
    }
}