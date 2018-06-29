<?php
class UserAction extends BaseAction{
	protected $token;
	
	//登录验证方法和公共文件配置
	public function _initialize() {
		$this->loginAuth();
		$this->UserAuth();
		define('RES', THEME_PATH . 'common');
        define('STATICS', TMPL_PATH . 'static');
		$wecha = M('Wxuser')->field('wxname,weixin,wxid,headerpic')->where(array(
            'token' => session('token'),
            //'uid' => session('uid')
        ))->find();
		$this->assign('aaa', 'aaa123123');
        $this->assign('wecha', $wecha);
		$this->token = session('token');
        $this->assign('token', $this->token);
		//==========
		//vip权限控制部分
		$this->userinfo        = M('User_group')->where(array(
            'id' => session('gid')
        ))->find();
        $this->userGroup = $this->userinfo;
        $this->users           = M('Users')->where(array(
            //'id' => $_SESSION['uid']
        ))->find();
        $this->user      = $this->users;
        $this->assign('thisUser', $this->users);
        $this->assign('viptime', $this->users['viptime']);
		
		/* $this->wecha = M('Wxuser')->field('wxname,weixin,wxid,headerpic')->where(array(
            'token' => session('token'),
            'uid' => session('uid')
        ))->find();
        $this->assign('wecha', $this->wecha); */
		
		//==========
	}
	/**
	 * 登录验证
	 */
	protected function loginAuth(){
		//登录验证
		if(session("LOGIN_NAME") == null)
			$this->redirect("User/Index/login");
	}
	/**
	 * 一站到底答题
	 */
	protected function canUseFunction($funname) {
        $token_open = M('token_open')->field('queryname')->where(array('token' => $this->token))->find();
        if (C('agent_version') && $this->agentid) {
            $function = M('Agent_function')->where(array('funname' => $funname, 'agentid' => $this->agentid))->find();
        } else {
            $function = M('Function')->where(array('funname' => $funname))->find();
        } if (intval($this->user['gid']) < intval($function['gid']) || strpos($token_open['queryname'], $funname) === false) {
            $this->error('您还没有开启该模块的使用权,请到功能模块中添加', U('Function/index', array('token' => $this->token)));
        }
    }
	
	/**
	 * 账号访问限制
	 */
	protected function UserAuth(){
		if($_SESSION['uid']=='106' && (in_array(ACTION_NAME,array('index','myFinance','mySale','withdrawMoney','moneyDetail','cutIncome','myFinanceDeal','saleReward','saleReset','saleReport','withdrawReport','withdrawDeal','withdrawShow','infoReport','showFather','customer'))!=1 || MODULE_NAME != 'Distribution')){
			exit("抱歉，您没有权限访问该页面！");
		}
		if($_SESSION['uid']=='114' && (in_array(ACTION_NAME,array('orders','ordersReport','orderInfo'))!=1 || MODULE_NAME != 'Product')){
			exit("抱歉，您没有权限访问该页面！");
		}
	}
}