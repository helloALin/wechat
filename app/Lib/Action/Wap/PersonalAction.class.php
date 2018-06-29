<?php
class PersonalAction extends Oauth2Action{
	public $token;
	private $storeInfo;
	private $storeID;
	
	public function _initialize(){	
		parent::_initialize();
		//$this->wecha_id='oTRiCtzmS1oLd4CXpXD8L48M9O1Y';   //我的号
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
		$this->token		= $this->_get('token'); //'qikwvz1409638161';//
		//没有Token
		if(empty($this->token)){exit;}
		$this->assign('token',$this->token);		
		$this->assign('staticFilePath',str_replace('./','/',THEME_PATH.'common/css/product'));
		//购物车
		$calCartInfo=$this->calCartInfo();
		$this->assign('totalProductCount',$calCartInfo[0]);
		$this->assign('totalProductFee',$calCartInfo[1]);
	}
	
	//推广工具列表页
	public function promotionTool(){
		$this->getStoreInfo();
		if(IS_AJAX){
			$where = array('token'=>$this->token, 'store_id'=>$this->storeID);
			$promotes = M('StorePromotion')->where($where)->select();
			foreach ($promotes as &$promote){
				$promote['create_time'] = date('Y-m-d H:i:s', $promote['create_time']);
			}
			echo json_encode($promotes);
		}else{
			$this->display();
		}
	}
	
	//删除推送链接
	public function delPromotion(){
		$id = $this->_get('id','trim,intval');
		$this->getStoreInfo();
		if($id){
			$where = array('token'=>$this->token, 'store_id'=>$this->storeID, 'id'=>$id);
			$result = M('StorePromotion')->where($where)->delete();
			if($result === false){
				$this->error('删除失败！请重试！');
			}elseif($result == 1){
				$this->success('删除成功！');
			}else{
				$this->error('删除失败，删除记录不存在！');
			}
		}else{
			$this->error('删除失败！');
		}
	}
	
	//添加、保存推广链接
	public function addPromotion(){
		if(!IS_POST){
			$this->display(); 
			return;
		}
		//保存推广信息
		$this->getStoreInfo();
		$title = $this->_post('title', 'trim');
		$url = $this->_post('url', 'trim');
		if(!empty($url)){
			if(!preg_match('/^http[s]?\\:\\/\\//i', $url)){
				$url = 'http://'.$url;
			}
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			//curl_setopt($ch, CURLOPT_HTTPHEADER, 'Accept-Charset: utf-8');
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);   //设置超时时间 5秒
			$content = curl_exec($ch);
			if(curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200'){
				$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
				$encode = substr($content_type, strrpos($content_type, '=') + 1);
				if(strcasecmp('utf-8', $encode) == 0){
					if(empty($title) && preg_match('/<title>(.*?)<\\/title>/is', $content, $matcher)){
						$title = $matcher[1];
					}
					if(empty($title)){
						$this->assign('warnMsg', '获取页面标题失败，请手动填写页面标题');
					}else{
						$data = array('title'=>$title, 'url'=>$url, 'count'=>0, 'token'=>$this->token, 
									'store_id'=>$this->storeID, 'create_time'=>time());
						$model = M('StorePromotion');
						if($model->add($data)){
							$this->redirect('promotionTool', $_GET);
						}else{
							Log::write('保存推广链接失败，'.$model->getLastSql());
						}
					}
				}else 
					$this->assign('warnMsg', '文章链接不支持推广！');
			}else{
				Log::write('文章链接访问失败，响应码：'.curl_getinfo($ch, CURLINFO_HTTP_CODE).' 错误信息：'.curl_error($ch));
				$this->assign('warnMsg', '文章链接访问失败，请检查链接地址是否正确！');
			}
			curl_close($ch);
		}else
			$this->assign('warnMsg', '请输入您要推广的文章链接！');
		
