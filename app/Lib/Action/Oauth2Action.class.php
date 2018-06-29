<?php

/*
 * 网页授权获取用户基本信息
 * and open the template in the editor.
 */
class Oauth2Action extends Action{
    public $wecha_id;
    protected function _initialize() {
        $this->token = $this->_get('token');
        $this->wxuser = S('wxuser_' . $this->token);
        if (empty($this->wxuser) || empty($this->wxuser['appid'])) {
            $this->wxuser = M('diymen_set')->where(array('token' => $this->token))->find();
			
            S('wxuser_' . $this->token, $this->wxuser);
        }
        if (!session('wecha_id_'.$this->token) && !isset($_GET['code'])) {
            $this->redirectToWeixin($this->wxuser['appid'], 1);
        } 
        if (isset($_GET['code'])) {
            $rt = $this->curlGet('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->wxuser['appid'] . '&secret=' . $this->wxuser['appsecret'] . '&code=' . $_GET['code'] . '&grant_type=authorization_code');
			Log::write("init code:".$_GET['code'].'response str: '. $rt);
            $jsonrt = json_decode($rt, 1);
            $this->wecha_id = $jsonrt['openid'];
            session('wecha_id_'.$this->token,$jsonrt['openid']);
            $retry_count = (int) $_GET['state'];
            if(empty($jsonrt["openid"])){
	            Log::write("get user openId json: ".$rt." retry count: ".$retry_count);
	            if($retry_count > 0 && $retry_count < 4)
            		$this->redirectToWeixin($this->wxuser['appid'], $retry_count + 1);
	            else{
	            	echo "获取用户信息失败！";exit;
	            }
            }
        } else {
            $this->wecha_id = session('wecha_id_'.$this->token);
        }
    }
    
    /**
     * 重定向到微信Oauth2，获取用户openId
     * 默认使用snsapi_base获取用户openid
     * @param $scope='snsapi_base'  获取用户详细信息使用：snsapi_userinfo
     */
    protected function redirectToWeixin($appid,$state='a', $scope='snsapi_base'){
    	$customeUrl = urlencode(C('site_url'). $_SERVER['REQUEST_URI']);
    	//$scope = 'snsapi_userinfo';
    	//$scope = 'snsapi_base';
    	$oauthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=#{appid}&redirect_uri=#{redirecturl}&response_type=code&scope=#{scope}&state=#{state}#wechat_redirect';
		Log::write("redirect url: ".str_replace(array("#{appid}", "#{redirecturl}", "#{scope}", "#{state}"), 
    			array($appid, $customeUrl, $scope, $state), $oauthUrl));
    	header('Location:' . str_replace(array("#{appid}", "#{redirecturl}", "#{scope}", "#{state}"), 
    			array($appid, $customeUrl, $scope, $state), $oauthUrl));
    	exit;
    }
    
    public function curlGet($url) {
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);   //设置超时时间 5秒
        $temp = curl_exec($ch);
        curl_close($ch);
        return $temp;
    }
}
?>
