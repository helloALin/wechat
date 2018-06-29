<?php

function isAndroid() {
	if (strstr($_SERVER['HTTP_USER_AGENT'], 'Android')) {
		return 1;
	}
	return 0;
}
// 制作一个调试输出函数
function p($msg, $color = 'green') {
	echo "<pre style='color:" . $color . "'>";
	var_dump($msg);
	echo "</pre>";
}

	/**
	 * 使用curl进行get请求
	 * @param string $url	请求地址
	 * @return string 请求相应内容
	 */
	function curlGet($url){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$htmlInfo = curl_exec($ch);
		$errorInfo = curl_error($ch);
		if(!empty($errorInfo)){
			Log::record("\nurl: ".$url, Log::INFO);
			Log::record("error_info: ".$errorInfo, Log::INFO);
			Log::save(Log::FILE, LOG_PATH.'http_request.log');
		}
		curl_close($ch);
		return $htmlInfo;
	}
	
	/**
	 * 使用curl进行post请求
	 * @param string $url	请求地址
	 * @param array $data	请求数据
	 * @return string 请求相应内容
	 */
	function curlPost($url, $data){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		//curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$htmlInfo = curl_exec($ch);
		$errorInfo = curl_error($ch);
		if(!empty($errorInfo)){
			Log::record("\nurl: ".$url." data: ".json_encode($data), Log::INFO);
			Log::record("error_info: ".curl_error($ch), Log::INFO);
			Log::save(Log::FILE, LOG_PATH.'http_request.log');
		}
		curl_close($ch);
		return $htmlInfo;
	}

	/**
	 * 获取accesstoken;
	 * @return array {status: 0|1, info: 'ff'}结果和信息
	 */
	function getAccessToken($token, $refresh = false) {
		$result = array("status"=> 0, "info"=>"必须先填写【AppId】【 AppSecret】");
		if(empty($token)){
			$result["info"] = "token 不能为空";
			return $result;
		}
		$access_token = S("ACCESS_TOKEN_".$token);
		if(empty($access_token) || $refresh){
			$api = M('Diymen_set')->where(array ('token' => $token))->find();
			if (!empty($api) && !empty($api['appid']) && !empty($api['appsecret'])) {
				$url_get = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $api['appid'] . '&secret=' . $api['appsecret'];
				$info = curlGet($url_get);
				$json = json_decode($info);
				if($json && $json->access_token){
					S('ACCESS_TOKEN_'.$token, $json->access_token, 7000);	//默认超时时间7000s
					S('APP_ID_'.$token, $api['appid'], 9000);	//默认超时时间9000s
					$result['status'] 	= 1;
					$result['info'] 	= $json->access_token;
					Log::write('获取accessToken：'.$json->access_token);
				}else{
					Log::write('获取accessToken失败：'.$info);
					$result['info'] = '获取accessToken失败，请确认填写的APPID和APPSECRET是否正确';
				}
			}
		}else{
			$result["status"] = 1;
			$result["info"] = $access_token;
		}
		return $result;
	}
	
	/**
	 * 获取JS-SDK的基本config参数
	 * @param string	$token		token
	 * @param string 	$url		展示地址url
	 * @param array 	$jsApiList	授权api列表
	 * @param boolean 	$isEncode	是否转为json
	 * @return array config	{appId:'',timestamp:,nonceStr:'',signature:'',jsApiList:[]}
	 */
	function getWXJSConfig($token, $url='', $jsApiList = '', $isEncode = false){
		if(empty($jsApiList) || !is_array($jsApiList)) $jsApiList = array();
		$result = array('timestamp'=>time(), 'nonceStr'=>createNonceStr(), 'jsApiList'=>$jsApiList);
/**		if(APP_DEBUG) {		//测试使用
			$token = 'jpxqwe1405492821';
			$result['debug'] = true;
		}*/
		$access_token = getAccessToken($token);
		$appId = S('APP_ID_'.$token);
		if(empty($appId)){
			$appId = M('Diymen_set')->where('token='.$token)->getField("appid");
		}
		if(empty($url)){
			// 根据SERVER获取url.
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
			$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		}
		if($access_token['status'] && $appId){
			$ticket = S('JSAPI_TICKET_'.$token);
			if(empty($ticket)){
				$ticket_url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token='.$access_token['info'];
				$ticket_json= curlGet($ticket_url);
				$ticket_obj	= json_decode($ticket_json);
				if($ticket_obj && $ticket_obj->errcode === 0){
					S('JSAPI_TICKET_'.$token, $ticket_obj->ticket, 7000);	//默认超时时间7000s
					$ticket = $ticket_obj->ticket;
				}else{
					Log::write('获取jsapi_ticket失败：'.$ticket_json);
				}
			}
			if($ticket){
				$signature = 'jsapi_ticket='.$ticket.'&noncestr='.$result['nonceStr']
							.'&timestamp='.$result['timestamp'].'&url='.$url;
				$result['appId']	= $appId;
				$result['signature']= sha1($signature);
				if(APP_DEBUG)
					Log::write($token.'--- get ticket signature: '.$signature);
			}
		}
		return $isEncode ? json_encode($result) : $result;
	}
	
	/**
	 * 含中文对象转json字符串，保留中文字符不变成unicode
	 * php5.3及更早版本使用，5.4开始可以使用JSON_UNESCAPED_UNICODE选项实现相同效果
	 * @param fixed $value
	 * @param string $opetion
	 */
	function json_encode_forCH($value, $opetions = null){
		return preg_replace_callback('/\\\\u([0-9a-f]{4})/i',
				create_function('$matches', 'return iconv("UCS-2BE", "UTF-8", pack("H*", $matches[1]));'),
				json_encode($value, $opetions));
	}
	
	/**
	 * 生成随机码，默认生成8位
	 * @param number $length
	 * @return string
	 */
	function createNonceStr($length = 8) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}
	
	/**
	 * 链接地址添加域名前缀
	 */
	function addSiteUrl($url){
		$url = trim($url);
		if(empty($url))
			return $url;
		if(preg_match('/^http[s]?\:\/\//i', $url))
			return $url;
		else if(preg_match('/^\//', $url))
			return C('site_url').$url;
		else 
			return C('site_url').'/'.$url;
	}
?>