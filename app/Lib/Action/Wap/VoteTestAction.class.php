<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class VoteTestAction extends Oauth2Action{
	public function _initialize(){	
		parent::_initialize();
	}
	public function vote(){
		$access_token = getAccessToken($_GET['token']);
		if($access_token["status"])
		{
			$access_token = $access_token["info"];
			$info_url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$this->wecha_id.'&lang=zh_CN';
			$result=json_decode(curlGet($info_url));
		    if($result && count($result)>0)
			{
				$encrypted = file_get_contents('http://121.8.157.221:8090/chinapost/IServAction!jiami.do?openid='.$result->openid.'&subscribe='.$result->subscribe.'&token=174830');
				//取得密文跳转至投票链接
				header('Location:http://121.8.157.221:8090/chinapost/VoteWapAction!voteIndex.do?voteid=175656&hash_code='.urlencode($encrypted));
			 }
		}else{
			Log::write("获取accesstoken失败：".$access_token['info'], Log::DEBUG, 3, LOG_PATH.'userInfo.log');
			echo '获取用户信息失败！';
			return;
		}
	}
	
	
}
?>
