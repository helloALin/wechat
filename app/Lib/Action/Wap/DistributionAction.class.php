<?php
class DistributionAction extends Oauth2Action{
	public $token;
	private $storeInfo;
	private $storeID;
	private $access_token;
	private $text;
	
	public function _initialize(){	
		$this->token = $this->_get('token');
		if(__ACTION__ != 'weixin'){
			//$this->getOpenId();
			$this->wecha_id='oTRiCtzmS1oLd4CXpXD8L48M9O1Y';   //我的号
			//$this->wecha_id='oTRiCt2TQuT2ZwBuz3IARETH0-fA';
			//$this->wecha_id='oTRiCt0Iuem5Z9jRPEW7n6quUvos';  //敏强的号
			//$this->wecha_id='oTRiCtxE-shZzn6eQblGq6hZS6L0';   //我的小号（下级）
			//======
			define('RES', THEME_PATH . 'common');
	        define('STATICS', TMPL_PATH . 'static');
			$this->assign('wecha_id',$this->wecha_id);
			$agent = $_SERVER['HTTP_USER_AGENT']; 
			if(!strpos($agent,'MicroMessenger')) {
				//echo '此功能只能在微信浏览器中使用';exit;
			}
			$this->token	= $this->_get('token'); //'qikwvz1409638161';//
			$this->storeID	= session('assist_storeId_'.$this->token);
			//没有Token
			if(empty($this->token)){exit;}
			$this->assign('token',$this->token);		
			$this->assign('staticFilePath',str_replace('./','/',THEME_PATH.'common/css/product'));
			//
			$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
			$this->assign('vipMap',$vipMap);
			//版权信息
			//版权信息
			$data=D('Adma');
			$adma=$data->where(array('token'=>$this->token))->find();
			$this->assign('adma',$adma);
			$this->assign('siteCopyright',$adma['copyright']);//站点版权信息
			//定义自定义分享
			$config = getWXJSConfig($this->token);
			//$config['debug']=true;  //调试模式
			$config['jsApiList']=array('onMenuShareTimeline','onMenuShareAppMessage');
			$this->assign("config", json_encode($config));
			//定义分享的标题,描述
			$imgUrl=C('site_url')."/tpl/static/share/images/logo2.jpg";
			$title="新盈利平台";
			$desc="轻轻松松--玩出财富来";
			$this->assign('imgUrl',$imgUrl);
			$this->assign('title',$title);
			$this->assign('desc',$desc);
		}
	}
	
