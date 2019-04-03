<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\mobile\controller;

use app\user\model\UserModel;
use cmf\controller\HomeBaseController;
use think\Db;

class IndexController extends HomeBaseController
{

    public function _initialize()
    {
        parent::_initialize();
        $weixin = session('weixin_user');
        if(!$weixin){

            $weixin = $this->GetOpenid();
            session('weixin_user',$weixin);

        }

        $user = Db::name('user')->where('openid',$weixin['openid'])->find();

        $no_valid = array(
            'dobind',
            'bindwxuser'
        );
        if($user){
            session('user',$user);
            $this->assign('user',$user);
        }else{

            if(!in_array((strtolower(request()->action())),$no_valid)){
                $this->redirect(url('mobile/Index/bindWxUser'));
                exit();
            }

        }

    }

    /**
     * 前台用户首页(公开)
     */
    public function index()
    {

        return $this->fetch("");
    }

    /**
     * 前台ajax 判断用户登录状态接口
     */
    function isLogin()
    {
        if (cmf_is_user_login()) {
            $this->success("用户已登录",null,['user'=>cmf_get_current_user()]);
        } else {
            $this->error("此用户未登录!");
        }
    }

    /**
     * 退出登录
    */
    public function logout()
    {
        session("user", null);//只有前台用户退出
        return redirect($this->request->root() . "/");
    }

    public function bindWxUser(){
        $mtk = cmf_random_string(16);
        session('bind_mtk',$mtk);
        $this->assign('mtk',$mtk);
        return $this->fetch(':bind');
    }

    public function doBind(){
        $data = input('post.');
        $mtk = session('bind_mtk');
        if(!$data['mtk']||$data['mtk']!=$mtk){
            $this->bind_error('请求失败');
        }

        if (!cmf_captcha_check($data['captcha'])) {
            $this->bind_error(lang('CAPTCHA_NOT_RIGHT'));
        }

        $user = Db::name('user')->where('user_login',$data['username'])->find();

        if(!$user){
            $this->bind_error('账户不存在或者密码错误');
        }

        $password = $data['password'];
        $comparePasswordResult = cmf_compare_password($user['user_pass'], $password);
        if($comparePasswordResult){
            $weixin = session('weixin_user');
            $add_arr = array(
                'user_id'           => $user['id'],
                'last_login_time'   => time(),
                'create_time'       => time(),
                'status'            => 1,
                'nickname'          => $weixin['nickname'],
                'third_party'       => 'weixin',
                'app_id'            => 0,
                'last_login_ip'     => get_client_ip(),
                'access_token'      => 0,
                'openid_id'         => $weixin['openid'],
                'union_id'          => $weixin['union_id'],
                'subscribe'         => $weixin['subscribe']
            );
            $res = Db::name('third_party_user')->insert($add_arr);
            Db::name('user')->where('user_login',$data['username'])->update(array('openid'=>$weixin['openid']));

            $this->success('绑定成功');
        }else{
            $this->bind_error('账户不存在或者密码错误');
        }
    }

    private function bind_error($msg){
        $mtk = cmf_random_string(16);
        session('bind_mtk',$mtk);
        $this->error($msg,'',$mtk);
    }
}
