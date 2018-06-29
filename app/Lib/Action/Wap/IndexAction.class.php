<?php
function strExists($haystack, $needle)
{
	return !(strpos($haystack, $needle) === FALSE);
}

class IndexAction extends Oauth2Action{
	public $wecha_id;
	private $tpl;	//微信公共帐号信息
	private $info;	//分类信息
	//private $wecha_id;
	private $copyright;
	public $company;
	public $token;
	public $weixinUser;
	public $homeInfo;
	public function _initialize(){
		parent::_initialize();
		//$this->wecha_id='oTRiCtzmS1oLd4CXpXD8L48M9O1Y';   //我的号
		define('RES', THEME_PATH . 'common');
        define('STATICS', TMPL_PATH . 'static');
        $this->assign('action', $this->getActionName());
		$agent = $_SERVER['HTTP_USER_AGENT']; 
		
		/* if(!strpos($agent,"icroMessenger")&&!isset($_GET['show'])) {
			echo '此功能只能在微信浏览器使用';exit;
		}
		 */
		//
		$Model = new Model();
		$rt=$Model->query("CREATE TABLE IF NOT EXISTS `tp_site_plugmenu` (
  `token` varchar(60) NOT NULL DEFAULT '',
  `name` varchar(20) NOT NULL DEFAULT '',
  `url` varchar(100) DEFAULT '',
  `taxis` mediumint(4) NOT NULL DEFAULT '0',
  `display` tinyint(1) NOT NULL DEFAULT '0',
  KEY `token` (`token`,`taxis`,`display`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");
		//
		$this->token=$this->_get('token','trim');
		$where['token']=$this->token;
		
		$tpl=D('Wxuser')->where($where)->find();
		$this->weixinUser=$tpl;
		
		if (isset($_GET['wecha_id'])&&$_GET['wecha_id']){
			$_SESSION['wecha_id']=$_GET['wecha_id'];
			$this->wecha_id=$this->_get('wecha_id');
		}
		if (isset($_SESSION['wecha_id'])){
			$this->wecha_id=$_SESSION['wecha_id'];
		}
		//dump($where);
		$fid=$this->_get('fid','intval');
		if($fid==""){$fid=0;}
		$info=M('Classify')->where(array('token'=>$this->_get('token'),'fid'=>$fid,'status'=>1))->order('sorts desc')->select();
		$info=$this->convertLinks($info);//加外链等信息
		//print_r($info);
		//exit;
		
		$gid=D('Users')->field('gid')->find($tpl['uid']);
		$this->userGroup=M('User_group')->where(array('id'=>$gid['gid']))->find();
		$this->copyright=$this->userGroup['iscopyright'];
		
		$this->info=$info;
		$tpl['color_id']=intval($tpl['color_id']);
		$this->tpl=$tpl;
		$company_db=M('company');
		$this->company=$company_db->where(array('token'=>$this->token,'isbranch'=>0))->find();
		$this->assign('company',$this->company);
		//
		$homeInfo=M('home')->where(array('token'=>$this->token))->find();
		$this->homeInfo=$homeInfo;
		$this->assign('iscopyright',$this->copyright);//是否允许自定义版权
		$this->assign('siteCopyright',C('copyright'));//站点版权信息
		$this->assign('homeInfo',$homeInfo);
		//
		$this->assign('token',$this->token);
		//
		
		$this->assign('copyright',$this->copyright);
		//plugmenus
		$plugMenus=$this->_getPlugMenu();
		$this->assign('plugmenus',$plugMenus);
		$this->assign('showPlugMenu',count($plugMenus));
	}
	
	
	public function classify(){
		$this->assign('info',$this->info);
		
		$this->display($this->tpl['tpltypename']);
	}
	
	public function index(){
		
		//
		if($this->info && $this->info[0]){
			if($this->info[0]['url'])
				$this->redirect(htmlspecialchars_decode($this->info[0]['url']));
			if(empty($url))
				$this->redirect('Wap/Index/lists', array('classid'=>$this->info[0]['id'],'fid'=>$this->info[0]['fid'],'token'=>$this->info[0]['token']));
		}
		//是否是高级模板
		if ($this->homeInfo['advancetpl']){
			echo '<script>window.location.href="/cms/index.php?token='.$this->token.'&wecha_id='.$this->wecha_id.'";</script>';
			exit();
		}
		//
		$where['token']=$this->_get('token');
		//dump($where);
		//	$where['status']=1;
		$where['classid']=0;
		
		$flash=M('Flash')->where($where)->select();
		$flash=$this->convertLinks($flash);
		$count=count($flash);
		$this->assign('flash',$flash);
		$this->assign('info',$this->info);
		$this->assign('num',$count);
		$this->assign('tpl',$this->tpl);
		$this->display($this->tpl['tpltypename']);
	}
	
	public function lists(){
		$where['token']=$this->_get('token','trim');
		$db = D('Img');	
		$where['classid']=$this->_get('classid','intval'); //分类ID
		$count = $db->where($where)->count();	
		$pageSize = 8;	
		$pagecount=ceil($count/$pageSize);
		$page = $this->_get('p', 'trim,intval');
		$p=0;
		if($page < 1){ $page = 1; }		
		if($page > $pagecount){$page = $pagecount;}
		if($page >= 1){$p=($page-1)*$pageSize;}
		$res=$db->where($where)->order('createtime DESC')->limit("$p,$pageSize")->select();
		//$res=$db->where($where)->order('createtime DESC')->select();
		$res=$this->convertLinks($res);
		/// 加载幻灯片
		$flash=M('Flash')->where(array('token'=>$where['token'], 'classid'=>'0'))->select();
		$flash=$this->convertLinks($flash);
		$this->assign('flash',$flash);
		
		$this->assign('page',$pagecount);
		$this->assign('p',$page);
		$this->assign('info',$this->info);
		$init_num = 0;
		foreach($this->info as $k =>$item){
			if($item['id'] == $where['classid'])
				$init_num = $k;
		}
		$this->assign('init_num', $init_num);
		$this->assign('tpl',$this->tpl);
		$this->assign('res',$res);
		$this->assign('copyright',$this->copyright);
		
		/* if ($count==1){
			$this->content($res[0]['id']);
			exit();
		} */
		
		$this->display($this->tpl['tpllistname']);
	}
	
	public function content($contentid=0){
		$db=M('Img');
		$where['token']=$this->_get('token','trim');
		if (!$contentid){
			$contentid=intval($_GET['id']);
		}
		$where['id']=array('neq',$contentid);
		$lists=$db->where($where)->limit(5)->order('uptatetime')->select();
		$where['id']=$contentid;
		$res=$db->where($where)->find();
		//定义分享内容
		$config = getWXJSConfig($this->_get('token','trim'));
		//$config['debug']=true;  //调试模式
		$config['jsApiList']=array('onMenuShareTimeline','onMenuShareAppMessage');
		$this->assign("config", json_encode($config));
		//
		//判断是否关注该公众号
		//p($this->wecha_id);
		$isSub=M('wxuser_people')->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
		$this->assign('isSub',$isSub);	//分类信息
		//
		M('Img')->where(array('id'=>$contentid))->setInc('click',1);//访问量+1
		//评论内容
		$map['c.token']=$this->token;
		$map['c.imgid']=$_GET['id'];
		$map['c.status']=1;
		$commentInfo =M()->table("tp_comment c")-> join('tp_wxuser_people p on c.wecha_id=p.wecha_id')->where($map)->order('c.createtime DESC')->field("c.*,p.nickname,p.headimgurl")->select();
		$this->assign('commentInfo',$commentInfo);
		//p($commentInfo);
		//exit;
		//
		$this->assign('info',$this->info);	//分类信息
		$this->assign('lists',$lists);		//列表信息
		$this->assign('res',$res);			//内容详情;
		$this->assign('tpl',$this->tpl);				//微信帐号信息
		$this->assign('copyright',$this->copyright);	//版权是否显示
		$this->display($this->tpl['tplcontentname']);
	}
	
	public function flash(){
		$where['token']=$this->_get('token','trim');
		$flash=M('Flash')->where($where)->select();
		$count=count($flash);
		$this->assign('flash',$flash);
		$this->assign('info',$this->info);
		$this->assign('num',$count);
		$this->display('ty_index');
	}
	/**
	 * 获取链接
	 *
	 * @param unknown_type $url
	 * @return unknown
	 */
	public function getLink($url){
		$urlArr=explode(' ',$url);
		$urlInfoCount=count($urlArr);
		if ($urlInfoCount>1){
			$itemid=intval($urlArr[1]);
		}
		//会员卡 刮刮卡 团购 商城 大转盘 优惠券 订餐 商家订单 表单
		if (strExists($url,'刮刮卡')){
			$link='/index.php?g=Wap&m=Guajiang&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
			if ($itemid){
				$link.='&id='.$itemid;
			}
		}elseif (strExists($url,'大转盘')){
			$link='/index.php?g=Wap&m=Lottery&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
			if ($itemid){
				$link.='&id='.$itemid;
			}
		}elseif (strExists($url,'优惠券')){
			$link='/index.php?g=Wap&m=Coupon&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
			if ($itemid){
				$link.='&id='.$itemid;
			}
		}elseif (strExists($url,'刮刮卡')){
			$link='/index.php?g=Wap&m=Guajiang&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
			if ($itemid){
				$link.='&id='.$itemid;
			}
		}elseif (strExists($url,'商家订单')){
			if ($itemid){
				$link=$link='/index.php?g=Wap&m=Host&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id.'&hid='.$itemid;
			}else {
				$link='/index.php?g=Wap&m=Host&a=Detail&token='.$this->token.'&wecha_id='.$this->wecha_id;
			}
		}elseif (strExists($url,'万能表单')){
			if ($itemid){
				$link=$link='/index.php?g=Wap&m=Selfform&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id.'&id='.$itemid;
			}
		}elseif (strExists($url,'相册')){
			$link='/index.php?g=Wap&m=Photo&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
			if ($itemid){
				$link='/index.php?g=Wap&m=Photo&a=plist&token='.$this->token.'&wecha_id='.$this->wecha_id.'&id='.$itemid;
			}
		}elseif (strExists($url,'全景')){
			$link='/index.php?g=Wap&m=Panorama&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
			if ($itemid){
				$link='/index.php?g=Wap&m=Panorama&a=item&token='.$this->token.'&wecha_id='.$this->wecha_id.'&id='.$itemid;
			}
		}elseif (strExists($url,'会员卡')){
			$link='/index.php?g=Wap&m=Card&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
		}elseif (strExists($url,'商城')){
			$link='/index.php?g=Wap&m=Product&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
		}elseif (strExists($url,'订餐')){
			$link='/index.php?g=Wap&m=Product&a=dining&dining=1&token='.$this->token.'&wecha_id='.$this->wecha_id;
		}elseif (strExists($url,'团购')){
			$link='/index.php?g=Wap&m=Groupon&a=grouponIndex&token='.$this->token.'&wecha_id='.$this->wecha_id;
		}elseif (strExists($url,'首页')){
			//$link='/index.php?g=Wap&m=Index&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
			$link='/index.php?g=Wap&m=Index&a=index&token='.$this->token;
		}elseif (strExists($url,'网站分类')){
			$link='/index.php?g=Wap&m=Index&a=lists&token='.$this->token;
			//$link='/index.php?g=Wap&m=Index&a=lists&token='.$this->token.'&wecha_id='.$this->wecha_id;
			if ($itemid){
				//$link='/index.php?g=Wap&m=Index&a=lists&token='.$this->token.'&wecha_id='.$this->wecha_id.'&classid='.$itemid;
				$link='/index.php?g=Wap&m=Index&a=lists&token='.$this->token.'&classid='.$itemid;
			}
		}elseif (strExists($url,'图文回复')){
			if ($itemid){
				$link='/index.php?g=Wap&m=Index&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id.'&id='.$itemid;
			}
		}elseif (strExists($url,'LBS信息')){
			$link='/index.php?g=Wap&m=Company&a=map&token='.$this->token.'&wecha_id='.$this->wecha_id;
			if ($itemid){
				$link='/index.php?g=Wap&m=Company&a=map&token='.$this->token.'&wecha_id='.$this->wecha_id.'&companyid='.$itemid;
			}
		}elseif (strExists($url,'DIY宣传页')){
			$link='/index.php/show/'.$this->token;
		}elseif (strExists($url,'婚庆喜帖')){
			if ($itemid){
				$link='/index.php?g=Wap&m=Wedding&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id.'&id='.$itemid;
			}
		}elseif (strExists($url,'投票')){
			if ($itemid){
				$link='/index.php?g=Wap&m=Vote&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id.'&id='.$itemid;
			}
		}else {
			$link=str_replace(array('{wechat_id}','{siteUrl}'),array($this->wecha_id,C('site_url')),$url);
			if (!strpos($url,'wecha_id=')){
				if (strpos($url,'?')){
					$link=$link.'&wecha_id='.$this->wecha_id;
				}else {
					$link=$link.'?wecha_id='.$this->wecha_id;
				}
			}
			
		}
		return $link;
	}
	public function convertLinks($arr){
		$i=0;
		foreach ($arr as $a){
			if ($a['url']){
				$arr[$i]['url']=$this->getLink($a['url']);
			}
			//if($a['uid']==""){
			//	$f=M('Classify')->where(array('fid'=>$a['id']))->order('sorts desc')->find();
			//	if ($f['id']>0){
					/* $arr[$i]['url']='/index.php?g=Wap&m=Index&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id.'&fid='.$f['fid']; */
					
			//		$arr[$i]['url']='/index.php?g=Wap&m=Index&a=index&token='.$this->token.'&fid='.$f['fid'];
			//	}
			//	unset($f);
			//}
			$i++;
		}
		return $arr;
	}
	public function _getPlugMenu(){
		$company_db=M('company');
		$this->company=$company_db->where(array('token'=>$this->token,'isbranch'=>0))->find();
		$plugmenu_db=M('site_plugmenu');
		$plugmenus=$plugmenu_db->where(array('token'=>$this->token,'display'=>1))->order('taxis ASC')->limit('0,4')->select();
		if ($plugmenus){
			$i=0;
			foreach ($plugmenus as $pm){
				switch ($pm['name']){
					case 'tel':
						if (!$pm['url']){
							$pm['url']='tel:/'.$this->company['tel'];
						}else {
							$pm['url']='tel:/'.$pm['url'];
						}
						break;
					case 'memberinfo':
						if (!$pm['url']){
							$pm['url']='/index.php?g=Wap&m=Userinfo&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
						}
						break;
					case 'nav':
						if (!$pm['url']){
							$pm['url']='/index.php?g=Wap&m=Company&a=map&token='.$this->token.'&wecha_id='.$this->wecha_id;
						}
						break;
					case 'message':
						break;
					case 'share':
						break;
					case 'home':
						if (!$pm['url']){
							$pm['url']='/index.php?g=Wap&m=Index&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
						}
						break;
					case 'album':
						if (!$pm['url']){
							$pm['url']='/index.php?g=Wap&m=Photo&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
						}
						break;
					case 'email':
						$pm['url']='mailto:'.$pm['url'];
						break;
					case 'shopping':
						if (!$pm['url']){
							$pm['url']='/index.php?g=Wap&m=Product&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id;
						}
						break;
					case 'membercard':
						$card=M('member_card_create')->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
						if (!$pm['url']){
							if($card==false){
								$pm['url']=rtrim(C('site_url'),'/').U('Wap/Card/get_card',array('token'=>$this->token,'wecha_id'=>$this->wecha_id));
							}else{
								$pm['url']=rtrim(C('site_url'),'/').U('Wap/Card/vip',array('token'=>$this->token,'wecha_id'=>$this->wecha_id));
							}
						}
						break;
					case 'activity':
						$pm['url']=$this->getLink($pm['url']);
						break;
					case 'weibo':
						break;
					case 'tencentweibo':
						break;
					case 'qqzone':
						break;
					case 'wechat':
						$pm['url']='weixin://addfriend/'.$this->weixinUser['wxid'];
						break;
					case 'music':
						break;
					case 'video':
						break;
					case 'recommend':
						$pm['url']=$this->getLink($pm['url']);
						break;
					case 'other':
						$pm['url']=$this->getLink($pm['url']);
						break;
				}
				$plugmenus[$i]=$pm;
				$i++;
			}
			
		}else {//默认的
			$plugmenus=array();
			/*
			$plugmenus=array(
			array('name'=>'home','url'=>'/index.php?g=Wap&m=Index&a=index&token='.$this->token.'&wecha_id='.$this->wecha_id),
			array('name'=>'nav','url'=>'/index.php?g=Wap&m=Company&a=map&token='.$this->token.'&wecha_id='.$this->wecha_id),
			array('name'=>'tel','url'=>'tel:'.$this->company['tel']),
			array('name'=>'share','url'=>''),
			);
			*/
		}
		return $plugmenus;
	}
}