	private function getOpenId(){
		$this->wxuser = S('assist_public_' . $this->token);
		if (empty($this->wxuser) || empty($this->wxuser['appid'])) {
			$this->wxuser = M('AssistPublic')->find($this->token);
			S('assist_public_' . $this->token, $this->wxuser, 86400);
		}
		if (!session('assist_openid_'.$this->token) && !isset($_GET['code'])) {
			$this->redirectToWeixin($this->wxuser['appid'], 1);
		}
		if (isset($_GET['code'])) {
			$rt = $this->curlGet('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->wxuser['appid'] . '&secret=' . $this->wxuser['appsecret'] . '&code=' . $_GET['code'] . '&grant_type=authorization_code');
			Log::write("assist public init code:".$_GET['code'].'response str: '. $rt);
			$jsonrt = json_decode($rt, 1);
			$this->wecha_id = $jsonrt['openid'];
			session('assist_openid_'.$this->token, $jsonrt['openid']);
			$retry_count = (int) $_GET['state'];
			if(empty($jsonrt["openid"])){
				Log::write("assist public get user openId json: ".$rt." retry count: ".$retry_count);
				if($retry_count > 0 && $retry_count < 4)
					$this->redirectToWeixin($this->wxuser['appid'], $retry_count + 1);
				else{
					echo "获取用户信息失败！";exit;
				}
			}
		} else {
			$this->wecha_id = session('assist_openid_'.$this->token);
		}
	}
	
	private function getAccessToken(){
		if(empty($this->access_token)){
			$data = S('assist_public_' . $this->token);
			if (empty($data) || empty($data['appid'])) {
				$data = M('AssistPublic')->find($this->token);
				S('assist_public_' . $this->token, $data, 86400);
			}
			$url_get = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $data['appid'] . '&secret=' . $data['appsecret'];
			$token = curlGet($url_get);
			$json = json_decode($token);
			if($json && $json->access_token){
				$this->access_token = $json->access_token;
			}else{
				Log::write('获取accessToken失败：'.$token);
			}
		}
		return $this->access_token;
	}
	
	function weixin(){
		$wechat = new Wechat($this->token);
		$data = $wechat->request();
		
		
	}
	
	function index(){
		$where = array('openid'=>$this->wecha_id, 'token'=>$this->token);
		$model = M('AssistBind');
		$this->storeID = $model->where($where)->getField('store_id');
		if($this->storeID){
			//显示直1，间1，间2新增了多少
			$direct1=M('rebate_record')->where(array('storeid'=>$this->storeID,'level'=>1))->order('recordtime desc')->getField('price');
			$indirect1=M('rebate_record')->where(array('storeid'=>$this->storeID,'level'=>2))->order('recordtime desc')->getField('price');
			$indirect2=M('rebate_record')->where(array('storeid'=>$this->storeID,'level'=>3))->order('recordtime desc')->getField('price');
			$this->assign('direct1', $direct1);
			$this->assign('indirect1', $indirect1);
			$this->assign('indirect2', $indirect2);
			//
			$info = M('store')->field('id,status')->find($this->storeID);
			if($info && $info['status'] == 1){
				session('assist_storeId_'.$this->token, $this->storeID);
				$this->getStoreInfo();
				//获取各级别客户数
				$customer = array('level1'=>0, 'level2'=>0, 'level3'=>0, 'count'=>0);
				$map['fid|gid|ggid']=$this->storeID;
				$map['token']=$this->token;
				$customers=M('store')->field("id,fid,gid,ggid")->where($map)->select();
				foreach($customers as &$cust){
					if($cust['fid'] == $this->storeID) {$customer['level1'] ++;}
					if($cust['gid'] == $this->storeID) {$customer['level2'] ++;}
					if($cust['ggid'] == $this->storeID){$customer['level3'] ++;}
					$customer["count"]++;
				}
				$this->assign('customer', $customer);
				$this->display();
			}else{
				$model->where($where)->delete();
				$this->error('用户信息不存在或未成为代理商，请联系管理员', U('Wap/Distribution/index', array('token'=>$this->token)));
			}
		}else{
			$this->assign('info', M('AssistPublic')->find($this->token));
			session('assist_storeId_'.$this->token, null);
			$this->display('bind');
		}
	}
	
	function bind(){
		if(!IS_POST)
			$this->success('操作失败！', U('Wap/Distribution/index', array('token'=>$this->token)));
		
		$store_id 	= $this->_post('store_id', 'trim');
		$code 		= $this->_post('code', 'trim');
		$warnMsg = '用户ID或标识码不正确，请确认是否正确再重试！';
		if(!empty($store_id) && !empty($code)){
			$store = M('Store')->where(array('token'=>$this->token, 'id'=>$store_id))->field('wecha_id,status')->find();
			//用户ID正确性验证
			if($store['status'] != 1){
				$warnMsg = '对不起，您未成为我们的代理商，无法进行绑定';
			}else if(!empty($store) && $code == substr($store['wecha_id'], 10, 6)){
				$data = array('openid'=>$this->wecha_id, 'token'=>$this->token);
				$model = M('AssistBind');
				//是否绑定验证
				if($model->where($data)->getField('openid')){
					$warnMsg = '该用户已经绑定过了，不能再进行绑定！';
				}else{
					$data['store_id'] = $store_id;
					$data['create_time'] = time();
					if($model->add($data)){
						$this->success('绑定成功！', U('Wap/Distribution/index', array('token'=>$this->token)));
						exit;
					} else {
						Log::write('绑定失败！用户信息：'.$name.' openid:'.$this->wecha_id);
						$warnMsg = '绑定失败！';
					}
				}
			}
		}
		$this->error($warnMsg);
	}
	

	//我的客户
	public function myCustomer(){
		//个人信息
		$this->assign('peppleInfo',$this->storeInfo);
		//我的客户
		$this->customer();
		$this->display();
	}
	
	//我的分级客户
	public function myClient(){
		$this->customer();
		$this->display();
	}
	
	//我的分销订单
	public function myDisOrders(){
		//是店主才有分销订单
		$this->getStoreInfo();
		//订单信息
		$map['store_id'] = $this->storeID;
		//$map['wecha_id']=$this->wecha_id;  //这个是自己的wechat_id
		$map['token']=$this->token;
		if($_GET['finish'])
		{
			$map['ostatus']=4;
		}
		else{
			$map['ostatus']=array('neq',4);
		}
		$disOrders=M('product_cart')->where($map)->select();
		//===========
		$this->assign('disOrders',$disOrders);
		$this->display();
	}
	
	//申请提现
	public function withdrawMoney(){
		Log::write("access_token：".$this->getAccessToken());
		/*
		Log::write("个人结算平台openid:".$this->wecha_id);
		if($this->wecha_id!='oTRiCtzmS1oLd4CXpXD8L48M9O1Y'){
			$this->error('提现功能还没开放，敬请期待！',U('Distribution/index',array('token'=>$this->token)));
		}
		*/
		//读取配置表
		$setInfo = M('distribution_set')->where(array('token'=>$this->token))->find();
		if(empty($setInfo) || empty($setInfo['cash'])){
			$this->error('商家未设置兑换比例，请稍后再试',U('Distribution/set',array('token'=>$this->token)));
		}
	
		if (IS_POST){
			if($this->_post('money', 'trim,intval') <= 0) {
				$this->error('提现金额必须要大于0！');
			}
			if(empty($this->storeID)) {
				$this->error('操作失败，请重新访问再进行尝试！');
			}
			$model = M('Store');
			$model->startTrans();
			$storeInfo = $model->lock(true)->find($this->storeID);  //微店铺表
			if($storeInfo['status'] != '1'){
				$this->error('您未通过审核或者未成为代理！');
			}
			$data['token'] 		=  $this->token;
			$data['store_id'] 	=  $this->storeID;
			$data['truename'] 	=  $this->_post('truename');
			$data['tel'] 		=  $this->_post('tel');
			$data['wecha_id'] 	=  $this->wecha_id;
			$data['withdrawWay']=  $this->_post('withdrawWay');
			//$data['zhifubao'] 	=  $this->_post('zhifubao');
			$data['bankname'] 	=  $this->_post('bankname');
			$data['subbankname'] 	=  $this->_post('subbankname');
			$data['bankcard'] 	=  $this->_post('bankcard');
			$data['money'] 		=  $this->_post('money', 'trim,intval');
			$data['extract'] 	=  $data['money'] * $setInfo['cash']; //算出需要的多多币
			$data['createtime'] =  time();
			$data['status'] 	=  0;   //状态 0未处理 1提现成功 2提现失败
			$refresh_url = U('Wap/Distribution/withdrawMoney', array('token'=>$this->token));
			if($storeInfo['income'] >= $data['extract']){
				//提现成功，先扣除对应的多多币
				if(M('withdraw_details')->add($data) 
						&& $model->where(array('id'=>$this->storeID))->setDec('income', $data['extract'])
						&& $model->commit()){
						//=========
						$url='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';
						$str=array('first'=>array('value'=>urlencode("恭喜您，您的提现申请已经提交！"),'color'=>"#173177"),'keyword1'=>array('value'=>urlencode($this->_post('bankname')),'color'=>"#FF0000"),'keyword2'=>array('value'=>urlencode($this->_post('bankcard')),'color'=>"#C4C400"),'keyword3'=>array('value'=>urlencode($this->_post('money', 'trim,intval')),'color'=>"#0000FF"),'keyword4'=>array('value'=>urlencode(date("Y-m-d H:i:s")),'color'=>"#0000FF"),'keyword5'=>array('value'=>urlencode("审核中"),'color'=>"#0000FF"),'remark'=>array('value'=>urlencode("\\n尊敬的客户：您的微币提现申请已成功提交，每月，1、11、21日结算，工作日将于24小时内到账，如遇节假日则顺延"),'color'=>"#008000"));
						
						$access_token = $this->getAccessToken();
						$data='{"touser":"'.$this->wecha_id.'","template_id":"9rxZEhwNjENNowBmSyw9QNibZLD76z8dWlh2lJ-gy-0","url":"","topcolor":"#FF0000","data":'.urldecode(json_encode($str)).'}';
						$url=$url.$access_token;
						$this->api_notice_increment($url,$data);
						Log::write("提现被推送人openid：".$storeInfo['wecha_id']);
						Log::write("this openid：".$this->wecha_id);
						Log::write("access_token：".$this->getAccessToken());
						//============	
					    $this->success('提交申请成功，等待管理员处理！', $refresh_url);
						
				}else{
					$model->rollback();
					Log::write("提现申请失败：".$model->getLastSql());
					$this->success('提交申请失败！', $refresh_url);
				}
			}else{
				$model->rollback();
				$this->success('提交申请失败， 微币数量不足！'.$storeInfo['income'].'..'.$data['extract'], $refresh_url);
			}
		
		}else{
			//头像，昵称，店铺id，会员等级
			$this->storeInfo = $this->getStoreInfo();
			//会员等级
			$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
			$this->assign('vipMap',$vipMap);
			//个人信息,姓名，卡号，支付宝账号
			$where = array('wecha_id' => $this->storeInfo['wecha_id'], 'token' =>$this->token);
			$userInfo = M('userinfo')->where($where)->find();
			$this->assign('userInfo', $userInfo);
			//多多币兑换成现金数量
			$cash = $this->storeInfo['income'] / $setInfo['cash'];
			$this->assign('cash', $cash);
			$this->assign('ratio', $setInfo['cash']);
			$this->assign('metaTitle', '申请提现');
			$this->display();
		}	
	}
	
	public function entry(){

		//是店主才有分销订单
		$this->getStoreInfo();
		$this->assign('info', M('AssistPublic')->find($this->token));
		$this->display();
	}
	/*
	*微币明细
	*by leo
	*/
	public function wbDetail(){
		/* Log::write("个人结算平台openid:".$this->wecha_id);
		if($this->wecha_id!='oFD4ys_Kk-3LIsrtJSXh-qDQBIPA'){
			$this->error('功能还没开放！',U('Distribution/index',array('token'=>$this->token)));
		} */
		//个人信息
		$this->getStoreInfo();
		//=======
		$where['storeid']=$this->storeID;
		if($_GET['statdate']&&$_GET['enddate']){
			$statdate=$_GET['statdate'];
			$enddate=$_GET['enddate'];
			$stat=strtotime($statdate);
			$end=strtotime($enddate);
			$where['recordtime'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间	
		}
		$where['status']=array('neq',2);
		$count      = M('rebate_record')->where($where)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$moneyDetail=M('rebate_record')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$this->assign('page',$show);
		$this->assign('moneyDetail',$moneyDetail);
		$wbMap = M('Dictionary')->where("type='weibi'")->getField("keyvalue,keyname");	
		$this->assign('wbMap',$wbMap);
		$this->display();
	}
	//二维码
	public function tcode() {
		/* if($this->wecha_id!='oFD4ys_Kk-3LIsrtJSXh-qDQBIPA'){
			$this->error('功能还没开放！',U('Distribution/index',array('token'=>$this->token)));
		} */
		//个人信息
		$this->getStoreInfo();
		$url=C(site_url).'/index.php?g=Wap&m=Product&a=index&token='.$this->token.'&store_id='.$this->storeID;
		$url=urlencode($url);
		echo '<img src="'.U('Distribution/qrcode', array('token'=>$this->token,'str'=>$url)).'" width="100%"/><br/>';
	}
	
	public function qrcode(){
		if(empty($_GET['str'])){
			echo '参数错误';exit;
		}
		//include "app/_CoreExtend/Vendor/phpqrcode/phpqrcode.php";
		Vendor("phpqrcode.phpqrcode");
		//Vendor("PHPExcel.PHPExcel"); // 引入phpexcel类(注意你自己的路径)
		//定义纠错级别
		$errorLevel = "L";
		//定义生成图片宽度和高度;默认为3
		$size = "4";
		//定义生成内容
		QRcode::png(htmlspecialchars_decode($_GET['str']), false, $errorLevel, $size);
	}
	//带logo的二维码
	//引用地址http://www.xcsoft.cn/public/qrcode
	//text:需要生成二维码的数据，默认:http://www.xcsoft.cn
	//size:图片每个黑点的像素,默认4
	//level:纠错等级,默认L
	//padding:图片外围空白大小，默认2
	//logo:全地址，默认为空
	//完整引用地址:http://www.xcsoft.cn/public/qrcode?text=http://www.xcsoft.cn&size=4&level=L&padding=2&logo=http://www.xcsoft.cn/Public//images/success.png
	public function qrcode2($text='http://www.xcsoft.cn',$size='4',$level='L',$padding=2,$logo=true){
		$text=$this->_get('text')?$this->_get('text'):$text;
		$size=$this->_get('size')?$this->_get('size'):$size;
		$level=$this->_get('level')?$this->_get('level'):$level;
		$logo=$this->_get('logo')?$this->_get('logo'):$logo;
		$padding=$this->_get('padding')?$this->_get('padding'):$padding;
		$path='./uploads/qrcode/';
		$QR=$path.'qrcode.png';
		vendor("phpqrcode.phpqrcode");
			QRcode::png($text,$QR, $level, $size,$padding);
		if($logo !== false){
			$QR = imagecreatefromstring(file_get_contents($QR));
			$logo = imagecreatefromstring(file_get_contents($logo));
			$QR_width = imagesx($QR);
			$QR_height = imagesy($QR);
			$logo_width = imagesx($logo);
			$logo_height = imagesy($logo);
			$logo_qr_width = $QR_width / 5;
			$scale = $logo_width / $logo_qr_width;
			$logo_qr_height = $logo_height / $scale;
			$from_width = ($QR_width - $logo_qr_width) / 2;
			imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
		}
		header("Content-Type:image/jpg");
		imagepng($QR);
	}
	public function mycode(){
		$text='http://www.itcast.cn';  //扫描跳转的链接
		$this->qrcode3($text);
	}
	
	public function qrcode3($text,$size='4',$level='L',$padding=2,$logo=false){
		vendor("phpqrcode.phpqrcode");
		
		QRcode::png($text,$QR, $level, $size,$padding);	
		header("Content-Type:image/jpg");
		imagepng($QR);
		
		/*
		$path='./uploads/qrcode/';
		$QR=$path.'qrcode.png';
		echo $QR;
		echo '<img src="'.$QR.'" width="20%"/><br/>';
		*/
		//QRcode::png(htmlspecialchars_decode($_GET['str']), false, $errorLevel, $size);
		
	}
	//微店铺
	private function getStoreInfo(){
		if(empty($this->storeID)){
			$this->error('参数不合法', U('Wap/Distribution/index', array('token'=>$this->token)));
		}
		$this->store_model = M('Store');  //微店铺表
		$map['token']	= $this->token;
		$map['id'] 		= $this->storeID;
		$map['status']	= 1; 		//已经通过审核，代表拥有自己的店铺
		$storeInfo = $this->store_model->where($map)->find();
		if(empty($storeInfo)){		//不是微店主
			$this->redirect('bind', array('token'=>$this->token));
		}
		//个人信息
		$this->assign('peppleInfo',$storeInfo);
		return $storeInfo;
	}
	
	//查看客户
	private function customer(){
		//是店主才有客户
		$this->getStoreInfo();
		//========
		$id = $this->storeID;
		
		$map['fid|gid|ggid']=$id;
		$map['token']=$this->token;
		$customers=M('store')->field("id,fid,gid,ggid")->where($map)->select();
		if(empty($customers)){
			$this->error('您还没有客户');exit;
		}
		$level1=0;$level2=0;$level3=0;$customersId = array();
		foreach($customers as &$cust){
			if($cust['fid'] == $id) {$level1 ++;}
			if($cust['gid'] == $id) {$level2 ++;}
			if($cust['ggid'] == $id){$level3 ++;}
			$customersId[] = $cust["id"];
		}
		//p($customers);
		//exit;
		$map = array("token"=>$this->token);
		switch($_GET["level"]){
			case 1:
				$map["fid"]=$id;break;
			case 2:
				$map["gid"]=$id;break;
			case 3:
				$map["ggid"]=$id;break;
			default:
				$map["id"] = array("in", $customersId); //如果$customersId为空时候，会报错。所以上面需要处理
		}
		$count      = M("store")->where($map)->field("id,nickname,wecha_id,fid,gid,ggid,createtime,level")->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$customerList = M("store")->where($map)->field("id,nickname,wecha_id,fid,gid,ggid,createtime,level,headimgurl")->select();
		foreach($customerList as &$v){
			if($v['fid'] == $id){$v['level']=1;}
			if($v['gid'] == $id){$v['level']=2;}
			if($v['ggid'] == $id){$v['level']=3;}
		}
		$this->assign('count',$count);
		$this->assign('page',$show);
		$this->assign("level1", $level1);
		$this->assign("level2", $level2);
		$this->assign("level3", $level3);
		$this->assign("customers", $customers);
		$this->assign("customerList", $customerList);
		//$this->display();
	}
	//微信回复调用
	private function api_notice_increment($url, $data){
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