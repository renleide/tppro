<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\UserBaseController;
use think\Db;

class IndexController extends UserBaseController
{
    public function index()
    {

        $user_id = cmf_get_current_user_id();
        $last_friday = strtotime('-1 friday', time());
        $where = array(
            'user_id'   => $user_id,
            'createtime'=> array('between',array($last_friday,time()))
        );
        $count = Db::name('report')->where($where)->count();


        $this->assign('count',$count);
        return $this->fetch(':index');
    }

    public function dataPost(){
        $postdata = input('post.');
        $user_id = cmf_get_current_user_id();
        $valid = array(
            'cur_week'  => '请填写本周汇报',
            'next_week' => '请填写下周计划'
        );
        foreach ($valid as $key => $value){
            if(!$postdata[$key]){
                $this->error($value);
            }
        }

        $add_arr = array(
            'user_id'       => $user_id?$user_id:0,
            'cur_week'      => json_encode($postdata['cur_week']),
            'next_week'     => json_encode($postdata['next_week']),
            'suggest'       => json_encode($postdata['suggest']),
            'sub_ip'        => get_client_ip(0, true),
            'createtime'    => time(),
            'status'        => 0
        );

        if(cmf_is_mobile()){
            $add_arr['device'] = 'mobile';
        }else{
            $add_arr['device'] = 'pc';
        }

        $res = Db::name('report')->insert($add_arr);
        if($res){
            $this->success('提交成功');
        }else{
            $this->error('提交失败');
        }
    }

    public function getReportList(){
        $type = input('post.type');//type 0本周 1上周、
        $user_id = cmf_get_current_user_id();

        if($type==0){

            $last_friday = strtotime('-1 friday', time());
            $where = array(
                'user_id'   => $user_id,
                'createtime'=> array('between',array($last_friday,time()))
            );
            $list = Db::name('report')->where($where)->select();

        }elseif ($type == 1){
            $last_friday = strtotime('-2 friday', time());
            $end_friday = strtotime('-1 friday', time());
            $where = array(
                'user_id'   => $user_id,
                'createtime'=> array('between',array($last_friday,$end_friday))
            );
            $list = Db::name('report')->where($where)->select();
        }

        $html = '';
        if($list){
            foreach ($list as $key => $value){
                $html.= '<li class="list-group-item"><a class="a_tag" href="'.
                    url('Index/detail',array('id'=>$value['id'])).
                    '" target="_blank">周报_'.date('Y/m/d',$value['createtime']).'</a></li>';
            }
        }

        exit(json_encode(array('code'=>1,'html'=>$html)));
    }

    public function detail($id){
        $info = Db::name('report')->where('id',$id)->find();
        $info['cur_week'] = json_decode($info['cur_week'],true);
        $info['next_week'] = json_decode($info['next_week'],true);
        $info['suggest'] = json_decode($info['suggest'],true);
        $this->assign('info',$info);
        return $this->fetch(':detail');
    }
}
