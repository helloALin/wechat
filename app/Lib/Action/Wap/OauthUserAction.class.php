<?php
class OauthUserAction extends Action
{
    
	public function getUserInfo(){
		$this->token = $_GET["token"];
		$weixinAppinfo = S('weixinAppinfo_' . $this->token);
		if (!$weixinAppinfo) {
			$weixinAppinfo = M('DiymenSet')->where(array('token' => $this->token))->find();
			if($weixinAppinfo)
				S('weixinAppinfo_' . $this->token, $weixinAppinfo, 86400);
			else
				exit("公众号信息不存在");
		}
		if (empty($_GET['code'])) {
			$this->redirectToWeixin($weixinAppinfo['appid'], 1, 'snsapi_userinfo');
		}else{
			$rt = $this->curlGet('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $weixinAppinfo['appid'] . '&secret=' . $weixinAppinfo['appsecret'] . '&code=' . $_GET['code'] . '&grant_type=authorization_code');
			//$rt='{"access_token":"OezXcEiiBSKSxW0eoylIeDVG5rha_NYMFoLK3y4mfXKNWNxRkwB7E8nxiGGM2Cm0CqHP1hLCnssj2Dr3FHcUidA-udXpSkqjlKZJwz19EmzRKORH3c_FCbaFJacyNgGSSEYAaNNqoiVSM_a-gFvV7Q","expires_in":7200,"refresh_token":"OezXcEiiBSKSxW0eoylIeDVG5rha_NYMFoLK3y4mfXKNWNxRkwB7E8nxiGGM2Cm0W7VpFIYb5a9OrtxKIh6JbCKRLekEwyHveADJnAldTy90bJDtAE0gfAWZ4Jv7cyC-rkCzFg0PJVMKFk4bjkfwYQ","openid":"oj1_Hjg4zjefORnyiFKMY_F-lRUA","scope":"snsapi_userinfo"}';
			$jsonrt = json_decode($rt, 1);
			Log::write("code: ".$_GET["code"]."  curl get accesstoken".$rt);
			//$this->wecha_id = $jsonrt['openid'];
			//session('wecha_id',$jsonrt['openid']);
			$user_info_json = $this->curlGet("https://api.weixin.qq.com/sns/userinfo?access_token=".$jsonrt["access_token"]."&openid=".$jsonrt["openid"]."&lang=zh_CN");
			$user_info = json_decode($user_info_json, 1);
			Log::write(dump($user_info, false));
			if(empty($user_info) || empty($user_info["openid"])){
				$retry_count = intval($this->_get('state',"trim"));
				Log::write("get user openId json: ".$rt." retry count: ".$retry_count);
				if(!$retry_count)
					$this->redirectToWeixin($weixinAppinfo['appid'], $retry_count + 1, 'snsapi_userinfo');
				else{
					exit("获取用户信息失败！");
				}
			}
			else{
				$where = array("wecha_id"=>$user_info["openid"],"token"=>$this->token);
				unset($user_info['openid']);
				unset($user_info['privilege']);
				if('false' !== M("store")->where($where)->save($user_info)){
					$this->redirect("Wap/Product/personalCenter", array("store_id"=>$_GET["store_id"],"token"=>$this->token));
				}else{
					Log::write(M("store")->getLastSql());
					$this->error("更新用户信息失败！");
				}
			}
			//$_SESSION["wxuser_name"]=$user_info["nickname"];
		}
		//$sceneInfo = $this->scene_model->where(array('token' => $this->token, 'id' => $id))->find();
		///$this->assign("sceneInfo", $sceneInfo);
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
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }
}
?>