<?php
class WeixinAction extends Action
{
    private $token;
    private $fun;
    private $data = array();
    private $my = '小微';
    private $myapi = 'http://www.xiaojo.com/api5.php?db=kisswugang&pw=214665692&chat=';
	private $wallmessage;
    public function index()
    {
        $this->token = $this->_get('token');
        $weixin = new Wechat($this->token);
        $data = $weixin->request();
        /* 获取请求信息 */
        $this->data = $weixin->request();
        $this->my = C('site_my');
        /* 获取回复信息 */
        list($content, $type) = $this->reply($data);
        /* 响应当前请求 */
        if($type && $content)
        	$weixin->response($content, $type);
        
    }
    private function reply($data){
		if(substr($data['Content'],0,3)=="kf#"){
			M('Wxuser_message')->add($data);
			return array('发送成功,客服马上与您联系！', 'text');
        
		}
        // M('Wxuser_message')->add($data);
        // return array('发送成功,客服马上与您联系！', 'text');
        // $contentStr = "发送成功,客服马上与您联系！";
        // $msgType = "transfer_customer_service";
        // $result = $this->transmitService($object);   
        //M('Wxuser_message')->add($data);
        //$weixin->transmitService();
        //Log::write("多客服返回信息".$weixin->transmitService(), Log::DEBUG, 3, LOG_PATH.'customers.log');           
        //}
        //========================
        if ('CLICK' == $data['Event']) {
            $data['Content'] = $data['EventKey'];
        }

        //=========================客服接入 === 接受文本消息客服接入和点击菜单客服接入==
        if(substr($data['Content'],0,6)=="客服"){
        	Log::write(date('Y-m-d H:i:s', $data['CreateTime']).' openId: '.$data['FromUserName'], Log::INFO, Log::FILE, LOG_PATH.$this->token.'_kefu.log');
        	return array('接入多客服', 'transfer_customer_service');
        }
		
        if ('voice' == $data['MsgType']) {
            $data['Content'] = $data['Recognition'];
        }
        if ('subscribe' == $data['Event']) {
			//获取关注者信息
			$this->getUserInfo($this->token,$this->data['FromUserName']);
			//
            $this->requestdata('follownum');
            $data = M('Areply')->field('home,keyword,content')->where(array('token' => $this->token))->find();
            if ($data['keyword'] == '首页' || $data['keyword'] == 'home') {
                return $this->shouye();
            }
            if ($data['home'] == 1) {
                $like['keyword'] = array('like', ('%' . $data['keyword']) . '%');
                $like['token'] = $this->token;
                $back = M('Img')->field('id,text,pic,url,title')->limit(9)->order('id desc')->where($like)->select();
                foreach ($back as $keya => $infot) {
                    if ($infot['url'] != false) {
                        $url = $this->getFuncLink($infot['url']);
                    } else {
                        $url = rtrim(C('site_url'), '/') . U('Wap/Index/content', array('token' => $this->token, 'id' => $infot['id'], 'wecha_id' => $this->data['FromUserName']));
                    }
                    $return[] = array($infot['title'], $infot['text'], $infot['pic'], $url);
                }
                return array($return, 'news');
            } else {
                return array($data['content'], 'text');
            }
        } elseif ('unsubscribe' == $data['Event']) {
			//取消关注
			/**/
			$isSub=M('wxuser_people')->where(array('token' => $this->token,'wecha_id' => $this->data['FromUserName'],'subscribe'=>1))->find();
			if($isSub){
				
				$people=M('wxuser_people')->where(array('id'=>$isSub['id'],'token' => $this->token,'wecha_id' => $this->data['FromUserName']))->save(array('subscribe'=>0));
			}
			//
            $this->requestdata('unfollownum');
			
        }
        //事件推送群发结果
        if ('MASSSENDJOBFINISH' == $data['Event']) {Log::write(dump($data, false));
            $map['errmsg'] = $data['Status'];
            if($data['Status'] == "send success"){
	            $map['status'] = 3;
            }else{
	            $map['status'] = 1;
            }
            $map['totalcount'] = $data['TotalCount'];
            $map['filtercount'] = $data['FilterCount'];
            $map['errorcount'] = $data['ErrorCount'];
            $map['sentcount'] = $data['SentCount'];
            $map['update_time'] = $data['CreateTime'];
            M('MassImgLog')->where(array('msgid' => $data['MsgID']))->save($map);
            return array('','text');
        }
//         if (!stripos($this->fun, 'api') && $data['Content']) {
//             $like['keyword'] = array('like', '%' . $data['Content']);
//             $like['token'] = $this->token;
//             $api = M('api')->where($like)->order('id desc')->find();
//             if ($api != false) {
//                 $vo['fromUsername'] = $this->data['FromUserName'];
//                 $vo['Content'] = $this->data['Content'];
//                 $vo['toUsername'] = $this->token;
//                 if ($api['type'] == 2) {
//                     $apidata = $this->api_notice_increment($api['url'], $vo);
//                     return array($apidata, 'text');
//                 } else {
//                     $apidata = $this->api_notice_increment($api['url'], $file);
//                     echo $apidata;
//                     return false;
//                 }
//             }
//         }///在页面上没找到对应的使用，暂时注释
		//根据token_open授权的权限
        $Pin = new GetPin();
        $key = $data['Content'];
        $open = M('Token_open')->where(array('token' => $this->_get('token')))->find();
        $this->fun = $open['queryname'];
        $datafun = explode(',', $open['queryname']);
        $tags = $this->get_tags($key);
        $back = explode(',', $tags);
        foreach ($back as $keydata => $data) {
            $string = $Pin->Pinyin($data);
            if (in_array($string, $datafun) && $string) {
                $check = $this->user('connectnum');
                if ($string == 'fujin') {
                    $this->recordLastRequest($key);
                }
                $this->requestdata('textnum');
                if ($check['connectnum'] != 1) {
                    $return = C('connectout');
                    continue;
                }
                unset($back[$keydata]);
                eval(('$return= $this->' . $string) . '($back);');
                continue;
            }
        }
		//开始一站到底开始
        if (!isset($_SESSION['wecha_id']) || $_SESSION['wecha_id'] == '') {
            $_SESSION['wecha_id'] = $this->data['FromUserName'];
        }
        if (@strpos($key, '出题') !== false) {
            $info = $this->dati();
            return $info;
        }
        if (S($_SESSION['wecha_id']) == 'start') {
            $info = $this->dati_start($key);
            return $info;
        }
		
        if (!empty($return)) {
            if (is_array($return)) {
                return $return;
            } else {
                return array($return, 'text');
            }
        } else {
            if (!(strpos($key, 'cheat') === FALSE)) {
                $arr = explode(' ', $key);
                $datas['lid'] = intval($arr[1]);
                $lotteryPassword = $arr[2];
                $datas['prizetype'] = intval($arr[3]);
                $datas['intro'] = $arr[4];
                $datas['wecha_id'] = $this->data['FromUserName'];
                $thisLottery = M('Lottery')->where(array('id' => $datas['lid']))->find();
                if ($lotteryPassword == $thisLottery['parssword']) {
                    $rt = M('Lottery_cheat')->add($datas);
                    if ($rt) {
                        return array('设置成功', 'text');
                    }
                    return array('设置失败:未知原因', 'text');
                } else {
                    return array('设置失败:密码不对', 'text');
                }
            }
            if ($this->data['Location_X']) {
                $this->recordLastRequest(($this->data['Location_Y'] . ',') . $this->data['Location_X'], 'location');
                return $this->map($this->data['Location_X'], $this->data['Location_Y']);
            }
            if ((!(strpos($key, '开车去') === FALSE) || !(strpos($key, '坐公交') === FALSE)) || !(strpos($key, '步行去') === FALSE)) {
                $this->recordLastRequest($key);
                $user_request_model = M('User_request');
                $loctionInfo = $user_request_model->where(array('token' => $this->_get('token'), 'msgtype' => 'location', 'uid' => $this->data['FromUserName']))->find();
                if ($loctionInfo && intval($loctionInfo['time'] > time() - 60)) {
                    $latLng = explode(',', $loctionInfo['keyword']);
                    return $this->map($latLng[1], $latLng[0]);
                }
                return array('请发送您所在的位置', 'text');
            }
			if(substr($key,0,2)=="##"){
				$this->wallmessage=substr_replace($key,"",0,2);
				return $this->Wewall();
			}
            if(substr($key,0,3)=="yyy"){
                $this->shakemessage=substr_replace($key,"",0,3);
                return $this->Shake();
            }
            switch ($key) {
            case '首页':
                return $this->home();
                break;
            case 'home':
                return $this->home();
                break;
            case '主页':
                return $this->home();
                break;
            case '地图':
                return $this->companyMap();
                break;
            case '最近的':
                $this->recordLastRequest($key);
                $user_request_model = M('User_request');
                $loctionInfo = $user_request_model->where(array('token' => $this->_get('token'), 'msgtype' => 'location', 'uid' => $this->data['FromUserName']))->find();
                if ($loctionInfo && intval($loctionInfo['time'] > time() - 60)) {
                    $latLng = explode(',', $loctionInfo['keyword']);
                    return $this->map($latLng[1], $latLng[0]);
                }
                return array('请发送您所在的位置', 'text');
                break;
            case 'lbs':
                $this->recordLastRequest($key);
                $user_request_model = M('User_request');
                $loctionInfo = $user_request_model->where(array('token' => $this->_get('token'), 'msgtype' => 'location', 'uid' => $this->data['FromUserName']))->find();
                if ($loctionInfo && intval($loctionInfo['time'] > time() - 60)) {
                    $latLng = explode(',', $loctionInfo['keyword']);
                    return $this->map($latLng[1], $latLng[0]);
                }
                return array('请发送您所在的位置', 'text');
                break;
            case '帮助':
                return $this->help();
                break;
            case '手机':
                return $this->shouji();
                break;
            case '域名':
                return $this->yuming();
                break;
            case '笑话':
                return $this->xiaohua();
                break;
            case '快递':
                return $this->kuaidi();
                break;
            case '公交':
                return $this->gongjiao();
                break;
            case '火车':
                return $this->huoche();
                break;
            case 'help':
                return $this->help();
                break;
            case '会员卡':
                return $this->member();
                break;
            case '身份证':
                return $this->shenfenzheng();
                break;
            case '会员':
                return $this->member();
                break;
            case '3g相册':
                return $this->xiangce();
                break;
            case '相册':
                return $this->xiangce();
                break;
			case '吃粽子':
                    $pro = M('czzreply_info')->where(array('token' => $this->token))->find();
                    $url = C('site_url') . U('Wap/Game/gameCzz', array('token'=>$this->token, 'wecha_id'=>$this->data['FromUserName']));
                    
                    return array(
                        array(
                            array(
                                $pro['title'],
                                strip_tags(htmlspecialchars_decode($pro['info'])) ,
                                $pro['picurl'],
                                $url
                            )
                        ) ,
                        'news'
                    );
                    break;
				
			case '2048':
			case '2048Plus':
			case '2048Fly':
				$pro = M('gamereply_info')->where(array('token' => $this->token, 'keyword'=>$key))->find();
				if($pro){
                    $url = C('site_url') . U('Wap/Game/game' . $key, array('token'=>$this->token, 'wecha_id'=>$this->data['FromUserName']));
                    return array(array(array(
                                $pro['title'],
                                strip_tags(htmlspecialchars_decode($pro['info'])) ,
                                $pro['picurl'],
                                $url
                            )
                        ), 'news'
                    );
				}else{
					return array("管理员未开放此游戏", 'text');
				}
				break;
            case '商城':
				//如果该微客拥有微店铺，则链接需要加上店铺ID。没有，则不需要带上。
				
				$this->store_model=M('Store');  //微店铺表
				$map['token']=$this->token;
				$map['wecha_id']=$this->data['FromUserName']; 
				$map['status']=1;
				$storeInfo=$this->store_model->where($map)->find();
				//p($storeInfo);
				if(empty($storeInfo['id'])){
					//没有自己的店铺
					if (isset($_GET['store_id'])&&intval($_GET['store_id'])){
						//进入别人的店铺
						$this->store_id=$_GET['store_id'];
					}else{
						//进入供货商的店铺
						$this->store_id=0;
					}
				}
				else{
					//有自己的店铺
					$this->store_id=$storeInfo['id'];
					//$this->redirect("Wap/Product/".ACTION_NAME, _
				}
				//输入商城关键字，触发图文消息
                $pro = M('reply_info')->where(array('infotype' => 'Shop', 'token' => $this->token))->find();
                return array(
						array(
							array(
								$pro['title'], 
								strip_tags(htmlspecialchars_decode($pro['info'])), 
								$pro['picurl'], 
								C('site_url') . U("Wap/Product/index",array('token'=>$this->token,'store_id'=>$this->store_id,"sgssz"=>"mp.weixin.qq.com"))
							)
						), 
					'news');
                break;
				
                /* $pro = M('reply_info')->where(array('infotype' => 'Shop', 'token' => $this->token))->find();
                $url = ((((C('site_url') . '/index.php?g=Wap&m=Store&a=cats&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&sgssz=mp.weixin.qq.com';
                if ($pro['apiurl']) {
                    $url = str_replace('&amp;', '&', $pro['apiurl']);
                }
                return array(array(array($pro['title'], $this->handleIntro($pro['info']), $pro['picurl'], $url)), 'news');
                break; */
				
            case 'aaa':
                return array(array(array($pro['title'], strip_tags(htmlspecialchars_decode($pro['info'])), $pro['picurl'], ((((C('site_url') . '/cms/index.php?token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
            case '订餐':
                $pro = M('reply_info')->where(array('infotype' => 'Dining', 'token' => $this->token))->find();
                return array(array(array($pro['title'], strip_tags(htmlspecialchars_decode($pro['info'])), $pro['picurl'], ((((C('site_url') . '/index.php?g=Wap&m=Product&a=dining&dining=1&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
            case '拆礼盒':
                $pro = M('Box')->where(array('status' => '1', 'token' => $this->token))->find();
                if($pro){
                    return array(array(array($pro['title'], strip_tags(htmlspecialchars_decode($pro['descr'])), $pro['cover'], ((((C('site_url') . '/index.php?g=Wap&m=Box&a=getPrize&id='.$pro['id'].'&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&sgssz=mp.weixin.qq.com')), 'news');
                }else{return array('商家未设置拆礼盒活动', 'text');}
                break;
            case '留言':
                $pro = M('reply_info')->where(array('infotype' => 'Liuyan', 'token' => $this->token))->find();
                return array(array(array($pro['title'], strip_tags(htmlspecialchars_decode($pro['info'])), $pro['picurl'], (((C('site_url') . '/index.php?g=Wap&m=Liuyan&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName'])), 'news');
                break;
            case '团购':
                $pro = M('reply_info')->where(array('infotype' => 'Groupon', 'token' => $this->token))->find();
                return array(array(array($pro['title'], strip_tags(htmlspecialchars_decode($pro['info'])), $pro['picurl'], ((((C('site_url') . '/index.php?g=Wap&m=Groupon&a=grouponIndex&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
            case '全景':
                $pro = M('reply_info')->where(array('infotype' => 'panorama', 'token' => $this->token))->find();
                if ($pro) {
                    return array(array(array($pro['title'], strip_tags(htmlspecialchars_decode($pro['info'])), $pro['picurl'], ((((C('site_url') . '/index.php?g=Wap&m=Panorama&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&sgssz=mp.weixin.qq.com')), 'news');
                } else {
                    return array(array(array('360°全景看车看房', '通过该功能可以实现3D全景看车看房', rtrim(C('site_url'), '/') . '/tpl/User/default/common/images/panorama/360view.jpg', ((((C('site_url') . '/index.php?g=Wap&m=Panorama&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&sgssz=mp.weixin.qq.com')), 'news');
                }
                break;
            case '微房产':
                $Estate = M('Estate')->where(array('token' => $this->token))->find();
                return array(array(array($Estate['title'], str_replace('&nbsp;', '', strip_tags(htmlspecialchars_decode($Estate['estate_desc']))), $Estate['cover'], ((((((C('site_url') . '/index.php?g=Wap&m=Estate&a=index&&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&hid=') . $Estate['id']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
			
            default:
                $check = $this->user('diynum', $key);
                if ($check['diynum'] != 1) {
                    return array(C('connectout'), 'text');
                } else {
                    return $this->keyword($key);
                }
            }
        }
    }
    public function xiangce()
    {
        $photo = M('Photo')->where(array('token' => $this->token, 'status' => 1))->find();
        $data['title'] = $photo['title'];
        $data['keyword'] = $photo['info'];
        $data['url'] = rtrim(C('site_url'), '/') . U('Wap/Photo/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName']));
        $data['picurl'] = $photo['picurl'] ? $photo['picurl'] : rtrim(C('site_url'), '/') . '/tpl/static/images/yj.jpg';
        return array(array(array($data['title'], $data['keyword'], $data['picurl'], $data['url'])), 'news');
    }
    public function companyMap()
    {
        import('Home.Action.MapAction');
        $mapAction = new MapAction();
        return $mapAction->staticCompanyMap();
    }
    public function shenhe($name)
    {
        $name = implode('', $name);
        if (empty($name)) {
            return '正确的审核帐号方式是：审核+帐号';
        } else {
            $user = M('Users')->field('id')->where(array('username' => $name))->find();
            if ($user == false) {
                return ('主人' . $this->my) . '提醒您,您还没注册吧n正确的审核帐号方式是：审核+帐号,不含+号';
            } else {
                $up = M('users')->where(array('id' => $user['id']))->save(array('status' => 1, 'viptime' => strtotime('+1 day')));
                if ($up != false) {
                    return ('主人' . $this->my) . '恭喜您,您的帐号已经审核,您现在可以登陆平台测试功能啦!';
                } else {
                    return '服务器繁忙请稍后再试';
                }
            }
        }
    }
    public function huiyuanka($name)
    {
        return $this->member();
    }
    public function member()
    {
        $card = M('member_card_create')->where(array('token' => $this->token, 'wecha_id' => $this->data['FromUserName']))->find();
        $cardInfo = M('member_card_set')->where(array('token' => $this->token))->find();
        //$this->behaviordata('Member_card_set', $cardInfo['id']);
        $reply_info_db = M('Reply_info');
        if ($card == false) {
            $where_member = array('token' => $this->token, 'infotype' => 'membercard');
            $memberConfig = $reply_info_db->where($where_member)->find();
            if (!$memberConfig) {
                $memberConfig = array();
                $memberConfig['picurl'] = rtrim(C('site_url'), '/') . '/tpl/static/images/member.jpg';
                $memberConfig['title'] = '会员卡,省钱，打折,促销，优先知道,有奖励哦';
                $memberConfig['info'] = '尊贵vip，是您消费身份的体现,会员卡,省钱，打折,促销，优先知道,有奖励哦';
            }
            $data['picurl'] = $memberConfig['picurl'];
            $data['title'] = $memberConfig['title'];
            $data['keyword'] = $memberConfig['info'];
            if (!$memberConfig['apiurl']) {
                $data['url'] = rtrim(C('site_url'), '/') . U('Wap/Card/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName']));
            } else {
                $data['url'] = str_replace('{wechat_id}', $this->data['FromUserName'], $memberConfig['apiurl']);
            }
        } else {
            $where_unmember = array('token' => $this->token, 'infotype' => 'membercard_nouse');
            $unmemberConfig = $reply_info_db->where($where_unmember)->find();
            if (!$unmemberConfig) {
                $unmemberConfig = array();
                $unmemberConfig['picurl'] = rtrim(C('site_url'), '/') . '/tpl/static/images/vip.jpg';
                $unmemberConfig['title'] = '申请成为会员';
                $unmemberConfig['info'] = '申请成为会员，享受更多优惠';
            }
            $data['picurl'] = $unmemberConfig['picurl'];
            $data['title'] = $unmemberConfig['title'];
            $data['keyword'] = $unmemberConfig['info'];
            if (!$unmemberConfig['apiurl']) {
                $data['url'] = rtrim(C('site_url'), '/') . U('Wap/Card/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName']));
            } else {
                $data['url'] = str_replace('{wechat_id}', $this->data['FromUserName'], $unmemberConfig['apiurl']);
            }
        }
        return array(array(array($data['title'], $data['keyword'], $data['picurl'], $data['url'])), 'news');
    }
    public function taobao($name)
    {
        $name = array_merge($name);
        $data = M('Taobao')->where(array('token' => $this->token))->find();
        if ($data != false) {
            if (strpos($data['keyword'], $name)) {
                $url = (($data['homeurl'] . '/search.htm?search=y&keyword=') . $name) . '&lowPrice=&highPrice=';
            } else {
                $url = $data['homeurl'];
            }
            return array(array(array($data['title'], $data['keyword'], $data['picurl'], $url)), 'news');
        } else {
            return '商家还未及时更新淘宝店铺的信息,回复帮助,查看功能详情';
        }
    }
    public function choujiang($name)
    {
        $data = M('lottery')->field('id,keyword,info,title,starpicurl')->where(array('token' => $this->token, 'status' => 1, 'type' => 1))->order('id desc')->find();
        if ($data == false) {
            return array('暂无抽奖活动', 'text');
        }
        $pic = $data['starpicurl'] ? $data['starpicurl'] : rtrim(C('site_url'), '/') . '/tpl/User/default/common/images/img/activity-lottery-start.jpg';
        $url = rtrim(C('site_url'), '/') . U('Wap/Lottery/index', array('type' => 1, 'token' => $this->token, 'id' => $data['id'], 'wecha_id' => $this->data['FromUserName']));
        return array(array(array($data['title'], $data['info'], $pic, $url)), 'news');
    }
    public function keyword($key)
    {
        $like['keyword'] = array('like', ('%' . $key) . '%');
        $like['token'] = $this->token;
        $data = M('keyword')->where($like)->order('id desc')->find();
        if ($data != false) {
            switch ($data['module']) {
            /* case 'Img':
                $this->requestdata('imgnum');
                $img_db = M($data['module']);
                $back = $img_db->field('id,text,pic,pic1,url,title')->limit(9)->order('id desc')->where($like)->select();
                $idsWhere = 'id in (';
                $comma = '';
				$i=1;
                foreach ($back as $keya => $infot) {
                    $idsWhere .= $comma . $infot['id'];
                    $comma = ',';
                    if ($infot['url'] != false) {
                        if (!(strpos($infot['url'], 'http') === FALSE)) {
                            $url = html_entity_decode($infot['url'])."&wecha_id=".$this->data['FromUserName'];
                        } else {
                            $url = $this->getFuncLink($infot['url']);
                        }
                    } else {
                        $url = rtrim(C('site_url'), '/') . U('Wap/Index/content', array('token' => $this->token, 'id' => $infot['id'], 'wecha_id' => $this->data['FromUserName']));
                    }
					if($i==1){
					$pic=$infot['pic'];
					}else{
					$pic=$infot['pic1'];
					}
                    $return[] = array($infot['title'], $infot['text'], $pic, $url);
					unset($pic);
					$i++;
                }
                $idsWhere .= ')';
                if ($back) {
                    $img_db->where($idsWhere)->setInc('click');
                }
                return array($return, 'news');
                break; */
			//===========
			//关键字属于图文内容
			case 'Img':
                $this->requestdata('imgnum');
                $img_db = M($data['module']);
                $back = $img_db->field('id,text,pic,url,title')->limit(9)->order('id desc')->where($like)->select();
                $idsWhere = 'id in (';
                $comma = '';
                foreach ($back as $keya => $infot) {
                    $idsWhere .= $comma . $infot['id'];
                    $comma = ',';
                    if ($infot['url'] != false) {
                        if (!(strpos($infot['url'], 'http') === FALSE)) {
                            $url = html_entity_decode($infot['url']);
                        } else {
                            $url = $this->getFuncLink($infot['url']);
                        }
                    } else {
                        $url = rtrim(C('site_url'), '/') . U('Wap/Index/content', array('token' => $this->token, 'id' => $infot['id'], 'wecha_id' => $this->data['FromUserName']));
                    }
                    $return[] = array($infot['title'], $infot['text'], $infot['pic'], $url);
                }
                $idsWhere .= ')';
                if ($back) {
                    $img_db->where($idsWhere)->setInc('click');
                }
                return array($return, 'news');
                break;
			//============
            case 'Host':
                $this->requestdata('other');
                $host = M('Host')->where(array('id' => $data['pid']))->find();
                return array(array(array($host['name'], $host['info'], $host['ppicurl'], ((((((C('site_url') . '/index.php?g=Wap&m=Host&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&hid=') . $data['pid']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
			//关键字属于房地产	
            case 'Estate':
                    $this->requestdata('other');
                    $Estate = M('Estate')->where(array(
                        'id' => $data['pid']
                    ))->find();
                    return array(
                        array(
                            array(
                                $Estate['title'],
                                $Estate['estate_desc'],
                                $Estate['cover'],
                                C('site_url') . '/index.php?g=Wap&m=Estate&a=index&token=' . $this->token . '&wecha_id=' . $this->data['FromUserName'] . '&sgssz=mp.weixin.qq.com'
                            ),
                            array(
                                '楼盘介绍',
                                $Estate['estate_desc'],
                                $Estate['house_banner'],
                                C('site_url') . '/index.php?g=Wap&m=Estate&a=index&&token=' . $this->token . '&wecha_id=' . $this->data['FromUserName'] . '&hid=' . $data['pid'] . '&sgssz=mp.weixin.qq.com'
                            ),
                            array(
                                '专家点评',
                                $Estate['estate_desc'],
                                $Estate['cover'],
                                C('site_url') . '/index.php?g=Wap&m=Estate&a=impress&&token=' . $this->token . '&wecha_id=' . $this->data['FromUserName'] . '&hid=' . $data['pid'] . '&sgssz=mp.weixin.qq.com'
                            ),
                            array(
                                '楼盘3D全景',
                                $Estate['estate_desc'],
                                $Estate['banner'],
                                C('site_url') . '/index.php?g=Wap&m=Panorama&a=item&id=' . $Estate['panorama_id'] . '&token=' . $this->token . '&wecha_id=' . $this->data['FromUserName'] . '&hid=' . $data['pid'] .  '&sgssz=mp.weixin.qq.com'
                            ),
                            array(
                                '楼盘动态',
                                $Estate['estate_desc'],
                                $Estate['house_banner'],
                                C('site_url') . '/index.php?g=Wap&m=Index&a=lists&classid=' . $Estate['classify_id'] . '&token=' . $this->token . '&wecha_id=' . $this->data['FromUserName'] . '&hid=' . $data['pid'] . '&sgssz=mp.weixin.qq.com'
                            ),
                            array(
                                '预约看房',
                                $Estate['estate_desc'],
                                $Estate['house_banner'],
                                C('site_url') . '/index.php?g=Wap&m=Estate&a=EstateReserveBook&addtype=estate'  . '&token=' . $this->token . '&wecha_id=' . $this->data['FromUserName'] . '&hid=' . $data['pid'] . '&sgssz=mp.weixin.qq.com'
                            )
                        ),
                        'news'
                    );
                    break;
			//关键字属于预约		
            case 'Reservation':
                $this->requestdata('other');
                $rt = M('Reservation')->where(array('id' => $data['pid']))->find();
                return array(array(array($rt['title'], $rt['info'], $rt['picurl'], ((((((((C('site_url') . '/index.php?g=Wap&m=Reservation&a=index&rid=') . $data['pid']) . '&addtype=') . $rt['addtype']) . '&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
			//关键字属于文本	
            case 'Text':
                $this->requestdata('textnum');
                $info = M($data['module'])->order('id desc')->find($data['pid']);
                return array(htmlspecialchars_decode(str_replace('{wechat_id}', $this->data['FromUserName'], $info['text'])), 'text');
                break;
			//关键字属于商城	
            case 'Product':
                $this->requestdata('other');
                $infos = M('Product')->limit(9)->order('id desc')->where($like)->select();
                if ($infos) {
                    $return = array();
                    foreach ($infos as $info) {
                        $return[] = array($info['name'], strip_tags(htmlspecialchars_decode($info['intro'])), $info['logourl'], ((((((C('site_url') . '/index.php?g=Wap&m=Product&a=product&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $info['id']) . '&sgssz=mp.weixin.qq.com');
                    }
                }
                return array($return, 'news');
                break;
			//关键字属于留言	
            case 'liuyan':
                $this->requestdata('other');
                $pro = M('liuyan')->where(array('id' => $data['pid']))->find();
                return array(array(array($pro['title'], strip_tags(htmlspecialchars_decode($pro['message'])), $pro['pic'], (((((C('site_url') . '/index.php?g=Wap&m=Liuyan&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $data['pid'])), 'news');
                break;
            case 'Selfform':
                $this->requestdata('other');
                $pro = M('Selfform')->where(array('id' => $data['pid']))->find();
                return array(array(array($pro['name'], strip_tags(htmlspecialchars_decode($pro['intro'])), $pro['logourl'], ((((((C('site_url') . '/index.php?g=Wap&m=Selfform&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $data['pid']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
            case 'Panorama':
                $this->requestdata('other');
                $pro = M('Panorama')->where(array('id' => $data['pid']))->find();
                return array(array(array($pro['name'], strip_tags(htmlspecialchars_decode($pro['intro'])), $pro['frontpic'], ((((((C('site_url') . '/index.php?g=Wap&m=Panorama&a=item&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $data['pid']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
			//关键字属于微调研	
            case 'Weidiaoyan':
                $this->requestdata('other');
                $pro = M('Weidiaoyan')->where(array('id' => $data['pid']))->find();
                return array(array(array($pro['name'], strip_tags(htmlspecialchars_decode($pro['intro'])), $pro['logourl'], ((((((C('site_url') . '/index.php?g=Wap&m=Weidiaoyan&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $data['pid']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
			//关键字属于微喜帖	
            case 'Wedding':
                $this->requestdata('other');
                $pro = M('Wedding')->where(array('id' => $data['pid']))->find();
                return array(array(array($pro['title'], (($pro['man'] . '和') . $pro['woman']) . '的微信喜帖', $pro['coverurl'], (((((C('site_url') . '/index.php?g=Wap&m=Wedding&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $data['pid']), array('给我的祝福', '', '', (((((C('site_url') . '/index.php?g=Wap&m=Wedding&a=comment&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $data['pid']), array('赴宴名单', '', '', (((((C('site_url') . '/index.php?g=Wap&m=Wedding&a=info&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $data['pid'])), 'news');
                break;
			//关键字属于微投票	
            case 'Vote':
                $this->requestdata('other');
                $Vote = M('Vote')->where(array('id' => $data['pid']))->find();
                return array(array(array($Vote['title'], str_replace('&nbsp;', ' ', strip_tags(htmlspecialchars_decode($Vote['info']))), $Vote['picurl'], ((((((C('site_url') . '/index.php?g=Wap&m=Vote&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $data['pid']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
			//关键字属于微场景	
            case 'Scene':
                $this->requestdata('other');
                $scene = M('Scene')->where(array('id' => $data['pid'],'token' => $this->token))->find();
                return array(array(array($scene['title'], $this->handleIntro($scene['info']), $scene['picurl'], ((((((C('site_url') . '/index.php?g=Wap&m=Scene&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $data['pid']) . '&sgssz=mp.weixin.qq.com')), 'news');
                break;
			//关键字属于抽奖	
            case 'Lottery':
               $this->requestdata('other');
               $info = M('Lottery')->find($data['pid']);
               if ($info == false || $info['status'] == 3) {
                   return array('活动可能已经结束或者被删除', 'text');
               }
               switch ($info['type']) {
               case 1:
                   $model = 'Lottery';
                   break;
               case 2:
                   $model = 'Guajiang';
                   break;
               case 3:
                   $model = 'Coupon';
                   break;
               case 4:
                   $model = 'Zadan';
				   break;
               case 5:
                   $model = 'LuckyFruit';
				   break;
				case 7:
                   $model = 'AppleGame';
				   break;
				case 8:
                   $model = 'Lovers';
				   break;
				
               }
               $id = $info['id'];
               $type = $info['type'];
               if ($info['status'] == 1) {
                   $picurl = $info['starpicurl'];
                   $title = $info['title'];
                   $id = $info['id'];
                   $info = $info['info'];
               } else {
                   $picurl = $info['endpicurl'];
                   $title = $info['endtite'];
                   $info = $info['endinfo'];
               }
               $url = C('site_url') . U((('Wap/' . $model) . '/index'), array('token' => $this->token, 'type' => $type, 'wecha_id' => $this->data['FromUserName'], 'id' => $id));
               return array(array(array($title, $info, $picurl, $url)), 'news');
			   //=============================================
            case 'Business':
                $this->requestdata('other');
                $thisItem = M('Busines')->where(array('bid' => $data['pid']))->find();
                return array(array(array($thisItem['title'], str_replace(array('&nbsp;', 'br /', '&amp;', 'gt;', 'lt;'), '', strip_tags(htmlspecialchars_decode($thisItem['business_desc']))), $thisItem['picurl'], (((((((C('site_url') . '/index.php?g=Wap&m=Business&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&bid=') . $thisItem['bid']) . '&type=') . $thisItem['type'])), 'news');
                break;
            //============================================= 
			case 'Carowner':
                $this->requestdata('other');
                $thisItem = M('Carowner')->where(array('id' => $data['pid']))->find();
                return array(array(array($thisItem['title'], $this->handleIntro($thisItem['info']), $thisItem['head_url'], (((C('site_url') . '/index.php?g=Wap&m=Car&a=owner&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName'])), 'news');
                break;
            case 'Carset':
                $this->requestdata('other');
                $thisItem = M('Carset')->where(array('id' => $data['pid']))->find();
                $news = array();
                array_push($news, array($thisItem['title'], '', $thisItem['head_url'], $thisItem['url'] ? str_replace(array('{wechat_id}', '{siteUrl}', '&amp;'), array($this->data['FromUserName'], C('site_url'), '&'), $thisItem['url']) : (((C('site_url') . '/index.php?g=Wap&m=Car&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']));
                array_push($news, array($thisItem['title1'], '', $thisItem['head_url_1'], $thisItem['url1'] ? str_replace(array('{wechat_id}', '{siteUrl}', '&amp;'), array($this->data['FromUserName'], C('site_url'), '&'), $thisItem['url1']) : (((C('site_url') . '/index.php?g=Wap&m=Car&a=brands&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']));
                array_push($news, array($thisItem['title2'], '', $thisItem['head_url_2'], $thisItem['url2'] ? str_replace(array('{wechat_id}', '{siteUrl}', '&amp;'), array($this->data['FromUserName'], C('site_url'), '&'), $thisItem['url2']) : (((C('site_url') . '/index.php?g=Wap&m=Car&a=salers&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']));
                array_push($news, array($thisItem['title3'], '', $thisItem['head_url_3'], $thisItem['url3'] ? str_replace(array('{wechat_id}', '{siteUrl}', '&amp;'), array($this->data['FromUserName'], C('site_url'), '&'), $thisItem['url3']) : (((C('site_url') . '/index.php?g=Wap&m=Car&a=CarReserveBook&addtype=drive&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']));
                array_push($news, array($thisItem['title4'], '', $thisItem['head_url_4'], $thisItem['url4'] ? str_replace(array('{wechat_id}', '{siteUrl}', '&amp;'), array($this->data['FromUserName'], C('site_url'), '&'), $thisItem['url4']) : (((C('site_url') . '/index.php?g=Wap&m=Car&a=owner&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']));
                array_push($news, array($thisItem['title5'], '', $thisItem['head_url_5'], $thisItem['url5'] ? str_replace(array('{wechat_id}', '{siteUrl}', '&amp;'), array($this->data['FromUserName'], C('site_url'), '&'), $thisItem['url5']) : (((C('site_url') . '/index.php?g=Wap&m=Car&a=tool&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']));
                array_push($news, array($thisItem['title6'], '', $thisItem['head_url_6'], $thisItem['url6'] ? str_replace(array('{wechat_id}', '{siteUrl}', '&amp;'), array($this->data['FromUserName'], C('site_url'), '&'), $thisItem['url6']) : (((C('site_url') . '/index.php?g=Wap&m=Car&a=showcar&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']));
                return array($news, 'news');
                break;
            //=============================
            case 'Heka':
                $this->requestdata('other');
                $pro = M('Heka')->where(array('id' => $data['pid']))->find();
                //return array(array(array($thisItem['title'], $this->handleIntro($thisItem['content']), $thisItem['bg_topic'], (((C('site_url') . '/index.php?g=Wap&m=Heka&a=index&id=$thisItem['id']&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName'])), 'news');
                return array(array(array($pro['title'], strip_tags(htmlspecialchars_decode($pro['content'])), $pro['topic'], ((((((C('site_url') . '/index.php?g=Wap&m=Heka&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&id=') . $data['pid']) . '&sgssz=mp.weixin.qq.com')), 'news'); 
                break;       
			//=============================================
			//默认情况
			default:
               $this->requestdata('videonum');
               $info = M($data['module'])->order('id desc')->find($data['pid']);
               return array(array($info['title'], $info['keyword'], $info['musicurl'], $info['hqmusicurl']), 'music');
            }
        } else {
            if (!strpos($this->fun, 'liaotian')) {
                $other = M('Other')->where(array('token' => $this->token))->find();
                if ($other == false) {
                    return array('回复帮助，可了解所有功能', 'text');
                } else {
                    if (empty($other['keyword'])) {
                        return array($other['info'], 'text');
                    } else {
                        $img = M('Img')->field('id,text,pic,url,title')->limit(5)->order('id desc')->where(array('token' => $this->token, 'keyword' => array('like', ('%' . $other['keyword']) . '%')))->select();
                        if ($img == false) {
                            return array('无此图文信息,请提醒商家，重新设定关键词', 'text');
                        }
                        foreach ($img as $keya => $infot) {
                            if ($infot['url'] != false) {
                                if (!(strpos($infot['url'], 'http') === FALSE)) {
                                    $url = html_entity_decode($infot['url']);
                                } else {
                                    $url = $this->getFuncLink($infot['url']);
                                }
                            } else {
                                $url = rtrim(C('site_url'), '/') . U('Wap/Index/content', array('token' => $this->token, 'id' => $infot['id'], 'wecha_id' => $this->data['FromUserName']));
                            }
                            $return[] = array($infot['title'], $infot['text'], $infot['pic'], $url);
                        }
                        return array($return, 'news');
                    }
                }
            }
            return $this->chat($key);
        }
    }
    public function getFuncLink($u)
    {
        $urlInfos = explode(' ', $u);
        switch ($urlInfos[0]) {
        default:
            $url = str_replace('{wechat_id}', $this->data['FromUserName'], $urlInfos[0]);
            break;
        case '刮刮卡':
            $Lottery = M('Lottery')->where(array('token' => $this->token, 'type' => 2, 'status' => 1))->order('id DESC')->find();
            $url = C('site_url') . U('Wap/Guajiang/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName'], 'id' => $Lottery['id']));
            break;
        //case '大转盘':
//            $Lottery = M('Lottery')->where(array('token' => $this->token, 'type' => 1, 'status' => 1))->order('id DESC')->find();
//            $url = C('site_url') . U('Wap/Lottery/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName'], 'id' => $Lottery['id']));
//            break;
        case '商家订单':
            $url = ((((((C('site_url') . '/index.php?g=Wap&m=Host&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&hid=') . $urlInfos[1]) . '&sgssz=mp.weixin.qq.com';
            break;
        case '万能表单':
            $url = C('site_url') . U('Wap/Selfform/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName'], 'id' => $urlInfos[1]));
            break;
        case '微调研':
            $url = C('site_url') . U('Wap/Weidiaoyan/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName'], 'id' => $urlInfos[1]));
            break;
        case '会员卡':
            $url = C('site_url') . U('Wap/Card/vip', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName']));
            break;
        case '首页':
            $url = (((rtrim(C('site_url'), '/') . '/index.php?g=Wap&m=Index&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName'];
            break;
        case '团购':
            $url = (((rtrim(C('site_url'), '/') . '/index.php?g=Wap&m=Groupon&a=grouponIndex&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName'];
            break;
        case '商城':
            $url = (((rtrim(C('site_url'), '/') . '/index.php?g=Wap&m=Product&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName'];
            break;
        case '订餐':
            $url = (((rtrim(C('site_url'), '/') . '/index.php?g=Wap&m=Product&a=dining&dining=1&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName'];
            break;
        case '相册':
            $url = (((rtrim(C('site_url'), '/') . '/index.php?g=Wap&m=Photo&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName'];
            break;
        case '网站分类':
            $url = C('site_url') . U('Wap/Index/lists', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName'], 'classid' => $urlInfos[1]));
            break;
        case 'LBS信息':
            if ($urlInfos[1]) {
                $url = C('site_url') . U('Wap/Company/map', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName'], 'companyid' => $urlInfos[1]));
            } else {
                $url = C('site_url') . U('Wap/Company/map', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName']));
            }
            break;
        case 'DIY宣传页':
            $url = (C('site_url') . '/index.php/show/') . $this->token;
            break;
        case '婚庆喜帖':
            $url = C('site_url') . U('Wap/Wedding/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName'], 'id' => $urlInfos[1]));
            break;
        case '投票':
            $url = C('site_url') . U('Wap/Vote/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName'], 'id' => $urlInfos[1]));
            break;
        case '喜帖':
            $url = C('site_url') . U('Wap/Wedding/index', array('token' => $this->token, 'wecha_id' => $this->data['FromUserName'], 'id' => $urlInfos[1]));
            break;
        }
        return $url;
    }
    public function home()
    {
        return $this->shouye();
    }
    public function shouye($name)
    {
        $home = M('Home')->where(array('token' => $this->token))->find();
        if ($home == false) {
            return array('商家未做首页配置，请稍后再试', 'text');
        } else {
            $imgurl = $home['picurl'];
            if ($home['apiurl'] == false) {
                if (!$home['advancetpl']) {
                    /* $url = ((((rtrim(C('site_url'), '/') . '/index.php?g=Wap&m=Index&a=index&token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&sgssz=mp.weixin.qq.com'; */
					$url = rtrim(C('site_url'), '/') .'/index.php?g=Wap&m=Index&a=index&token='. $this->token;
                } else {
                    $url = ((((rtrim(C('site_url'), '/') . '/cms/index.php?token=') . $this->token) . '&wecha_id=') . $this->data['FromUserName']) . '&sgssz=mp.weixin.qq.com';
                }
            } else {
                $url = $home['apiurl']."&wecha_id=".$this->data['FromUserName'];
            }
        }
        return array(array(array($home['title'], $home['info'], $imgurl, $url)), 'news');
    }
    public function kuaidi($data)
    {
        $data = array_merge($data);
        $str = file_get_contents((('http://www.weinxinma.com/api/index.php?m=Express&a=index&name=' . $data[0]) . '&number=') . $data[1]);
        return $str;
    }
    public function langdu($data)
    {
        $data = implode('', $data);
        $mp3url = 'http://www.apiwx.com/aaa.php?w=' . urlencode($data);
        return array(array($data, '点听收听', $mp3url, $mp3url), 'music');
    }
    public function jiankang($data)
    {
        if (empty($data)) {
            return ('主人，' . $this->my) . '提醒您n正确的查询方式是:n健康+身高,+体重n例如：健康170,65';
        }
        $height = $data[1] / 100;
        $weight = $data[2];
        $Broca = ($height * 100 - 80) * 0.7;
        $kaluli = ((66 + 13.7 * $weight) + (5 * $height) * 100) - 6.8 * 25;
        $chao = $weight - $Broca;
        $zhibiao = $chao * 0.1;
        $res = round($weight / ($height * $height), 1);
        if ($res < 18.5) {
            $info = ('您的体形属于骨感型，需要增加体重' . $chao) . '公斤哦!';
            $pic = 1;
        } elseif ($res < 24) {
            $info = ('您的体形属于圆滑型的身材，需要减少体重' . $chao) . '公斤哦!';
        } elseif ($res > 24) {
            $info = ('您的体形属于肥胖型，需要减少体重' . $chao) . '公斤哦!';
        } elseif ($res > 28) {
            $info = '您的体形属于严重肥胖，请加强锻炼，或者使用我们推荐的减肥方案进行减肥';
        }
        return $info;
    }
    public function fujin($keyword)
    {
        $keyword = implode('', $keyword);
        if ($keyword == false) {
            return (($this->my . '很难过,无法识别主人的指令,正确使用方法是:输入【附近+关键词】当') . $this->my) . '提醒您输入地理位置的时候就OK啦';
        }
        $data = array();
        $data['time'] = time();
        $data['token'] = $this->_get('token');
        $data['keyword'] = $keyword;
        $data['uid'] = $this->data['FromUserName'];
        $re = M('Nearby_user');
        $user = $re->where(array('token' => $this->_get('token'), 'uid' => $data['uid']))->find();
        if ($user == false) {
            $re->data($data)->add();
        } else {
            $id['id'] = $user['id'];
            $re->where($id)->save($data);
        }
        return ('主人【' . $this->my) . '】已经接收到你的指令n请发送您的地理位置给我哈';
    }
    
    /**
     * 记录/更新用户最近一次请求信息
     */
    public function recordLastRequest($key, $msgtype = 'text')
    {
        $rdata = array();
        $rdata['time'] = time();
        $rdata['token'] = $this->_get('token');
        $rdata['keyword'] = $key;
        $rdata['msgtype'] = $msgtype;
        $rdata['uid'] = $this->data['FromUserName'];
        $user_request_model = M('User_request');
        $user_request_row = $user_request_model->where(array('token' => $this->_get('token'), 'msgtype' => $msgtype, 'uid' => $rdata['uid']))->find();
        if (!$user_request_row) {
            $user_request_model->add($rdata);
        } else {
            $rid['id'] = $user_request_row['id'];
            $user_request_model->where($rid)->save($rdata);
        }
    }
    public function map($x, $y)
    {
        $user_request_model = M('User_request');
        $user_request_row = $user_request_model->where(array('token' => $this->_get('token'), 'msgtype' => 'text', 'uid' => $this->data['FromUserName']))->find();
        if (!(strpos($user_request_row['keyword'], '附近') === FALSE)) {
            $user = M('Nearby_user')->where(array('token' => $this->_get('token'), 'uid' => $this->data['FromUserName']))->find();
            $keyword = $user['keyword'];
            $radius = 2000;
            $str = file_get_contents((((((C('site_url') . '/map.php?keyword=') . urlencode($keyword)) . '&x=') . $x) . '&y=') . $y);
            $array = json_decode($str);
            $map = array();
            foreach ($array as $key => $vo) {
                $map[] = array($vo->title, $key, rtrim(C('site_url'), '/') . '/tpl/static/images/home.jpg', $vo->url);
            }
            return array($map, 'news');
        } else {
            import('Home.Action.MapAction');
            $mapAction = new MapAction();
            if ((!(strpos($user_request_row['keyword'], '开车去') === FALSE) || !(strpos($user_request_row['keyword'], '坐公交') === FALSE)) || !(strpos($user_request_row['keyword'], '步行去') === FALSE)) {
                if (!(strpos($user_request_row['keyword'], '步行去') === FALSE)) {
                    $companyid = str_replace('步行去', '', $user_request_row['keyword']);
                    if (!$companyid) {
                        $companyid = 1;
                    }
                    return $mapAction->walk($x, $y, $companyid);
                }
                if (!(strpos($user_request_row['keyword'], '开车去') === FALSE)) {
                    $companyid = str_replace('开车去', '', $user_request_row['keyword']);
                    if (!$companyid) {
                        $companyid = 1;
                    }
                    return $mapAction->drive($x, $y, $companyid);
                }
                if (!(strpos($user_request_row['keyword'], '坐公交') === FALSE)) {
                    $companyid = str_replace('坐公交', '', $user_request_row['keyword']);
                    if (!$companyid) {
                        $companyid = 1;
                    }
                    return $mapAction->bus($x, $y, $companyid);
                }
            } else {
                switch ($user_request_row['keyword']) {
                case '最近的':
                    return $mapAction->nearest($x, $y);
                    break;
                }
            }
        }
    }
    public function suanming($name)
    {
        $name = implode('', $name);
        if (empty($name)) {
            return ('主人' . $this->my) . '提醒您正确的使用方法是[算命+姓名]';
        }
        $data = require_once CONF_PATH . 'suanming.php';
        $num = mt_rand(0, 80);
        return ($name . 'n') . trim($data[$num]);
    }
    public function yinle($name)
    {
        $name = implode('', $name);
        $url = 'http://httop1.duapp.com/mp3.php?musicName=' . $name;
        $str = file_get_contents($url);
        $obj = json_decode($str);
        return array(array($name, $name, $obj->url, $obj->url), 'music');
    }
    public function geci($n)
    {
        $name = implode('', $n);
        $str = $this->myapi . urlencode($name);
        //$json = json_decode(file_get_contents($str));
        //$reply = urldecode($json->content);
		$reply = urldecode(file_get_contents($str));
        $reply = str_replace('{br}', 'n', $reply);
        return $reply;
    }
    public function tianqi($n)
    {
        $name = implode('', $n);
        $str = $this->myapi . urlencode(($name . '天气'));
        //$json = json_decode(file_get_contents($str));
        //$reply = urldecode($json->content);
		$reply = urldecode(file_get_contents($str));
        return $reply;
    }
    public function yuming($n)
    {
        $name = implode('', $n);
        @($str = 'http://api.ajaxsns.com/api.php?key=free&appid=0&msg=' . urlencode(('域名' . $name)));
        $json = json_decode(file_get_contents($str));
        $str = str_replace('{br}', '
', $json->content);
        return str_replace('mzxing_com', 'Winxin', $str);
    }
    public function shouji($n)
    {
        $n = implode('', $n);
        if (count($n) > 1) {
            $this->error_msg($n);
            return false;
        }
        $xml_array = simplexml_load_file(('http://api.k780.com:88/?app=phone.get&phone=' . $n) . '&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=xml');
        //将XML中的数据,读取到数组对象中
        foreach ($xml_array as $tmp) {
            if ($str !== iconv('UTF-8', 'UTF-8', iconv('UTF-8', 'UTF-8', $str))) {
                $str = iconv('GBK', 'UTF-8', $str);
            }
            $str = (((((('【手机】' . $tmp->phone) . '【归属地】') . $tmp->att) . '【卡类型】') . $tmp->ctype) . '【邮编】') . $tmp->postno;
        }
        return $str;
    }
    public function shenfenzheng($n)
    {
        $n = implode('', $n);
        if (count($n) > 1) {
            $this->error_msg($n);
            return false;
        }
        $xml_array = simplexml_load_file(('http://api.k780.com:88/?app=idcard.get&idcard=' . $n) . '&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=xml');
        //将XML中的数据,读取到数组对象中
        foreach ($xml_array as $tmp) {
            if ($str !== iconv('UTF-8', 'UTF-8', iconv('UTF-8', 'UTF-8', $str))) {
                $str = iconv('GBK', 'UTF-8', $str);
            }
            $str = (((((('【身份证】' . $tmp->idcard) . '【地址】') . $tmp->att) . '【性别】') . $tmp->sex) . '【生日】') . $tmp->born;
        }
        return $str;
    }
    public function gongjiao($data)
    {
        $data = array_merge($data);
        if (count($data) != 2) {
            $this->error_msg();
            return false;
        }
        $json = file_get_contents((('http://www.twototwo.cn/bus/Service.aspx?format=json&action=QueryBusByLine&key=c3e2c03e-4a93-41f0-8ebe-dbadd7ea7858&zone=' . $data[0]) . '&line=') . $data[1]);
        $data = json_decode($json);
        $xianlu = $data->Response->Head->XianLu;
        $xdata = get_object_vars($xianlu->ShouMoBanShiJian);
        $xdata = $xdata['#cdata-section'];
        $piaojia = get_object_vars($xianlu->PiaoJia);
        $xdata = ($xdata . '
') . $piaojia['#cdata-section'];
        $main = $data->Response->Main->Item->FangXiang;
        $xianlu = $main[0]->ZhanDian;
        $str = $xdata;
        $str .= '
' . '【本公交途经】';
        for ($i = 0; $i < count($xianlu); $i++) {
            $str .= ('
' . $i) . trim($xianlu[$i]->ZhanDianMingCheng);
        }
        return $str;
    }
    public function huoche($data, $time = '')
    {
        $data = array_merge($data);
        $data[2] = date('Y', time()) . $time;
        if (count($data) != 3) {
            $this->error_msg(($data[0] . '至') . $data[1]);
            return false;
        }
        $time = empty($time) ? date('Y-m-d', time()) : date('Y-', time()) . $time;
        $json = file_get_contents(((((('http://www.twototwo.cn/train/Service.aspx?format=json&action=QueryTrainScheduleByTwoStation&key=c3e2c03e-4a93-41f0-8ebe-dbadd7ea7858&startStation=' . $data[0]) . '&arriveStation=') . $data[1]) . '&startDate=') . $data[2]) . '&ignoreStartDate=0&like=1&more=0');
        if ($json) {
            $data = json_decode($json);
            $main = $data->Response->Main->Item;
            if (count($main) > 10) {
                $conunt = 10;
            } else {
                $conunt = count($main);
            }
            for ($i = 0; $i < $conunt; $i++) {
                $str .= ((((((((('n 【编号】' . $main[$i]->CheCiMingCheng) . 'n 【类型】') . $main[$i]->CheXingMingCheng) . 'n【发车时间】:　') . $time) . ' ') . $main[$i]->FaShi) . 'n【耗时】') . $main[$i]->LiShi) . ' 小时';
                $str .= 'n----------------------';
            }
        } else {
            $str = ((('没有找到 ' . $name) . ' 至 ') . $toname) . ' 的列车';
        }
        return $str;
    }
    public function fanyi($name)
    {
        $name = array_merge($name);
        $url = ('http://openapi.baidu.com/public/2.0/bmt/translate?client_id=kylV2rmog90fKNbMTuVsL934&q=' . $name[0]) . '&from=auto&to=auto';
        $json = Http::fsockopenDownload($url);
        if ($json == false) {
            $json = file_get_contents($url);
        }
        $json = json_decode($json);
        $str = $json->trans_result;
        if ($str[0]->dst == false) {
            return $this->error_msg($name[0]);
        }
        $mp3url = 'http://www.apiwx.com/aaa.php?w=' . $str[0]->dst;
        return array(array($str[0]->src, $str[0]->dst, $mp3url, $mp3url), 'music');
    }
    public function caipiao($name)
    {
        $name = array_merge($name);
        $url = 'http://api2.sinaapp.com/search/lottery/?appkey=0020130430&appsecert=fa6095e113cd28fd&reqtype=text&keyword=' . $name[0];
        $json = Http::fsockopenDownload($url);
        if ($json == false) {
            $json = file_get_contents($url);
        }
        $json = json_decode($json, true);
        $str = $json['text']['content'];
        return $str;
    }
    public function mengjian($name)
    {
        $name = array_merge($name);
        if (empty($name)) {
            return '周公睡着了,无法解此梦,这年头神仙也偷懒';
        }
        $data = M('Dream')->field('content')->where(('`title` LIKE \'%' . $name[0]) . '%\'')->find();
        if (empty($data)) {
            return '周公睡着了,无法解此梦,这年头神仙也偷懒';
        }
        return $data['content'];
    }
    public function gupiao($name)
    {
        $name = array_merge($name);
        $url = 'http://api2.sinaapp.com/search/stock/?appkey=0020130430&appsecert=fa6095e113cd28fd&reqtype=text&keyword=' . $name[0];
        $json = Http::fsockopenDownload($url);
        if ($json == false) {
            $json = file_get_contents($url);
        }
        $json = json_decode($json, true);
        $str = $json['text']['content'];
        return $str;
    }
    public function getmp3($data)
    {
        $obj = new getYu();
        $ContentString = $obj->getGoogleTTS($data);
        $randfilestring = ((('mp3/' . time()) . '_') . sprintf('%02d', rand(0, 999))) . '.mp3';
        file_put_contents($randfilestring, $ContentString);
        return rtrim(C('site_url'), '/') . $randfilestring;
    }
    public function xiaohua()
    {
        $name = implode('', $n);
        $str = $this->myapi . urlencode('笑话');
        //$json = json_decode(file_get_contents($str));
        //$reply = urldecode($json->content);
		$reply = urldecode(file_get_contents($str));
        $reply = str_replace('{br}', 'n', $reply);
        return $reply;
    }
    public function liaotian($name)
    {
        $name = array_merge($name);
        $this->chat($name[0]);
    }
    public function chat($name)
    {
        $this->requestdata('textnum');
        $check = $this->user('connectnum');
        if ($check['connectnum'] != 1) {
            return array(C('connectout'), 'text');
        }
        if ($name == '糗事') {
            $name = '笑话';
        }
        $str = $this->myapi . urlencode($name);
        //$json = json_decode(file_get_contents($str));
        //$reply = urldecode($json->content);
		$reply = urldecode(file_get_contents($str));
        $reply = str_replace('{br}', 'n', $reply);
        $reply = str_replace('小九', $this->my, $reply);
        if (stristr($reply, '还不能理解')) {
            $other = M('Other')->where(array('token' => $this->token))->find();
            if ($other == false) {
                
            } else {
                if (empty($other['keyword'])) {
                    if ($other['info']) {
                        return array($other['info'], 'text');
                    }
                } else {
                    if ($other['keyword'] == '首页' || $other['keyword'] == 'home') {
                        return $this->shouye();
                    }
                    $back = M('Img')->field('id,text,pic,url,title')->limit(5)->order('id desc')->where(array('token' => $this->token, 'keyword' => array('like', ('%' . $other['keyword']) . '%')))->select();
                    if ($back == false) {
                        return array('无此图文信息,请提醒商家，重新设定关键词', 'text');
                    }
                    foreach ($back as $keya => $infot) {
                        if ($infot['url'] != false) {
                            $url = $this->getFuncLink($infot['url']);
                        } else {
                            $url = rtrim(C('site_url'), '/') . U('Wap/Index/content', array('token' => $this->token, 'id' => $infot['id'], 'wecha_id' => $this->data['FromUserName']));
                        }
                        $return[] = array($infot['title'], $infot['text'], $infot['pic'], $url);
                    }
                    return array($return, 'news');
                }
            }
        }
        return array($reply, 'text');
    }
    public function fistMe($data)
    {
        if ('event' == $data['MsgType'] && 'subscribe' == $data['Event']) {
            return $this->help();
        }
    }
    public function help()
    {
        $data = M('Areply')->where(array('token' => $this->token))->find();
        return array(preg_replace('/(1512)|(15)|(12)/', 'n', $data['content']), 'text');
    }
    public function error_msg($data)
    {
        return ('没有找到' . $data) . '相关的数据';
    }
    
    /**
     * 请求数验证及添加请求数
     * @param unknown $action
     * @param string $keyword
     * @return number
     */
    public function user($action, $keyword = '')
    {
        $user = M('Wxuser')->field('uid')->where(array('token' => $this->token))->find();
        $usersdata = M('Users');
        $dataarray = array('id' => $user['uid']);
        $users = $usersdata->field('gid,diynum,connectnum,activitynum,viptime')->where(array('id' => $user['uid']))->find();
        $group = M('User_group')->where(array('id' => $users['gid']))->find();
        if ($users['diynum'] < $group['diynum']) {
            $data['diynum'] = 1;
            if ($action == 'diynum') {
                $usersdata->where($dataarray)->setInc('diynum');
            }
        }
        if ($users['connectnum'] < $group['connectnum']) {
            $data['connectnum'] = 1;
            if ($action == 'connectnum') {
                $usersdata->where($dataarray)->setInc('connectnum');
            }
        }
        if ($users['viptime'] > time()) {
            $data['viptime'] = 1;
        }
        return $data;
    }
    /**
     * 记录公众号的请求日志信息
     */
    public function requestdata($field)
    {
        $data['year'] = date('Y');
        $data['month'] = date('m');
        $data['day'] = date('d');
        $data['token'] = $this->token;
        $mysql = M('Requestdata');
        $check = $mysql->field('id')->where($data)->find();
        if ($check == false) {
            $data['time'] = time();
            $data[$field] = 1;
            $mysql->add($data);
        } else {
            $mysql->where($data)->setInc($field);
        }
    }
    public function behaviordata($field, $id = '', $type = '')
    {
        $data['date'] = date('Y-m-d', time());
        $data['token'] = $this->token;
        $data['openid'] = $this->data['FromUserName'];
        $data['keyword'] = $this->data['Content'];
        $data['model'] = $field;
        if ($id != false) {
            $data['fid'] = $id;
        }
        if ($type != false) {
            $data['type'] = 1;
        }
        $mysql = M('Behavior');
        $check = $mysql->field('id')->where($data)->find();
        $this->updateMemberEndTime($data['openid']);
        if ($check == false) {
            $data['enddate'] = time();
            $mysql->add($data);
        } else {
            $mysql->where($data)->setInc('num');
        }
    }
    public function updateMemberEndTime($openid)
    {
        $mysql = M('Wehcat_member_enddate');
        $id = $mysql->field('id')->where(array('openid' => $openid))->find();
        $data['enddate'] = time();
        $data['openid'] = $openid;
        if ($id == false) {
            $mysql->add($data);
        } else {
            $data['id'] = $id;
            $mysql->save($data);
        }
    }
    public function baike($name)
    {
        $name = implode('', $name);
        $name_gbk = iconv('utf-8', 'gbk', $name);
        $encode = urlencode($name_gbk);
        $url = ('http://baike.baidu.com/list-php/dispose/searchword.php?word=' . $encode) . '&pic=1';
        $get_contents = $this->httpGetRequest_baike($url);
        $get_contents_gbk = iconv('gbk', 'utf-8', $get_contents);
        preg_match('/URL=(\\S+)\'>/s', $get_contents_gbk, $out);
        $real_link = 'http://baike.baidu.com' . $out[1];
        $get_contents2 = $this->httpGetRequest_baike($real_link);
        preg_match('#"Description"\\scontent="(.+?)"\\s\\/\\>#is', $get_contents2, $matchresult);
        if (isset($matchresult[1]) && $matchresult[1] != '') {
            return htmlspecialchars_decode($matchresult[1]);
        } else {
            return ('抱歉，没有找到与“' . $name) . '”相关的百科结果。';
        }
    }
    public function api_notice_increment($url, $data)
    {
        $ch = curl_init();
        $header = 'Accept-Charset: utf-8';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
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
        } else {
            return $tmpInfo;
        }
    }
    public function httpGetRequest_baike($url)
    {
        $headers = array('User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:14.0) Gecko/20100101 Firefox/14.0.1', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-us,en;q=0.5', 'Referer: http://www.baidu.com/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        curl_close($ch);
        if ($output === FALSE) {
            return 'cURL Error: ' . curl_error($ch);
        }
        return $output;
    }
    public function get_tags($title, $num = 10)
    {
        vendor('Pscws.Pscws4', '', '.class.php');
        $pscws = new PSCWS4();
        $pscws->set_dict(CONF_PATH . 'etc/dict.utf8.xdb');
        $pscws->set_rule(CONF_PATH . 'etc/rules.utf8.ini');
        $pscws->set_ignore(true);
        $pscws->send_text($title);
        $words = $pscws->get_tops($num);
        $pscws->close();
        $tags = array();
        foreach ($words as $val) {
            $tags[] = $val['word'];
        }
        return implode(',', $tags);
    }
	public function GetUrlToDomain($domain)
	{
		$re_domain = '';
		$domain_postfix_cn_array = array("com", "net", "org", "gov", "edu", "com.cn", "cn");
		$array_domain = explode(".", $domain);
		$array_num = count($array_domain) - 1;
		if ($array_domain[$array_num] == 'cn') {
			if (in_array($array_domain[$array_num - 1], $domain_postfix_cn_array)) {
				$re_domain = $array_domain[$array_num - 2] . "." . $array_domain[$array_num - 1] . "." . $array_domain[$array_num];
			} else {
				$re_domain = $array_domain[$array_num - 1] . "." . $array_domain[$array_num];
			}
		} else {
			$re_domain = $array_domain[$array_num - 1] . "." . $array_domain[$array_num];
		}
		return $re_domain;
	}
	public function Wewall()
	{
		$yyy = M('Wewall')->where(array('isact' => '1','token' => $this->token))->find();

		$welog=array();

		if ($yyy == false) {
		return array('目前商家未开启微信墙活动', 'text');
		}
		$openid=$this->data['FromUserName'];
		$exs= M('Wewalllog')->where(array('openid' => $openid,'token' => $this->token))->find();
		if($yyy['iflottery']=='1' && $exs==false){
			$welog['sncode']= uniqid();
		}
		$welog['content'] = $this->wallmessage;
		$welog['uid'] = $yyy['id'];
		$welog['token'] = $this->token;
		$welog['updatetime'] = time();
		$welog['ifsent']='0';
		$welog['ifscheck']='0';
		if($yyy['ifcheck']=='0'){
			$welog['ifcheck']='1';
		}else{
			$welog['ifcheck']='0';
		}
		if($exs==false){
			$welog['openid'] = $openid;
			M('Wewalllog')->add($welog);
			$sncode=$welog['sncode'];
		}else{
			M('Wewalllog')->where(array('openid' => $openid,'token' => $this->token))->save($welog);
			$sncode=$exs['sncode'];
		}

		if ($yyy['iflottery']=='1'){
		  return array('上墙成功！获得sn号码为['.$sncode.'],请留意抽奖环节哦','text');
		}else{
			return array('上墙成功！祝君万事如意','text');
		}
	}

    public function Shake()
    {
        $yyy = M('Shake')->where(array('isopen' => '1','token' => $this->token))->find();
        if($yyy == false){
            return array('目前没有正在进行中的摇一摇活动','text');
        }
        $url = C('site_url').U('Wap/Toshake/index',array('token'=>$this->token,'phone'=>$this->shakemessage,'wecha_id'=>$this->data['FromUserName']));
        return array('<a href="'.$url.'">点击进入刺激的现场摇一摇活动</a>','text');
    }
	
	 function handleIntro($str)
    {
        $str = html_entity_decode(htmlspecialchars_decode($str));
        $search = array('&amp;', '&quot;', '&nbsp;', '&gt;', '&lt;');
        $replace = array('&', '"', ' ', '>', '<');
        return strip_tags(str_replace($search, $replace, $str));
    }
	
	    private function dati()
    {
        $wecha_id = $_SESSION['wecha_id'];
        S($wecha_id, 'start', 1800);
        //清空用户上次的所有积分
        M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $wecha_id))->delete();
        return array('回复xzt开始选择题答题，回复jdt开始简答题，回复ktc开始看图猜,回复jf查询积分,回复top查询前五排名,回复help获得帮助,回复end结束游戏', 'text');
    }
    private function dati_start($content)
    {
        $now_time = time();
        $where['wecha_id'] = $_SESSION['wecha_id'];
        $where['token'] = $this->token;
        /*
        | -------------------------------------------
        | 记录答题者的信息
        | -------------------------------------------
        */
        $record = M('DatiRecord')->where($where)->find();
        if ($record == NULL) {
            M('DatiRecord')->add($where);
            $record = M('DatiRecord')->where($where)->find();
        }
        $condition['token'] = $where['token'];
        switch (strtolower($content)) {
        case 'jdt':
            //开始出题
            $condition['type'] = 1;
            if (S($record['id'] . '_did') !== null) {
                S($record['id'] . '_did', null);
            }
            $ak = M('Dati')->where($condition)->order('id DESC')->find();
            if ($ak !== null) {
                S($record['id'] . '_did', $ak['id'], 1800);
                S($record['id'] . '_daan', $ak['daan'], 1800);
                S($record['id'] . '_type', 1, 1800);
                S($record['id'] . '_score', $ak['score'], 1800);
                S($record['id'] . '_time', $now_time, 1800);
                //记录出题时间
                return array($ak['title'], 'text');
            } else {
                S($record['id'] . '_did', 'end', 1800);
                return array('题库还没题目哦！回复xzt开始选择题，回复ktc开始看图猜.', 'text');
            }
            break;
        case 'xzt':
            //开始出题
            $condition['type'] = 0;
            if (S($record['id'] . '_did') !== null) {
                S($record['id'] . '_did', null);
            }
            $ak = M('Dati')->where($condition)->order('id DESC')->find();
            if ($ak !== null) {
                S($record['id'] . '_did', $ak['id'], 1800);
                S($record['id'] . '_daan', $ak['daan'], 1800);
                S($record['id'] . '_type', 0, 1800);
                S($record['id'] . '_score', $ak['score'], 1800);
                S($record['id'] . '_time', $now_time, 1800);
                //记录出题时间
                return array($ak['title'], 'text');
            } else {
                S($record['id'] . '_did', 'end', 1800);
                return array('题库还没题目哦！先玩玩其他的吧！', 'text');
            }
            break;
        case 'ktc':
            //开始出题
            $condition['type'] = 2;
            if (S($record['id'] . '_did') !== null) {
                S($record['id'] . '_did', null);
            }
            $ak = M('Dati')->where($condition)->order('id DESC')->find();
            if ($ak !== null) {
                S($record['id'] . '_did', $ak['id'], 1800);
                S($record['id'] . '_daan', $ak['daan'], 1800);
                S($record['id'] . '_type', 2, 1800);
                S($record['id'] . '_score', $ak['score'], 1800);
                S($record['id'] . '_time', $now_time, 1800);
                //记录出题时间
                return array(array(array($ak['title'], $this->handleIntro($ak['info']), $ak['picurl'], '')), 'news');
            } else {
                S($record['id'] . '_did', 'end', 1800);
                return array('题库还没题目哦！先玩玩其他的吧！', 'text');
            }
            break;
        case 'end':
            S($_SESSION['wecha_id'], null);
            return array('您已经结束了一站到底游戏！您还可以关注我们其他好玩的游戏!', 'text');
            break;
        case 'jf':
            return array('您的积分为：' . $record['score'], 'text');
            break;
        case 'help':
            return array('回复xzt开始选择题答题，回复jdt开始简答题，回复ktc开始看图猜,回复jf查询积分,回复top查询前五排名,回复help获得帮助,回复end结束游戏', 'text');
            break;
        case 'top':
            $like['token'] = $this->token;
            $back = M('Dati_record')->limit(5)->order('score desc')->where($like)->select();
            if ($back == false) {
                return array('暂无排名信息', 'text');
            }
            $topstr = '';
            foreach ($back as $keya => $infot) {
                if ($_SESSION['wecha_id'] == $infot['wecha_id']) {
                    $topstr = ((($topstr . $infot['wecha_id']) . '(你)：') . $infot['score']) . "\r\n";
                } else {
                    $topstr = ((($topstr . $infot['wecha_id']) . '：') . $infot['score']) . "\r\n";
                }
            }
            return array('游戏排名：' . $topstr, 'text');
            break;
        default:
            if (S($record['id'] . '_did') !== 'end') {
                $condition['id'] = array('lt', S($record['id'] . '_did'));
                $condition['type'] = S($record['id'] . '_type');
                $ak = M('Dati')->where($condition)->order('id DESC')->find();
                if ($ak == null) {
                    //如果已经是最后一题
                    $return = '';
                    if (S($record['id'] . '_daan') !== '') {
                        $daan = S($record['id'] . '_daan');
                        $return .= ('正确答案是:' . S(($record['id'] . '_daan'))) . '-------------------';
                        switch (S($record['id'] . '_type')) {
                        case 0:
                            if (strtolower($content) == strtolower($daan)) {
                                $data['score'] = (int) $record['score'] + intval(S(($record['id'] . '_score')));
                                M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                            } else {
                                if ($record['score'] >= S($record['id'] . '_score')) {
                                    $data['score'] = (int) $record['score'] - intval(S(($record['id'] . '_score')));
                                    M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                                }
                            }
                            break;
                        case 1:
                            if (@stripos($content, $daan) !== false) {
                                $data['score'] = (int) $record['score'] + intval(S(($record['id'] . '_score')));
                                M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                            } else {
                                if ($record['score'] >= S($record['id'] . '_score')) {
                                    $data['score'] = (int) $record['score'] - intval(S(($record['id'] . '_score')));
                                    M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                                }
                            }
                            break;
                        case 2:
                            if (@stripos($content, $daan) !== false) {
                                $data['score'] = (int) $record['score'] + intval(S(($record['id'] . '_score')));
                                M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                            } else {
                                if ($record['score'] >= S($record['id'] . '_score')) {
                                    $data['score'] = (int) $record['score'] - intval(S(($record['id'] . '_score')));
                                    M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                                }
                            }
                            break;
                        }
                    }
                    S($record['id'] . '_did', 'end');
                    $return .= '你好棒！所有题目都答完了!回复xzt开始选择题答题，回复jdt开始简答题，回复ktc开始看图猜,回复jf查询积分,回复top查询前五排名,回复help获得帮助,回复end结束游戏';
                    return array($return, 'text');
                } elseif (S($record['id'] . '_type') == 2) {
                    $daan = S($record['id'] . '_daan');
                    if (@stripos($content, $daan) !== false) {
                        $data['score'] = (int) $record['score'] + intval(S(($record['id'] . '_score')));
                        M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                    } else {
                        if ($record['score'] > S($record['id'] . '_score')) {
                            $data['score'] = (int) $record['score'] - intval(S(($record['id'] . '_score')));
                            M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                        }
                    }
                    $info = (('正确答案：' . $daan) . '--------------------------') . $ak['info'];
                    $imgurl = $ak['picurl'];
                    $url = '';
                    S($record['id'] . '_daan', $ak['daan'], 1800);
                    S($record['id'] . '_did', $ak['id'], 1800);
                    return array(array(array($ak['title'], $this->handleIntro($info), $imgurl, $url)), 'news');
                } else {
                    $daan = S($record['id'] . '_daan');
                    switch (intval(S($record['id'] . '_type'))) {
                    case 0:
                        if (strtolower($content) == strtolower($daan)) {
                            $data['score'] = (int) $record['score'] + intval(S(($record['id'] . '_score')));
                            M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                        } else {
                            if ($record['score'] >= S($record['id'] . '_score')) {
                                $data['score'] = (int) $record['score'] - intval(S(($record['id'] . '_score')));
                                M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                            }
                        }
                        break;
                    case 1:
                        if (@stripos($content, $daan) !== false) {
                            $data['score'] = (int) $record['score'] + intval(S(($record['id'] . '_score')));
                            M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                        } else {
                            if ($record['score'] >= S($record['id'] . '_score')) {
                                $data['score'] = (int) $record['score'] - intval(S(($record['id'] . '_score')));
                                M('DatiRecord')->where(array('token' => $this->token, 'wecha_id' => $_SESSION['wecha_id']))->save($data);
                            }
                        }
                        break;
                    }
                }
                $return = (('正确答案：' . $daan) . ';-------------------------') . $ak['title'];
                S($record['id'] . '_daan', $ak['daan'], 1800);
                S($record['id'] . '_did', $ak['id'], 1800);
                return array($return, 'text');
            }
        }
        S($_SESSION['wecha_id'], null);
        return array('答题结束', 'text');
        break;
    }
	//获取用户信息
	public function getUserInfo($token,$wecha_id){
		
		$access_token = getAccessToken($token);
		if(!$access_token["status"]) {
			Log::write("获取accesstoken失败：".$access_token['info'], Log::DEBUG, 3, LOG_PATH.'userInfo.log');
			return ;
		}
		$access_token = $access_token["info"];
		
		//获取用户信息
		Log::write("获取用户基本信息openid：".$wecha_id, Log::DEBUG, 3, LOG_PATH.'userInfo.log');
		$info_url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$wecha_id.'&lang=zh_CN';
		$return_str = curlGet($info_url);
		Log::write("获取用户基本信息接口返回：".$return_str, Log::DEBUG, 3, LOG_PATH.'userInfo.log');
		$result=json_decode($return_str, true);
		if($result['openid']){
			$id = M('wxuser_people')->where(array('token' => $token,'wecha_id' => $wecha_id))->getField('id');
			if($id){
				$result['id'] = $id;
				$result['update_time'] = time();
				M('wxuser_people')->save($result);
			}else{
				$result['token'] = $token;
				$result['wecha_id'] = $wecha_id;
				$result['create_time'] = time();
				M('wxuser_people')->add($result);
			}
		}
		return $result;
	}
	
}
?>