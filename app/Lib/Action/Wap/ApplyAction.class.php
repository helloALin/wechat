<?php
class ApplyAction extends BaseAction{
	//报名表
	public function index(){
		exit;
		//$apply=D('apply_wap');
		$token=$_GET['token'];
		$this->assign('token',$token);
		$apply=D('wechat_apply');
		//报名的人数
		$cnt=$apply->count();
		//报名人的手机,报名时间
		$info=$apply->field('tel,time')->order('id desc')->select();
		//手机号码中间用星号替换
		foreach($info as $k => &$v){
		$v["tel"]=preg_replace("/(1\d{1,3})\d\d\d\d(\d{4,4})/", "\$1****\$2", $v["tel"]);
		}
		//============
		$start='2014年10月20日'; //报名开始时间
		$day=24*60*60;
		$interval=7;//间隔时间
		$end='2014年10月27日'; //报名开始时间
		$pattern='/(\d+)/'; 
		preg_match_all($pattern,$start,$matches); 
		$timepre=mktime(0,0,0,$matches[0][1],$matches[0][2],$matches[0][0]); 
		$timenow=time(); 
		$time=$timenow-$timepre;
		if($time < $day*$interval ){
			//还在七天之内,可以报名
			$status=1;
		}
		else{
			//七天之外,不能报名
			$status=0;
		}
		$this->assign('status',$status);
		//============
		//获取用户的wechat_id
		/* $api=M('Diymen_set')->where(array('token'=>$_GET['token']))->find();
		$customeUrl = C('site_url') . $_SERVER['REQUEST_URI'];
		$scope = 'snsapi_base';
		if (!isset($_GET['code'])){
		$oauthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $api['appid'] . '&redirect_uri=' . urlencode($customeUrl) . '&response_type=code&scope=' . $scope . '&state=oauth#wechat_redirect';
		header('Location:' . $oauthUrl);
		}
		if (isset($_GET['code'])){
			$url_get='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$api['appid'].'&secret='.$api['appsecret'].'&code='.$_GET['code'].'&grant_type=authorization_code';
			$json=json_decode($this->curlGet($url_get));
			
		}
		$this->assign('wecha_id',$json->openid); */
		//===========
		$this->assign('cnt',$cnt);
		$this->assign('info',$info);		
		$this->display();
	}
	public function nanShan(){
		//$apply=D('apply_wap');
		$token=$_GET['token'];
		$this->assign('token',$token);
		$apply=D('wechat_apply');
		//报名的人数
		$cnt=$apply->where(array('token'=>$token))->count();
		//报名人的手机,报名时间
		$info=$apply->field('tel,time')->order('id desc')->where(array('token'=>$token))->select();
		//手机号码中间用星号替换
		foreach($info as $k => &$v){
		$v["tel"]=preg_replace("/(1\d{1,3})\d\d\d\d(\d{4,4})/", "\$1****\$2", $v["tel"]);
		}
		//============
		$start='2014年10月20日'; //报名开始时间
		$day=24*60*60;
		$interval=7;//间隔时间
		$end='2014年10月27日'; //报名开始时间
		$pattern='/(\d+)/'; 
		preg_match_all($pattern,$start,$matches); 
		$timepre=mktime(0,0,0,$matches[0][1],$matches[0][2],$matches[0][0]); 
		$timenow=time(); 
		$time=$timenow-$timepre;
		if($time < $day*$interval ){
			//还在七天之内,可以报名
			$status=1;
		}
		else{
			//七天之外,不能报名
			$status=0;
		}
		$this->assign('status',$status);		
		$this->assign('cnt',$cnt);
		$this->assign('info',$info);		
		$this->display();
	}
	public function addApply(){
		//p(session('token'));
		//p(M('Wedding_info_copy'));
		//$token=$this->_get('token');
		//echo $token;
		
		$data=array();		
		$data['token'] 		= $this->_post('token');
		$data['name'] = $this->_post('userName');
		$data['tel'] = $this->_post('tel');
		$data['duty'] = $this->_post('duty');
		//$data['count'] = $this->_post('count');
		//$data['content'] = $this->_post('content');
		//$data['type'] = 1;
		$data['create_time'] = time(); 
		$result=M('wechat_apply')->add($data);
		
		if($result){
			echo'提交成功，客服会尽快联系您！';
			exit;
		}else{
			echo'提交失败';
		}			
	}
	public function testshow(){
		$this->display();
	}	
}