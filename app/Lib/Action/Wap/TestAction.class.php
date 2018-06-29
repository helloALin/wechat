<?php	
class TestAction extends Oauth2Action{
	public $token;
	public $wecha_id;
	public $product_model;
	public $product_cat_model;
	public $store_model;
	public $isDining;
	private $storeInfo;
	private $storeID;
	public function _initialize(){
		//parent::_initialize();
		//$this->wecha_id='oTRiCtzmS1oLd4CXpXD8L48M9O1Y';   //我的号
		$this->token=$this->_get('token');
	}
		
	public function index(){
		
		dump(getAccessToken($this->token));exit;
		$people=M('wxuser_people')->where(array('token' => $this->token,'wecha_id' => $this->wecha_id))->find();
		//p($people);
		if($people){
			//后来关注的,信息在wxuser_people表中

		}else{
			//1、之前关注的，信息没写入wxuser_people表2、没有关注
			$this->getUserInfo($this->token,$this->wecha_id);
		}
		
	}
	//获取用户信息
	public function getUserInfo($token,$wecha_id)
	{
		$access_token = getAccessToken($token);
		if($access_token["status"])
		{
			$access_token = $access_token["info"];
			Log::write("获取用户基本信息token：".$access_token, Log::DEBUG, 3, LOG_PATH.'userInfo.log');
			$info_url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$wecha_id.'&lang=zh_CN';
			$result=json_decode(curlGet($info_url));
			Log::write("获取用户基本信息接口返回：".json_encode($result), Log::DEBUG, 3, LOG_PATH.'userInfo.log');
			
	        if($result && count($result)>0 &&$result->subscribe==1)
			{
				$data['token']=$token;
				$data['wecha_id']=$wecha_id;
				$data['nickname']=$result->nickname;
				$data['sex']=$result->sex;
				$data['city']=$result->city;
				$data['country']=$result->country;
				$data['province']=$result->province;
				$data['language']=$result->language;
				//$data['headimgurl']=substr($result->headimgurl,0,(strlen($result->headimgurl))-2);
				$data['headimgurl']=$result->headimgurl;
				$data['subscribe_time']=$result->subscribe_time;
				$data['subscribe']=$result->subscribe;
				$data['create_time']=time();
				
				Log::write("添加用户信息".json_encode($data), Log::DEBUG, 3, LOG_PATH.'userInfo.log');
				
				M('wxuser_people')->add($data);
				return;
			}
		}
		else
		{
			Log::write("获取accesstoken失败：".$access_token['info'], Log::DEBUG, 3, LOG_PATH.'userInfo.log');
			return;
		}
	}	
}
?>