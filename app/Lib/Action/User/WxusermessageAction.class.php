<?php

/**

 *互动微信墙 Written by leohoko

**/

class WxusermessageAction extends UserAction{
	public $next_openid;
	public function _initialize() {
		parent::_initialize();
		$token_open=M('token_open')->field('queryname')->where(array('token'=>session('token')))->find();
		if(!strpos($token_open['queryname'],'Wxusermessage')){
            $this->error('您还开启该模块的使用权,请到功能模块中添加',U('Function/index',array('token'=>session('token'),'id'=>session('wxid'))));
		}
	}
	
	public function index(){
	
		$where['ToUserName']=$this->wecha['wxid'];
		$order['id']='desc';
		$count=D('Wxuser_message')->where($where)->count();
		$page=new Page($count,20);
		$info=D('Wxuser_message')->where($where)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('info',$info);
		//print_r($info);
		$this->display();
	}
	public function answer(){
		$message=M('Wxuser_message')->where(array('id'=>$this->_get('id'),'ToUserName'=>$this->wecha['wxid']))->find();
		$this->assign('message',$message);
		$this->display();
	}
	public function wxuser(){
		$where['token']=session('token');
		$order['subscribe_time']='desc';
		$count=D('wecha_user')->where($where)->count();
		$page=new Page($count,20);
		$info=D('wecha_user')->where($where)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('info',$info);
		$this->display();
	}
	public function getuserlist(){
		$token = session('token');
		$access_token = getAccessToken($token);
		if($access_token["status"]){
			$access_token = $access_token["info"];
		}else{
			$this->error($access_token["info"],U('User/Wxusermessage/wxuser'));
		}
		
		$url_get="https://api.weixin.qq.com/cgi-bin/user/get?access_token=".$access_token."&next_openid=";
		$userlist=json_decode(curlGet($url_get),true);
		$j=ceil($userlist['total']/$userlist['count']);
		unset($userlist);
		for ($i=1;$i<=$j;$i++){ 
			$url_get="https://api.weixin.qq.com/cgi-bin/user/get?access_token=".$access_token."&next_openid=".$this->next_openid;
			$userlist=json_decode(curlGet($url_get),true);
			$this->next_openid=$userlist['next_openid'];
			$this->add($userlist['data']['openid'],$access_token);
		}
		$this->success('导入成功',U('User/Wxusermessage/wxuser'));
		
	}
	public function add($arr,$access_token){
		foreach ($arr as $v){
			$url_get="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$v."&lang=zh_CN";
			$userinfo=json_decode(curlGet($url_get));
			
			$data['token']=session('token');
			$data['nickname']=$userinfo->nickname;
			$data['sex']=$userinfo->sex;
			$data['city']=$userinfo->city;
			$data['province']=$userinfo->province;
			$data['country']=$userinfo->country;
			$data['headimgurl']=$userinfo->headimgurl;
			$data['subscribe_time']=$userinfo->subscribe_time;
			
			$count=M('wecha_user')->where(array('token'=>session('token'),'wecha_id'=>$v))->count();
			
			if($count>0){
				M('wecha_user')->where(array('token'=>session('token'),'wecha_id'=>$v))->save($data);
			}else{
				$data['wecha_id']=$v;
				M('wecha_user')->add($data);
			}
			unset($data);
			unset($userinfo);
		}
	}
	public function more(){
		$this->display();
	}
	public function del(){
		$where['id']=$this->_get('id','intval');
		$where['ToUserName']=$this->wecha['wxid'];
		if(D('wxuser_message')->where($where)->delete()){
			$this->success('操作成功',U('User/Wxusermessage/index'));
		}else{
			$this->error('操作失败',U('User/Wxusermessage/index'));
		}
	}
	public function post(){
		$message=M('Wxuser_message')->where(array('id'=>$this->_post('id'),'ToUserName'=>$this->wecha['wxid']))->find();
		

		$access_token = getAccessToken(session('token'));
		if($access_token["status"]){
			$data='{"touser":"'.$message['FromUserName'].'","msgtype":"text","text":{"content":"'.$this->_post('text').'"}}';
			$url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token["info"];
			if($this->api_send_message($url,$data) == false){
				$this->error('操作失败');
			}else{
				$this->success('操作成功');
			}
		}else{
			$this->error('操作失败'.$access_token["info"]);
		}
	}
	
	function api_send_message($url, $data){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$tmpInfo = curl_exec($ch);
		if (curl_errno($ch)) {
			return false;
		}else{

			return true;
		}
	}

}

?>