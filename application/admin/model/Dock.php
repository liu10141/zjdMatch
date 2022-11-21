<?php

namespace app\admin\model;

use think\Cache;
use think\Model;

/**
 * Created by PhpStorm.
 * User: MC
 * Date: 2021/9/19
 * Time: 17:46
 */
class Dock extends Model
{
    /**
     * 请求api接口
     * @param $url api地址
     * @param $post_data 请求数据
     * @param $isToken 是否需要token
     * @return array|mixed
     */
    public function interfaceDock($url, $data, $isToken = true)
    {
        if ($isToken && !isset($data['token'])) {
            $token = Cache::get('token');
            if (empty($token)) {

            }
            $post_data = array_merge($data, array('token' => $token));
        } else {
            $token = '';
            $post_data = $data;
        }
        //请求有人云api
        $post_url = config('API_ADDRESS') . $url;
        $dataRes = http_json_data($post_url, $post_data, $token);
        $dataRes = json_decode($dataRes, true);
        return $dataRes;
    }

    /**
     * 登录
     * 获取token缓存两小时
     */
    public function login($data)
    {
        $token = '';
        $post_data = $data;
        $post_url = config('API_ADDRESS') . '/user/login';
        $dataRes = http_json_data($post_url, $post_data, $token);
        $dataRes = json_decode($dataRes, true);
        return $dataRes;
    }

    /**
     * 获取设备列表
     */
    public function getDevs($data)
    {
        $res = $this->interfaceDock('/dev/getDevs', $data);
        return $res;
    }

    /**
     * 添加设备
     */
    public function addDevice($data)
    {
        $res = $this->interfaceDock('/dev/addDevice', $data);
        return $res;
    }

    /**
     * 校验SN
     */
    public function getDeviceTypeBySn($data)
    {
        $res = $this->interfaceDock('/vn/sys/param/getDeviceTypeBySn', $data);
        return $res;
    }

    /**
     * 编辑设备
     */
    public function editDevice($data)
    {
        $res = $this->interfaceDock('/dev/editDevice', $data);
        return $res;
    }

    /**
     * 删除设备
     */
    public function deleteDevices($data)
    {
        $res = $this->interfaceDock('/dev/deleteDevices', $data);
        return $res;
    }

    /**
     * 获取设备详情
     */
    public function getDevice($data)
    {
        $res = $this->interfaceDock('/dev/getDevice', $data);
        return $res;
    }

    /**
     * 获取项目列表
     */
    public function queryProjectList($data)
    {
        $res = $this->interfaceDock('/projectInfo/queryProjectList', $data);
        return $res;
    }

    /**
     * 获取项目分组列表
     */
    public function getDevGroups($data)
    {
        $res = $this->interfaceDock('/dev/getDevGroups', $data);
        return $res;
    }

    /**
     * 添加项目分组
     */
    public function addDevGroup($data)
    {
        $res = $this->interfaceDock('/dev/addDevGroup', $data);
        return $res;
    }

    /**
     * 获取项目分组详情
     */
    public function getDevGroup($data)
    {
        $res = $this->interfaceDock('/dev/getDevGroup', $data);
        return $res;
    }

    /**
     * 编辑项目分组
     */
    public function editDevGroup($data)
    {
        $res = $this->interfaceDock('/dev/editDevGroup', $data);
        return $res;
    }

    /**
     * 删除项目分组
     */
    public function deleteDevGroups($data)
    {
        $res = $this->interfaceDock('/dev/deleteDevGroups', $data);
        return $res;
    }

    /**
     * 获取项目设备模板
     */
    public function getDeviceTemplates($data)
    {
        $res = $this->interfaceDock('/dev/template/getDeviceTemplates', $data);
        return $res;
    }

    /**
     * 根据设备编号集合获取从机和数据点信息
     */
    public function getDataPointInfoByDevice($data)
    {
        $res = $this->interfaceDock('/datadic/getDataPointInfoByDevice', $data);
        return $res;
    }

    /**
     * 根据设备编号和设备模版ID获取模版组态画面
     */
    public function getConfigurations($data)
    {
        $res = $this->interfaceDock('/configuration/getConfigurations', $data);
        return $res;
    }

    /**
     * 获取资源容器列表
     */
    public function getProjects($data)
    {
        $res = $this->interfaceDock('/vn/projectinfo/getProjects', $data);
        return $res;
    }

    /**
     * 获取某个用户的设备列表
     */
    public function getDevsForVn($data)
    {
        $res = $this->interfaceDock('/vn/dev/getDevsForVn', $data);
        return $res;
    }

    /**
     * 获取子用户列表
     */
    public function getUsers($data)
    {
        $res = $this->interfaceDock('/vn/user/getUsers', $data);
        return $res;
    }

    /**
     * 获取用户信息
     */
    public function getUser($data)
    {
        $res = $this->interfaceDock('/user/getUser', $data);
        return $res;
    }

    /**
     * 获取用户关联角色
     */
    public function getUserRole($data)
    {
        $res = $this->interfaceDock('/role/getUserRole', $data);
        return $res;
    }

    /**
     * 查询用户创建的角色列表
     */
    public function getUserCreateRole($data)
    {
        $res = $this->interfaceDock('/role/getUserCreateRole', $data);
        return $res;
    }

    /**
     * 用户关联设备权限
     */
    public function permissionDevicesAndData($data)
    {
        $res = $this->interfaceDock('/vn/upms/permissionDevicesAndData', $data);
        return $res;
    }

    /**
     * 编辑用户角色
     */
    public function addUserRoleNexusDelOld($data)
    {
        $res = $this->interfaceDock('/role/addUserRoleNexusDelOld', $data);
        return $res;
    }

    /**
     * 添加子用户
     */
    public function regUser2($data)
    {
        $res = $this->interfaceDock('/user/regUser2', $data);
        return $res;
    }

    /**
     * 编辑用户信息
     */
    public function editUser($data)
    {
        $res = $this->interfaceDock('/user/editUser', $data);
        return $res;
    }

    /**
     * 删除子用户
     */
    public function deleteUsers($data)
    {
        $res = $this->interfaceDock('/user/deleteUsers', $data);
        return $res;
    }
}