		$this->display();
	}
	
	//显示推广分享页面
	public function showPromotion(){
		$id = $this->_get('id','trim,intval');
		if($id){
			$where = array('token'=>$this->token, 'id'=>$id);
			$promotion = M('StorePromotion')->where($where)->find();
			if(!empty($promotion)){
				//增加访问数
				M('StorePromotion')->where($where)->setInc('count');
				$this->assign('info', $promotion);
				$where['id'] = $promotion['store_id'];
				$this->assign('user_logo', M('Store')->where($where)->getField('headimgurl'));
			}
		}
		$this->display();
	}
	
	//我的地址
	public function myAddress(){
		if(IS_AJAX){
			$this->getStoreInfo(false);
			if($this->storeID){
				$last = $this->_post('last', 'intval');
				$amount = $this->_post('amount', 'intval');
				$amount = $amount ? $amount : 10;
				$where = array('token'=>$this->token, 'openid'=>$this->wecha_id);
				$field = 'id,name,tel,province,city,county,address';
				$addresses = M('Address')->field($field)->where($where)->limit($last,$amount)->order('update_time desc')->select();
				foreach ($addresses as &$address){
					$address['create_time'] = date('Y-m-d H:i:s', $address['create_time']);
				}
			}
			echo json_encode($addresses);
		}else{
			$this->getStoreInfo();
			$last_page_url = 'javascript:history.go(-1);';
			if(strpos($_SERVER['HTTP_REFERER'], U('Wap/Personal/editAddress')) === false){
				$_SESSION['myaddress_last_page_url'] = $_SERVER['HTTP_REFERER'];
			}elseif($_SESSION['myaddress_last_page_url']){
				$last_page_url = $_SESSION['myaddress_last_page_url'];
			}
			$this->assign('last_page_url', $last_page_url);
			//根据是否含select参数判断是否从结算页面进入地址选择模式
			
			if(strpos($_SERVER['HTTP_REFERER'], U('Wap/Product/orderCart')) > -1
				|| strpos($last_page_url, U('Wap/Product/orderCart')) > -1){
				if(M('Address')->where(array('token'=>$this->token, 'openid'=>$this->wecha_id))->count(1)){
					$this->assign('select', 1);
				}else{	//提交订单时未添加地址时，直接跳转到添加地址页面
					$this->redirect('Wap/Personal/editAddress',array('token'=>$this->token, 'select'=>1, 'store_id'=>$_GET['store_id']));
				}
			}
			$this->assign('store_id', $_GET['store_id']);
			$this->display();
		}
	}
	
	public function editAddress(){
		$model = D('Address');
		$where = array('token'=>$this->token, 'openid'=>$this->wecha_id);
		if(IS_GET){
			$id = $this->_get('id', 'intval');
			if($id){
				$where['id'] = $id;
				$info = $model->where($where)->find();
				if($info)
					$this->assign('info', $info);
			}
			$this->display();
			return;
		}
		//提交地址信息进行保存操作
		$result = false;
		if($_POST && $model->create()){
			$model->openid = $this->wecha_id;
			$model->token=$this->token;
			if($model->id){
				$where['id'] = $model->id;
				$result = $model->where($where)->save();
			}else{
				$result = $model->add();
			}
		}
		if($result === false){
			$this->error($model->getError() ? $model->getError() : '操作失败！');
		}else{
			$param = array('token'=>$this->token, 'store_id' => $_GET['store_id']);
			
			if($_GET['select']){ //提交订单时未添加地址时的跳转
				$param['addrId'] = $result;
				$this->success('保存成功 ！', U('Wap/Product/orderCart', $param));
			}else{
				$this->success('保存成功 ！', U('Wap/Personal/myAddress', $param));
			}
		}
			
	}
	
	//删除推送链接
	public function delAddress(){
		$id = $this->_get('id','trim,intval');
		if($id){
			$where = array('token'=>$this->token, 'openid'=>$this->wecha_id, 'id'=>$id);
			$result = M('Address')->where($where)->delete();
			if($result === false){
				$this->error('删除失败！请重试！');
			}elseif($result == 1){
				$this->success('删除成功！');
			}else{
				$this->error('删除失败，删除记录不存在！');
			}
		}else{
			$this->error('删除失败！');
		}
	}
	//提升会员等级申请
	public function upvip(){
		$this->getStoreInfo();
		$this->assign('peppleInfo',$this->storeInfo);
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField('keyvalue,keyname');
		$this->assign('vipMap',$vipMap);
		$userInfo=M('userinfo')->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
		$this->assign('metaTitle','会员升级申请');
		//==
		$upvipInfo=M('upvip')->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'status'=>3))->find();
		if($upvipInfo){
			//已经申请会员等级提升,审核中
			$status=1;
			$this->assign('upvipInfo', $upvipInfo);
		}else{
			//未申请会员等级提升
			$status=0;
			$upvipAllInfo=M('upvip')->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->order('createtime desc')->find();
			$this->assign('upvipAllInfo',$upvipAllInfo);
		}
		$this->assign('status',$status);
		//个人订单信息
		$product_cart_model=M('product_cart');
		//$this->wecha_id
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		//$map['ostatus']=4;//交易完成
		$map['ostatus']  = array('in','2,3,4');//用户体验说支付成功之后的订单就可当申请凭据
		$orders=$product_cart_model->where($map)->order('time DESC')->field('orderid,price')->select();
		$this->assign('orders',$orders);
		//
		if (IS_POST){
			if ($keep === 1) {
				echo '你还不是微店主，请先申请成为微店主！';	
				return;
			}
			if($status == 1){
				echo '申请审核中...';	return;
			}
			if($this->_post('upvip')<= $this->storeInfo['vip']){
				echo '申请等级不能小于原来等级！';	return;
			}
			$upvipDb=M('upvip');
			$arr = explode(',',$this->_post('orderid'));
			
			$data['token'] =  $this->token;
			$data['store_id'] =  $this->storeInfo['id'];
			$data['truename'] =  $userInfo['truename'];
			$data['tel'] =  $userInfo['tel'];
			$data['wecha_id'] =  $this->wecha_id;
			$data['upvip'] =  $this->_post('upvip');  //申请提升的等级
			$data['orderid'] =  $arr[0];
			$data['price'] =  $arr[1];
			$data['createtime'] = time();
			$data['status'] = 3;   //状态 0未申请 1申请成功 2申请失败 3申请审核中
			//p($data);
			//exit;
			$result=$upvipDb->add($data);
			if($result){				
				echo'提交申请成功！';
			}else{
				echo'提交申请失败！';
			}
		
		}else{
			$this->display();
		}
	}
	//购物车显示 购物数量
	public function calCartInfo($carts=''){
		$totalFee=0;
		$totalCount=0;
		if (!$carts){
			$carts=$this->_getCart();
		}
		if ($carts){
			foreach ($carts as $c){
				if ($c){
					$totalFee+=floatval($c['price'])*$c['count'];
					$totalCount+=intval($c['count']);
				}
			}
		}
		return array($totalCount,$totalFee);
	}
	function _getCart(){
		if (!isset($_SESSION['session_cart_products'])||!strlen($_SESSION['session_cart_products'])){
			$carts=array();
		}else {
			$carts=unserialize($_SESSION['session_cart_products']);
		}
		return $carts;
	}
	/**
	 * 获取用户微店铺信息，获取失败时默认跳转到商品列表页
	 */
	private function getStoreInfo($isRedirect = true){
		//微店铺
		$this->store_model=M('Store');  //微店铺表
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		//$map['status']=1; //已经通过审核，代表拥有自己的店铺
		$this->storeInfo=$this->store_model->where($map)->find();
		//未等级的用户跳转到商城首页
		if(empty($this->storeInfo) && $isRedirect){
			$this->redirect('Wap/Product/index');exit;
		}
		$this->storeID = $this->storeInfo['id'];
	}	
	//是否是微店主
    private function isStorekeeper()
    {
        $keeper            = 0;
        $storeDb = M('Store');
        $where               = array(
            'token' => $this->token,
            'wecha_id' => $this->wecha_id,
            'status' => 1
        );
		//微店主
        $storeInfo                = $storeDb->where($where)->order('sign_time desc')->find();
        if ($storeInfo) {
            $keeper = 1;
        }
        return $keeper;
    }
	
}

?>