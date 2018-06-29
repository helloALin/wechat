<?php
class IndexAction extends UserAction{
	//关注回复
	public function index(){
	
		$this->display();
	}
	public function resetpwd(){
		$uid=$this->_get('uid','intval');
		$code=$this->_get('code','trim');
		$rtime=$this->_get('resettime','intval');
		$info=M('Users')->find($uid);
		if( (md5($info['uid'].$info['password'].$info['email'])!==$code) || ($rtime<time()) ){
			$this->error('非法操作',U('Index/index'));
		}
		$this->assign('uid',$uid);
		$this->display();
	}
	public function success(){
		//p(session('uname'));
		$this->display();
	}
	// 用户登出
    public function logout() {
		session(null);
		session_destroy();
		unset($_SESSION);
        if(session('?'.C('USER_AUTH_KEY'))) {
            session(C('USER_AUTH_KEY'),null);
           
            //redirect(U('Home/Index/index'));
            //$this->success('退出登录！',U('Home/Index/index'));
            $this->redirect('Home/Index/index');
            
        }else {
            //$this->error('退出成功！',U('Home/Index/index'));
            //$this->error('退出成功！','Home/Index/index');
            $this->redirect('Home/Index/index');
        }
    }
}