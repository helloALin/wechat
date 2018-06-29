<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class BoxAction extends BaseAction{
    
    public function index(){
        $db = M('box');
        //是否已经有领取过的盒子
        $count = M('box_get')->where(array('token'=>$_GET['token'],'wecha_id'=>$_GET['wecha_id'],'isget'=>array('eq',0),'pid'=>$_GET['id']))->count();
        if($count>0){
            $this->success('您已经领取过',U('Box/myPrize',array('token'=>$_GET['token'],'wecha_id'=>$_GET['wecha_id'],'id'=>$_GET['id'])));
            exit;
        }
        
        //访问数加1
        $db->where(array('token'=>$_GET['token'],'id'=>$_GET['id']))->setInc('joinnum');
        $info = $db->where(array('token'=>$_GET['token'],'id'=>$_GET['id']))->find();
        $this->assign('info', $info);
        
        //活动时间结束或者关闭状态
        if(strtotime($info['enddate'])<time() || $info['status']==0){
            $this->assign('isover', 1);
        }
        //查询每人限领数量
        $pernums = M('box_get')->where(array('token'=>$_GET['token'],'wecha_id'=>$_GET['wecha_id'],'pid'=>$_GET['id']))->count();
        if($info['pernums'] == 0){//无限次领取
            $this->assign('pernums', 10000);
        }else{
            $this->assign('pernums', $info['pernums']-$pernums);
        }
        //算出今天还有几次可以领
        $now = time();
        $year        = date('Y', $now);
        $month       = date('m', $now);
        $day         = date('d', $now);
        $firstSecond = mktime(0, 0, 0, $month, $day, $year);
        $lastSecond  = mktime(23, 59, 59, $month, $day, $year);
        $box_get = M('box_get');
        $num = $box_get->where(array('token'=>$_GET['token'],'wecha_id'=>$_GET['wecha_id'],'time'=>array(array('GT',$firstSecond),array('LT',$lastSecond),'and')))->count();
        if($info['daynums'] == 0){//无限次领取
            $this->assign('num', 10000);
        }else{            
            if($info['daynums']==$num){
                $this->assign('num', 0);
            }else{
                $this->assign('num', $info['daynums']-$num);
            }
        }
		
		$config = getWXJSConfig($_GET['token']);
		//$config['debug']=true;  //调试模式
		$config['jsApiList']=array('onMenuShareTimeline','onMenuShareAppMessage');
		$this->assign("config", json_encode($config));
        
        //分享的链接
        $api=M('Diymen_set')->where(array('token'=>$_GET['token']))->find();
        $redirect_url = C(site_url)."/index.php?g=Wap&m=Box&a=indexShare&id=".$_GET['id']."&token=".$_GET['token'];
        $url_get = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$api['appid']."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=snsapi_base&state=0#wechat_redirect";
        $this->assign("shareurl", $url_get);
        
        $this->display();
    }
    
    public function getPrize(){
        $db = M('box');
        $info = $db->where(array('token'=>$_GET['token'],'id'=>$_GET['id']))->find();
        $this->assign('info', $info);
        
        //获奖名单
        $prizeList = M('box_get')->where(array('token'=>$_GET['token'],'isshare'=>array('neq',0),'pid'=>$_GET['id']))->select();
        $this->assign('prizeList', $prizeList);
       
		$config = getWXJSConfig($_GET['token']);
		$config['jsApiList']=array('onMenuShareTimeline','onMenuShareAppMessage');
		$this->assign("config", json_encode($config));
		
        //分享的链接
        $api=M('Diymen_set')->where(array('token'=>$_GET['token']))->find();
        $redirect_url = C(site_url)."/index.php?g=Wap&m=Box&a=indexShare&id=".$_GET['id']."&token=".$_GET['token'];
        $url_get = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$api['appid']."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=snsapi_base&state=0#wechat_redirect";
        $this->assign("shareurl", $url_get);
         
        $this->display();
    }

    public function send(){
        $db = M('box_get');
        $_POST['time']=time();
        
        $info = M('box')->where(array('token'=>$_POST['token'],'id'=>$_POST['pid']))->find();
        switch (intval($_POST['info-prize']))
        {case 2:$_POST['boxname'] = $info['boxname2'];
                break;
        case 3:$_POST['boxname'] = $info['boxname3'];
                break;
         default:$_POST['boxname'] = $info['boxname'];
                break;
            
        }
        //有效参与数加1
        M('box')->where(array('token'=>$_POST['token'],'id'=>$_POST['pid']))->setInc('validnum');
        
        $id = $db->add($_POST);
        if($id){            
            echo '{"errno":"ok","path":"/index.php?g=Wap&m=Box&a=my&token='.$_POST['token'].'&wecha_id='.$_POST['wecha_id'].'&id='.$_POST['pid'].'&prizeid='.$_POST['info-prize'].'&box_getid='.$id.'"}';
        }else{
            echo '{"errno":"no","error":"领取失败"}';
        }
    }
    
    public function my(){
        $db = M('box');
        $info = $db->where(array('token'=>$_GET['token'],'id'=>$_GET['id']))->find();
        switch (intval($_GET['prizeid']))
        {case 2:$this->assign('box_url', $info['box_url2']);
                $this->assign('boxpeople', $info['boxpeople2']);
                break;
         case 3:$this->assign('box_url', $info['box_url3']);
                $this->assign('boxpeople', $info['boxpeople3']);
                break;
         default:$this->assign('box_url', $info['box_url']);
                $this->assign('boxpeople', $info['boxpeople']);
                break;
            
        }
        $this->assign('info', $info);
		
		$config = getWXJSConfig($_GET['token']);
		$config['jsApiList']=array('onMenuShareTimeline','onMenuShareAppMessage');
		$this->assign("config", json_encode($config));
        
         //分享的链接
        $api=M('Diymen_set')->where(array('token'=>$_GET['token']))->find();
        $redirect_url = C(site_url)."/index.php?g=Wap&m=Box&a=myShare&activityid=".$_GET['id']."&token=".$_GET['token']."&wecha_id=".$_GET['wecha_id']."&box_getid=".$_GET['box_getid'];
        $url_get = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$api['appid']."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=snsapi_base&state=0#wechat_redirect";
        $this->assign("shareurl", $url_get);
        
        $this->display();
    }
    
    public function myPrize(){
        
        //判断是否有关注公众账号
        $access_token = getAccessToken($_GET['token']);
        if($access_token["status"]){        	
        	$url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token['info'].'&openid='.$_GET['wecha_id'].'&lang=zh_CN';
        	$jsondata = json_decode($this->curlGet($url));
        	$this->assign('subscribe', $jsondata->subscribe);
        }else{
			Log::write("Wap/BoxAction.myPrize - getAccessToken:".$access_token["info"]);
        }
         
        $db = M('box');
        $info = $db->where(array('token'=>$_GET['token'],'id'=>$_GET['id']))->find();
        $this->assign('info', $info);
        //最新一次领取的盒子
        $lastPrize = M('box_get')->where(array('token'=>$_GET['token'],'wecha_id'=>$_GET['wecha_id'],'isget'=>array('eq',0),'pid'=>$_GET['id']))->order('time desc')->limit(0,1)->select();

        if($lastPrize){
            switch (intval($lastPrize[0]['info-prize']))
            {case 2:$this->assign('box_url', $info['box_url2']);
                    $this->assign('award_pic', $info['award2_pic']);
                    $this->assign('boxpeople', $info['boxpeople2']);
                    break;
             case 3:$this->assign('box_url', $info['box_url3']);
                    $this->assign('award_pic', $info['award3_pic']);
                    $this->assign('boxpeople', $info['boxpeople3']);
                    break;
             default:$this->assign('box_url', $info['box_url']);
                    $this->assign('award_pic', $info['award_pic']);
                    $this->assign('boxpeople', $info['boxpeople']);
                    break;

            }
            $this->assign('lastPrize', $lastPrize[0]);
			
			$config = getWXJSConfig($_GET['token']);
			$config['jsApiList']=array('onMenuShareTimeline','onMenuShareAppMessage');
			$this->assign("config", json_encode($config));
            
            //分享的链接
            $api=M('Diymen_set')->where(array('token'=>$_GET['token']))->find();
            $redirect_url = C(site_url)."/index.php?g=Wap&m=Box&a=myShare&activityid=".$_GET['id']."&token=".$_GET['token']."&wecha_id=".$_GET['wecha_id']."&box_getid=".$lastPrize[0]['id'];
            $url_get = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$api['appid']."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=snsapi_base&state=0#wechat_redirect";
            $this->assign("shareurl", $url_get);
        
            $this->display();
        }else{
            redirect("index.php?g=Wap&m=Box&a=index&id=".$_GET['id']."&token=".$_GET['token']."&wecha_id=".$_GET['wecha_id']);
        }
    }

    public function addShare(){
        $db = M('box');
        //分享数加1
        $db->where(array('token'=>$_GET['token'],'id'=>$_GET['id']))->setInc('sharenum');
    }
    
    //主页分享获取点击者的openid
    public function indexShare(){
        if (isset($_GET['code'])){
            $api=M('Diymen_set')->where(array('token'=>$_GET['token']))->find();
            $url_get='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$api['appid'].'&secret='.$api['appsecret'].'&code='.$_GET['code'].'&grant_type=authorization_code';
            $json=json_decode($this->curlGet($url_get));        
            redirect("index.php?g=Wap&m=Box&a=getPrize&id=".$_GET['id']."&token=".$_GET['token']."&wecha_id=".$json->openid);
        }else{
            echo "NO CODE";
        }
    }
    
    //找朋友拆礼盒页面分享获取点击者的openid及处理
    public function myShare(){
        if (isset($_GET['code'])){
            $api=M('Diymen_set')->where(array('token'=>$_GET['token']))->find();
            $url_get='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$api['appid'].'&secret='.$api['appsecret'].'&code='.$_GET['code'].'&grant_type=authorization_code';
            $json=json_decode($this->curlGet($url_get));
            redirect("index.php?g=Wap&m=Box&a=openPrize&activityid=".$_GET['activityid']."&token=".$_GET['token']."&wecha_id=".$_GET['wecha_id']."&openid=".$json->openid."&box_getid=".$_GET['box_getid']);
        }else{
            echo "NO CODE";
        }
    }
    
    //朋友打开分享链接的操作处理
     public function openPrize(){
		 
		 if($_GET['wecha_id']==$_GET['openid']){
			 redirect("index.php?g=Wap&m=Box&a=myPrize&id=".$_GET['activityid']."&token=".$_GET['token']."&wecha_id=".$_GET['wecha_id']);
			 exit;
		 }
         $box = M('box')->where(array('token'=>$_GET['token'],'id'=>$_GET['activityid']))->find();
         $box_get = M('box_get')->where(array('token'=>$_GET['token'],'id'=>$_GET['box_getid']))->find();
         $box_open = M('box_open')->where(array('token'=>$_GET['token'],'wecha_id'=>$_GET['wecha_id'],'activityid'=>$_GET['activityid'],'openid'=>$_GET['openid'],'box_getid'=>$_GET['box_getid']))->find();
         
         switch (intval($box_get['info-prize'])){
             case 2:$people = $box['boxpeople2'];
                 $this->assign('box_url', substr($box['box_url2'],-5,1));
                 break;
             case 3:$people = $box['boxpeople3']; 
                 $this->assign('box_url', substr($box['box_url3'],-5,1));
                 break;
             default: $people = $box['boxpeople'];  
                 $this->assign('box_url', substr($box['box_url'],-5,1));
         }
         //type=0表示还差几个小伙伴拆就可以打开礼盒share
         $type = 0;
         $this->assign('have', $box_get['extnum']+1);//第几个拆
         $this->assign('rest', $people-$box_get['extnum']-1);//还差几个拆
         //type=1表示该礼盒已经被成功打开啦done
         if($box_get['isget'] == 1){
             $type = 1;
         }
         //type=2表示你是第一个帮我拆礼盒的朋友first
         else if($box_get['extnum'] == 0){
             $type = 2;
         }
         //type=3表示你是最后一个帮我拆礼盒的朋友last
         else if($box_get['extnum'] == ($people-1)){
             $type = 3;
         }
         //type=4表示你已经拆过这个礼盒opened
         if($box_open){
             $type = 4;
         }
         $this->assign('type', $type);
         $this->assign('box', $box);
         $this->assign('box_get', $box_get);
		 
		$config = getWXJSConfig($_GET['token']);
		$config['jsApiList']=array('onMenuShareTimeline','onMenuShareAppMessage');
		$this->assign("config", json_encode($config));
         
          //分享的链接
        $api=M('Diymen_set')->where(array('token'=>$_GET['token']))->find();
        $redirect_url = C(site_url)."/index.php?g=Wap&m=Box&a=myShare&activityid=".$_GET['activityid']."&token=".$_GET['token']."&wecha_id=".$_GET['wecha_id']."&box_getid=".$_GET['box_getid'];
        $url_get = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$api['appid']."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=snsapi_base&state=0#wechat_redirect";
        $this->assign("shareurl", $url_get);
         
         $this->display();
     }
     
     public function getAward(){
         //添加到帮拆礼盒关系表tp_box_open表
            $map = array();
            $map['token']=$_GET['token'];
            $map['wecha_id']=$_GET['wecha_id'];
            $map['openid']=$_GET['openid'];
            $map['box_getid']=$_GET['box_getid'];
            $map['activityid']=$_GET['activityid'];
            $map['time']=time();
            $map['openth']=$_GET['openth'];
			$box_open = M('box_open')->where(array('token'=>$_GET['token'],'wecha_id'=>$_GET['wecha_id'],'activityid'=>$_GET['activityid'],'openid'=>$_GET['openid'],'box_getid'=>$_GET['box_getid']))->find();
			if(empty($box_open)){
				M('box_open')->add($map);
				//拆礼盒朋友数加1
				M('box_get')->where(array('token'=>$_GET['token'],'id'=>$_GET['box_getid']))->setInc('extnum');
			}           
            redirect("index.php?g=Wap&m=Box&a=openPrize&activityid=".$_GET['activityid']."&token=".$_GET['token']."&wecha_id=".$_GET['wecha_id']."&openid=".$_GET['openid']."&box_getid=".$_GET['box_getid']);
     }
     
     public function openBox(){       
         $prize= array();
         $box_get = M('box_get')->where(array('id'=>$_POST['id'],'token'=>$_POST['token']))->find();
         $box = M('box')->where(array('id'=>$_POST['pid'],'token'=>$_POST['token']))->find();
         if($box_get['sn']==''){
          switch (intval($box_get['info-prize']))
            {case 2:$num = $box['awardnums2'];
                    $awardnums = 'awardnums2';
                    $this->assign('box_url', substr($box['box_url2'],-5,1));
                    $this->assign('awards', $box['awardname2']);
                    $prize['boxname']=$box['boxname2'];
                    $prize['awardname']=$box['awardname2'];
                    $prize['award_pic']=$box['award2_pic'];
                    $prize['awardrule']=$box['awardrule2'];
                    $prize['starttime']=$box['starttime2'];
                    $prize['endtime']=$box['endtime2'];
                    $prize['awardnums'] = $box['awardnums2'];
                    break;
             case 3:$num = $box['awardnums3'];
                    $awardnums = 'awardnums3';
                    $this->assign('box_url', substr($box['box_url3'],-5,1));
                    $this->assign('awards', $box['awardname3']);
                    $prize['boxname']=$box['boxname3'];
                    $prize['awardname']=$box['awardname3'];
                    $prize['award_pic']=$box['award3_pic'];
                    $prize['awardrule']=$box['awardrule3'];
                    $prize['starttime']=$box['starttime3'];
                    $prize['endtime']=$box['endtime3'];
                    $prize['awardnums'] = $box['awardnums3'];
                    break;
             default:$num = $box['awardnums'];
                     $awardnums = 'awardnums';
                     $this->assign('box_url', substr($box['box_url'],-5,1));
                     $this->assign('awards', $box['awardname']);
                     $prize['boxname']=$box['boxname'];
                    $prize['awardname']=$box['awardname'];
                    $prize['award_pic']=$box['award_pic'];
                    $prize['awardrule']=$box['awardrule'];
                    $prize['starttime']=$box['starttime'];
                    $prize['endtime']=$box['endtime'];
                    $prize['awardnums'] = $box['awardnums'];
            }
          if($num>0){
              
                    $randLength=8;
                    $chars='abcdefghijklmnopqrstuvwxyz0123456789';
                    $len=strlen($chars);
                    $randStr='';
                    for ($i=0;$i<$randLength;$i++){
                            $randStr.=$chars[rand(0,$len-1)];
                    }
              //100%中奖
              if($box['allpeople'] == 1){
                  M('box_get')->where(array('id'=>$_POST['id'],'token'=>$_POST['token']))->save(array('isshare'=>1,'isget'=>1,'sn'=>$randStr,'time'=>time()));
                  M('box')->where(array('id'=>$_POST['pid'],'token'=>$_POST['token']))->setDec($awardnums);
                  $this->assign('isget', 1);

                  //获奖之后微信推送消息
                  $content = "恭喜".$box_get['info-name']."获得".$prize['awardname']."\r\n奖品使用规则：".$prize['awardrule']."\r\n兑奖码：".$randStr."\r\n兑奖时间："
                          .$prize['starttime']."-".$prize['endtime']."\r\n抓紧时间兑奖吧！";

                  $access_token = getAccessToken($_POST['token']);
                  if($access_token["status"]){		
	                  $data='{"touser":"'.$_POST['wecha_id'].'","msgtype":"text","text":{"content":"'.$content.'"}}';
	                  $url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token['info'];
	                  $this->api_notice_increment($url,$data);
                  }else{
					  Log::write("Wap/BoxAction.openBox - getAccessToken:".$access_token["info"]);
			      }
                }else{
                    $result = $this->get_rand($prize['awardnums'], intval($box['allpeople'] * intval($box['pernums'])));
                    if($result==1){
                        
                        M('box_get')->where(array('id'=>$_POST['id'],'token'=>$_POST['token']))->save(array('isshare'=>1,'isget'=>1,'sn'=>$randStr,'time'=>time()));
                        M('box')->where(array('id'=>$_POST['pid'],'token'=>$_POST['token']))->setDec($awardnums);
                        $this->assign('isget', 1);

                        //获奖之后微信推送消息
                        $content = "恭喜".$box_get['info-name']."获得".$prize['awardname']."\r\n奖品使用规则：".$prize['awardrule']."\r\n兑奖码：".$randStr."\r\n兑奖时间："
                                .$prize['starttime']."-".$prize['endtime']."\r\n抓紧时间兑奖吧！";

                        $access_token = getAccessToken($_POST['token']);
                  		if($access_token["status"]){
                  			$data='{"touser":"'.$_POST['wecha_id'].'","msgtype":"text","text":{"content":"'.$content.'"}}';
	                        $url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token['info'];
	                        $this->api_notice_increment($url,$data);
	               		}else{
							Log::write("Wap/BoxAction.openBox - getAccessToken:".$access_token["info"]);
					    }
                        
                    }else{
                        M('box_get')->where(array('id'=>$_POST['id'],'token'=>$_POST['token']))->save(array('isget'=>1));
                        $this->assign('isget', 0);
                    }
                }
                
              
          }else{
               M('box_get')->where(array('id'=>$_POST['id'],'token'=>$_POST['token']))->save(array('isget'=>1));
               $this->assign('isget', 0);
          }
          $config = getWXJSConfig($_POST['token']);
		  $config['jsApiList']=array('onMenuShareTimeline','onMenuShareAppMessage');
		  $this->assign("config", json_encode($config));
          //分享的链接
          $api=M('Diymen_set')->where(array('token'=>$_POST['token']))->find();
          $redirect_url = C(site_url)."/index.php?g=Wap&m=Box&a=indexShare&id=".$_POST['pid']."&token=".$_POST['token'];
          $url_get = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$api['appid']."&redirect_uri=".urlencode($redirect_url)."&response_type=code&scope=snsapi_base&state=0#wechat_redirect";
          $this->assign("shareurl", $url_get);
        
          $this->assign('box', $box);
          $this->assign('box_get', $box_get);
          $this->display();
         }
     }
     
     protected function get_rand($awardnums, $total)
    {
        $result  = 0;
        $randNum = mt_rand(1, $total);
        if ($awardnums > 0) {
            if ($randNum >=1 && $randNum <= $awardnums) {
                $result = 1;
            }
        }
        return $result;
    }
             
    function curlGet($url){
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
      function api_notice_increment($url, $data){
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
