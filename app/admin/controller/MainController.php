<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\Menu;

class MainController extends AdminBaseController
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     *  后台欢迎页
     */
    public function index()
    {
        $last_friday = strtotime('-1 friday', time());
        $where = array(
            'createtime'=> array('between',array($last_friday,time()))
        );
        $user_ids = Db::name('report')->where($where)->group('user_id')->column('user_id');

        $unsub_users = Db::name('user')->where(array('id'=>array('not in',$user_ids)))->field('id,user_nickname')->select();
        $this->assign('unsub_users',$unsub_users);


        return $this->fetch();
    }

    public function getuserlist(){
        $page = input('post.page');

        $count = Db::name('user')->where(array('user_type'=>2,'user_status'=>1))->count();

        $list = Db::name('user')->where(array('user_type'=>2,'user_status'=>1))
            ->limit($page*10,10)->field('id,user_nickname')->select()->toArray();
        if($list){
            $last_friday = strtotime('-1 friday', time());

            foreach ($list as $key => $value){
                $where = array(
                    'user_id'   => $value['id'],
                    'createtime'=> array('between',array($last_friday,time()))
                );
                $status = Db::name('report')->where($where)->count();

                $list[$key]['sub_status'] = $status?1:0;
            }
        }

        $this->assign('list',$list);
        $this->assign('totalpage',ceil($count/10));
        return $this->fetch('user_list');
    }

    public function userAlertHandle(){
        $type = input('post.type');
        if($type=='single'){
            $user_id = input('post.user_id');


            $email = Db::name('user')->where('id',$user_id)->value('user_email');
            if(!$email){
                $this->error('未找到该用户');
            }

            $alert_status = cache('alert_'.$user_id);
            if($alert_status){
                $this->error('一天内只能提醒一次');
            }

            $res = cmf_send_email($email,'你本周的周报尚未提交，请及时提交','请提交周报，地址：http://report.medp.cn');
            if($res){
                cache('alert_'.$user_id,1,array('expire'=>86400));
                $this->success('提醒成功');
                exit();
            }else{
                $this->error('邮件发送失败');
            }
        }elseif($type=='all'){

            $last_friday = strtotime('-1 friday', time());

            $where = array(
                'createtime'=> array('between',array($last_friday,time()))
            );

            $user_ids = Db::name('report')
                ->where($where)
                ->group('user_id')->column('user_id');
            $user_list = Db::name('user')
                ->where(array('user_type'=>2,'user_status'=>1,'id'=>array('not in'=>$user_ids)))
                ->field('id,user_email')->select()->toArray();
            if($user_list){
                foreach ($user_list as $key => $value){
                    $alert_status = cache('alert_'.$value['id']);
                    if(!$alert_status){
                        $res = cmf_send_email($value['user_email'],'你本周的周报尚未提交，请及时提交','请提交周报，地址：http://report.medp.cn');
                        if($res){
                            cache('alert_'.$value['id'],1,array('expire'=>86400));
                        }
                    }
                }
            }
            $this->success('提醒成功');
            exit();

        }

    }

}
