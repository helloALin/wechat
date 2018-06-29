<?php
function check_subscribe($token, $wecha_id){
	$result = M('wxuser_people')->where(array('token' => $token,'wecha_id' => $wecha_id))->getField('subscribe');
	if($result === null){
		$access_token = getAccessToken($token);
		if(!$access_token["status"]) {
			Log::write("check_subscribe:获取accesstoken失败：".$access_token['info'], Log::DEBUG, 3, LOG_PATH.'userInfo.log');
			return false;
		}
		$access_token = $access_token["info"];
	
		//获取用户信息
		Log::write("check_subscribe:获取用户基本信息openid：".$wecha_id, Log::DEBUG, 3, LOG_PATH.'userInfo.log');
		$info_url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$wecha_id.'&lang=zh_CN';
		$return_str = curlGet($info_url);
		Log::write("check_subscribe:获取用户基本信息接口返回：".$return_str, Log::DEBUG, 3, LOG_PATH.'userInfo.log');
		$result = json_decode($return_str, true);
		if($result['openid']){
			$result['token'] = $token;
			$result['wecha_id'] = $wecha_id;
			$result['create_time'] = time();
			M('wxuser_people')->add($result);
		}
	}
	return $result == 1 || $result['subscribe'] == 1;
}