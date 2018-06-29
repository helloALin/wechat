<?php
class ProductAction extends Oauth2Action{
	public $token;
	public $wecha_id;
	public $product_model;
	public $product_cat_model;
	public $store_model;
	public $isDining;
	private $storeInfo;
	private $storeID;
	public function _initialize(){
		parent::_initialize();
		//$this->wecha_id='oTRiCtzmS1oLd4CXpXD8L48M9O1Y';   //我的号
		//$this->wecha_id='oTRiCtxE-shZzn6eQblGq6hZS6L0';   //我的小号（下级）
		//$this->wecha_id='oTRiCt2TQuT2ZwBuz3IARETH0-fA';   //权章的号（下下级）
		//$this->wecha_id='oTRiCtzGU5Ls05xZTOMhmgNXz8kc';  //未申请微店
		//$this->wecha_id='oTRiCt0Iuem5Z9jRPEW7n6quUvos';  //敏强的号
		//======
		define('RES', THEME_PATH . 'common');
        define('STATICS', TMPL_PATH . 'static');
		$this->assign('wecha_id',$this->wecha_id);
		$agent = $_SERVER['HTTP_USER_AGENT']; 
		if(!strpos($agent,"MicroMessenger")) {
		//	echo '此功能只能在微信浏览器中使用';exit;
		}
		$this->token		= $this->_get('token');
		//没有Token
		if(empty($this->token)){exit;}
		$this->assign('token',$this->token);		
		$this->product_model=M('Product');  //商品表
		$this->product_cat_model=M('Product_cat');
		$this->assign('staticFilePath',str_replace('./','/',THEME_PATH.'common/css/product'));
		//购物车
		$calCartInfo=$this->calCartInfo();
		$this->assign('totalProductCount',$calCartInfo[0]);
		$this->assign('totalProductFee',$calCartInfo[1]);
		//是否是餐饮
		if (isset($_GET['dining'])&&intval($_GET['dining'])){
			$this->isDining=1;
			$this->assign('isDining',1);
		}		
		//微店铺
		$this->store_model=M('Store');  //微店铺表
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		//$map['status']=1; //已经通过审核，代表拥有自己的店铺
		$storeInfo=$this->store_model->where($map)->find();
		$this->storeInfo=$storeInfo;
		//下面的if无论如何都会走进去的
		if(empty($storeInfo['id']) || $storeInfo['status'] != 1 || $_GET["store_id"] == $storeInfo['id']){
			$this->store_id=0;
			if (isset($_GET['store_id'])&&intval($_GET['store_id']) == $storeInfo['id'] && $storeInfo['status'] == 1){
				$this->store_id=$_GET['store_id'];
			}elseif(!empty($storeInfo["fid"])){
				if(intval($_GET['store_id']) == $storeInfo["fid"]){
					$this->store_id = $_GET["store_id"];
				}else{
					$_GET["store_id"]=$storeInfo["fid"];
					$this->redirect("Wap/Product/".ACTION_NAME, $_GET);exit;
				}
			}elseif(empty($storeInfo["fid"]) && isset($_GET['store_id']) && intval($_GET['store_id'])){
				$parentStore = $this->store_model->where(array("id"=>$_GET['store_id'],"status"=>'1'))->find();
				if($parentStore){
					$this->store_id = $_GET["store_id"];
				}
			}
			//没有自己的店铺
			//建立自己的店铺			
			if(empty($storeInfo['id'])){
				//处理所属父亲fid，爷爷gid，祖父ggid 分类
				/*if($parentStore){
					$data["fid"]=$parentStore["id"];
					$data["gid"]=$parentStore["fid"];
					$data["ggid"]=$parentStore["gid"];
				}*/
				$data['token'] = $this->token;
				$data['wecha_id'] = $this->wecha_id;
				$data['status'] = 0; //未申请（审核）
				$data['vip'] = 0; 
				$data['createtime'] = time(); 
				$data['id']=$this->store_model->add($data);
				$this->storeInfo = $data;
			}
		}
		else{
			//有自己的店铺，进入自己的店铺（如果参数store_id的值不是自己的店铺id,则重定向到自己的id）
			$this->store_id=$storeInfo['id'];
			$_GET["store_id"]=$this->store_id;
			$this->redirect("Wap/Product/".ACTION_NAME, $_GET);exit;
		}
		$this->assign('store_id',$this->store_id);
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
		$imgUrl=C('site_url')."/tpl/static/share/images/logo.jpg";
		$title="对女人多一份关爱";
		$desc="品味女人的最爱，男人也可以用的卫生巾。帮助女性朋友们找到最适合自己的，最独特的，最高贵的，最典雅的贴身卫生用品；帮助男人为心爱的女人提供最贴心的高端礼品。";
		$this->assign('imgUrl',$imgUrl);
		$this->assign('title',$title);
		$this->assign('desc',$desc);
	}
	function remove_html_tag($str){  //清除HTML代码、空格、回车换行符
		//trim 去掉字串两端的空格
		//strip_tags 删除HTML元素

		$str = trim($str);
		$str = @preg_replace('/<script[^>]*?>(.*?)<\/script>/si', '', $str);
		$str = @preg_replace('/<style[^>]*?>(.*?)<\/style>/si', '', $str);
		$str = @strip_tags($str,"");
		$str = @ereg_replace("\t","",$str);
		$str = @ereg_replace("\r\n","",$str);
		$str = @ereg_replace("\r","",$str);
		$str = @ereg_replace("\n","",$str);
		$str = @ereg_replace(" ","",$str);
		$str = @ereg_replace("&nbsp;","",$str);
		return trim($str);
	}
	//products() 改成 index（）
	public function index(){
		//微店铺
		/*
		if(isset($_GET['store_id'])){
			$store=M('Store');  //微店铺表
			$map['token']=$this->token;
			$map['wecha_id']=$this->wecha_id;
			$map['id']=$_GET['store_id'];
			$map['status']=1; //已经通过审核，拥有店铺
			$storeAudit=$this->store_model->where($map)->find();
			$this->assign('storeAudit',$storeAudit);
		}
		*/
		//===========
		//商城公告
		$db = D('notice');
		$noticeInfo=$db->where(array('token'=>$this->token,'status'=>1))->find();
		$this->assign('noticeInfo',$noticeInfo);
		//商品幻灯片广告
		$where['token']=$this->token;
		$where['classid']=1;
		$flash=M('Flash')->where($where)->select();
		$this->assign('flash',$flash);
		//===========
		$where=array('token'=>$this->token,'status'=>1);
		//获得分类id
		if (isset($_GET['catid'])){
			$catid=intval($_GET['catid']);
			$where['catid']=$catid;
			$thisCat=$this->product_cat_model->where(array('id'=>$catid))->find();
			$this->assign('thisCat',$thisCat);
		}
		//搜索功能
		if (IS_POST){
			$_GET['keyword'] = $this->_post('search_name');
			if($_GET['catid']){
				$_GET['catid'] = $this->_post('catid');
			}
			$this->redirect("index", $_GET);
			//$this->redirect('/index.php?g=Wap&m=Product&a=index&token='.$this->token.'&keyword='.$key);
		}
		
		if (isset($_GET['keyword'])){
            //$where['name|intro|keyword'] = array('like',"%".$_GET['keyword']."%");
			$where['name'] = array('like',"%".$_GET['keyword']."%");
            $this->assign('isSearch',1);
		}
		$count = $this->product_model->where($where)->count();
		$this->assign('count',$count); 
		//p($products);
		//排序方式
		$method=isset($_GET['method'])&&($_GET['method']=='DESC'||$_GET['method']=='ASC')?$_GET['method']:'DESC';
		$orders=array('time','discount','price','salecount');
		$order=isset($_GET['order'])&&in_array($_GET['order'],$orders)?$_GET['order']:'time';
		$this->assign('order',$order);
		$this->assign('method',$method);
		//============	
		$products = $this->product_model->where($where)->order($order.' '.$method)->limit('4')->select();
		//echo $this->product_model->getLastSql();
		
		$this->assign('products',$products);
		$this->assign('metaTitle','微商城');
		$this->display();
	}
	public function product(){
		//判断是否已收藏该商品
		$CollectModel=M('Collect');
		$map['token']=$_GET['token'];
		$map['wecha_id']=$this->wecha_id ;
		$map['pid']=$_GET['id'];
		$collectPid=$CollectModel->distinct(true)->where($map)->getField('pid',true);
		if(empty($collectPid)){
			//未收藏
			$s=0;
		}
		else{ 
			//未收藏
			$s=1;
		}
		$this->assign('s',$s);
		//
		$where=array('token'=>$this->token);//上架
		
		if (isset($_GET['id'])){
			$id=intval($_GET['id']);
			$where['id']=$id;
		}
		
		$product=$this->product_model->where($where)->find();
		$this->assign('product',$product);
		if ($product['endtime']){
			$leftSeconds=intval($product['endtime']-time());
			$this->assign('leftSeconds',$leftSeconds);
		}
		$this->assign('metaTitle',$product['name']);
		/* 
		$product['intro']=str_replace(array('&lt;','&gt;','&quot;','&amp;nbsp;'),array('<','>','"',' '),$product['intro']);
		$intro=$this->remove_html_tag($product['intro']);
		$intro=mb_substr($intro,0,30,'utf-8');
		 */
		$product['intro']=str_replace(array('&lt;','&gt;','&quot;','&amp;nbsp;'),array('<','>','"',' '),$product['intro']); 
		$intro=$product['intro'];
		$this->assign('intro',$intro);
		//分店信息
		$company_model=M('Company');
		$branchStoreCount=$company_model->where(array('token'=>$this->token,'isbranch'=>1))->count();
		$this->assign('branchStoreCount',$branchStoreCount);
		//销量最高的商品
		$sameCompanyProductWhere=array('token'=>$this->token,'id'=>array('neq',$product['id']));
		if ($product['dining']){
			$sameCompanyProductWhere['dining']=1;
		}
		if ($product['groupon']){
			$sameCompanyProductWhere['groupon']=1;
		}
		if (!$product['groupon']&&!$product['dining']){
			$sameCompanyProductWhere['groupon']=array('neq',1);
			$sameCompanyProductWhere['dining']=array('neq',1);
		}
		if (isset($_GET['catid'])){
			$sameCompanyProductWhere['catid']=$_GET['catid'];
		}
		$products=$this->product_model->where($sameCompanyProductWhere)->limit('salecount DESC')->limit('0,5')->select();
		$this->assign('products',$products);
		$this->display();
	}
	public function cats(){
		$catWhere=array('parentid'=>0,'token'=>$this->token);
		if (isset($_GET['parentid'])){
			$parentid=intval($_GET['parentid']);
			$catWhere['parentid']=$parentid;
			
			$thisCat=$this->product_cat_model->where(array('id'=>$parentid))->find();
			$this->assign('thisCat',$thisCat);
			$this->assign('parentid',$parentid);
		}
		if ($this->isDining){
			$catWhere['dining']=1;
		}else {
			$catWhere['dining']=0;
		}
		$cats = $this->product_cat_model->where($catWhere)->order('id asc')->select();
		$this->assign('cats',$cats);
		$this->assign('metaTitle','商品分类');
		$this->display();
	}
	
	public function ajaxProducts(){
		$where=array('token'=>$this->token,'status'=>1);
		if (isset($_GET['catid'])){
			$catid=intval($_GET['catid']);
			$where['catid']=$catid;
		}
		if (isset($_GET['keyword'])){
            //$where['name|intro|keyword'] = array('like',"%".$_GET['keyword']."%");
			$where['name'] = array('like',"%".$_GET['keyword']."%");
           // $this->assign('isSearch',1);
		}
		$page=isset($_GET['page'])&&intval($_GET['page'])>1?intval($_GET['page']):2;
		$pageSize=isset($_GET['pagesize'])&&intval($_GET['pagesize'])>1?intval($_GET['pagesize']):5;
		$start=($page-1)*$pageSize;
		$products = $this->product_model->where($where)->order('id desc')->limit($start.','.$pageSize)->select();
		$str='{"products":[';
		if ($products){
			$comma='';
			foreach ($products as $p){
				$str.=$comma.'{"id":"'.$p['id'].'","catid":"'.$p['catid'].'","storeid":"'.$p['storeid'].'","name":"'.$p['name'].'","price":"'.$p['price'].'","token":"'.$p['token'].'","keyword":"'.$p['keyword'].'","salecount":"'.$p['salecount'].'","logourl":"'.$p['logourl'].'","time":"'.$p['time'].'","oprice":"'.$p['oprice'].'"}';
				$comma=',';
			}
		}
		$str.=']}';
		$this->show($str);
	}
	
	public function header(){
		$this->display();
	}
	
	public function productDetail(){
		$where=array('token'=>$this->token);
		if (isset($_GET['id'])){
			$id=intval($_GET['id']);
			$where['id']=$id;
		}
		$product=$this->product_model->where($where)->find();
		$product['intro']=str_replace(array('&lt;','&gt;','&quot;','&amp;nbsp;'),array('<','>','"',' '),$product['intro']);
		$this->assign('product',$product);
		$this->assign('metaTitle',$product['name']);
		
		$this->display();
	}
	public function company($display=1){
		//店铺信息
		$company_model=M('Company');
		$where=array('token'=>$this->token);
		if (isset($_GET['companyid'])){
			$where['id']=intval($_GET['companyid']);
		}
		
		$thisCompany=$company_model->where($where)->find();
		$this->assign('thisCompany',$thisCompany);
		//分店信息
		$branchStores=$company_model->where(array('token'=>$this->token,'isbranch'=>1))->order('taxis ASC')->select();
		$branchStoreCount=count($branchStores);
		$this->assign('branchStoreCount',$branchStoreCount);
		$this->assign('branchStores',$branchStores);
		$this->assign('metaTitle','公司信息');
		if($display){
		$this->display();
		}
	}
	public function companyMap(){
		$this->apikey=C('baidu_map_api');
		$this->assign('apikey',$this->apikey);
		$this->company(0);
		$this->display();
	}
	public function addProductToCart(){//商品id|商品价格|商品数量,
		
		$count=isset($_GET['count'])?intval($_GET['count']):1;
		//echo '购买的数量是：'.$count;
		//exit;
		$carts=$this->_getCart();
		$id=intval($_GET['id']); //商品id
		if (key_exists($id,$carts)){
		
			//$carts[$id]['count']++;
			$carts[$id]['count']=$carts[$id]['count']+$count;
		}else {
			$carts[$id]=array('count'=>$count,'price'=>floatval($_GET['price']));
		}
		$_SESSION['session_cart_products']=serialize($carts);
		
		$calCartInfo=$this->calCartInfo();
		echo $calCartInfo[0].'|'.$calCartInfo[1];
	}
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
	//购物车
	public function cart(){
		//获取到最新添加到购物车的商品
		$where=array('token'=>$this->token);
		if (isset($_GET['id'])){
			$id=intval($_GET['id']);
			$where['id']=$id;
			$product=$this->product_model->where($where)->find();
			//S('wxuser_' . id, $product);
		}
		
		//p($product);
		//$this->assign('product',$product);
		//=======
		$totalFee=0;
		$totalCount=0;
		$products=array();
		$ids=array();
		$carts=$this->_getCart();
		foreach ($carts as $k=>$c){
			if (is_array($c)){
				$productid=$k;
				$price=$c['price'];
				$count=$c['count'];
				//
				if (!in_array($productid,$ids)){
					array_push($ids,$productid);
				}
				$totalFee+=$price*$count;
				$totalCount+=$count;
			}
		}
		if (count($ids)){
			$list=$this->product_model->where(array('id'=>array('in',$ids)))->select();
		}
		//判断是不是餐饮
		$isDining=0;
		if ($list){
			$i=0;
			foreach ($list as $p){
				$list[$i]['count']=$carts[$p['id']]['count'];
				if ($p['dining']){
					$isDining=1;
				}
				$i++;
			}
		}
		$this->assign('cartIsDining',$isDining);
		$this->assign('products',$list);
		//
		$this->assign('totalFee',$totalFee);
		$this->assign('metaTitle','购物车');
		$this->display();
	}
	public function deleteCart(){
		$products=array();
		$ids=array();
		$carts=$this->_getCart();
		foreach ($carts as $k=>$c){
			$i=0;
			if (strlen($c)){
				$productid=$k;
				$price=$c['price'];
				$count=$c['count'];
				//
				if ($_GET['id']!=$productid){
					$products[$productid]=array('price'=>$price,'count'=>$count);
				}
				$i++;
			}
		}
		$_SESSION['session_cart_products']=serialize($products);
		$this->redirect(U('Product/orderCart',array('token'=>$_GET['token'],'store_id'=>$this->store_id)));
	}
	public function ajaxUpdateCart(){
		$carts=$this->_getCart();
		$g_id=intval($_GET['id']);
		$g_count=intval($_GET['count']);
		if ($carts){
			foreach ($carts as $k=>$c){
				if ($g_id==$k){
					$carts[$k]['count']=$g_count;
				}
			}
		}
		$_SESSION['session_cart_products']=serialize($carts);
		$calCartInfo=$this->calCartInfo();
		echo $calCartInfo[0].'|'.$calCartInfo[1];
	}
	//去结算
	public function test2(){
		$row=array();
		$carts=$this->_getCart();//购物车里面：商品的id,价格，数量
		//p($carts);	
		$allCartInfo=$this->calCartInfo($carts); //购物车里面：总价，总数
		p($allCartInfo);
	}
	public function orderCart(){
		/*
		if (isset($_GET['cartIsDining'])&&intval($_GET['cartIsDining'])){
			$cartIsDining=1;
			$this->assign('cartIsDining',1);
		}
		*/
		if(empty($this->wecha_id)){
			echo '服务器繁忙，请稍后再试!';
			exit;
		}
		//物流信息
		$logInfo=M('Logistics')->where(array('token'=>$this->token))->select();
		$this->assign('logInfo',$logInfo);
		//个人信息
		$userinfo_model=M('Userinfo');
		$user_where = array('token'=>$this->token,'wecha_id'=>$this->wecha_id);
		$thisUser=$userinfo_model->where($user_where)->find();
		$addrId = $this->_get('addrId', 'intval');
		if(IS_GET && !empty($addrId)){
			$user_where['id'] = $addrId;
			$address = M("Address")->field('name,tel,province,city,county,address')->where($user_where)->find();
			if($address){
				$thisUser['truename'] = $address['name'];
				$thisUser['tel'] = $address['tel'];
				$thisUser['address'] = $address['province'].$address['city'].$address['county'].$address['address'];
			}
		}
		$this->assign('thisUser',$thisUser);
		
		//是否要支付
		$alipay_config_db=M('Alipay_config');
		$alipayConfig=$alipay_config_db->where(array('token'=>$this->token))->find();
		$this->assign('alipayConfig',$alipayConfig);
		//
		//查询我的微币数量
		$wbInfo=M('store')->where(array('id'=>$this->store_id))->getField('income');
		$this->assign('wbInfo',$wbInfo);
		//微币可以兑换成X元
		$setInfo=M('distribution_set')->where(array('token'=>$this->token))->find();
		$cash=$this->storeInfo['income']/$setInfo['cash'];
		$this->assign('cash',$cash);
		//-----------------------------------------------------
		//获取到最新添加到购物车的商品
		$where=array('token'=>$this->token);
		if (isset($_GET['id'])){
			$id=intval($_GET['id']);
			$where['id']=$id;
			$product=$this->product_model->where($where)->find();
			//S('wxuser_' . id, $product);
		}
		$totalFee=0;
		$totalCount=0;
		$ids=array();
		$carts=$this->_getCart();
		foreach ($carts as $k=>$c){
			if (is_array($c)){
				$productid=$k;
				$price=$c['price'];
				$count=$c['count'];
				//
				if (!in_array($productid,$ids)){
					array_push($ids,$productid);
				}
				$totalFee+=$price*$count;
				$totalCount+=$count;
			}
		}
		if (count($ids)){
			$list=$this->product_model->where(array('id'=>array('in',$ids)))->select();
		}
		//判断是不是餐饮
		$isDining=0;
		if ($list){
			$i=0;
			foreach ($list as $p){
				$list[$i]['count']=$carts[$p['id']]['count'];
				if ($p['dining']){
					$isDining=1;
				}
				$i++;
			}
		}
		$this->assign('cartIsDining',$isDining);
		$this->assign('products',$list);
		//
		$this->assign('totalFee',$totalFee);
		$this->assign('metaTitle','购物车');
		
		
		if (IS_POST){ 
			$row=array();
			$carts=$this->_getCart();//购物车里面：商品的id,价格，数量
			if(empty($carts)){
				$this->error("购物车没有宝贝了！请到商城选购！",U('Product/index',array('token'=>$this->token,'store_id'=>$this->store_id)));
			}
			//
			$allCartInfo=$this->calCartInfo($carts); //购物车里面：总数，总价
			$totalFee=$allCartInfo[1];  //总价
			//
			$cartsCount=0;
			//
			$isGroupon=0;  //是否是团购
			//把团购、普通购物和餐饮分开
			$normalCart=array(); //普通购物
			$grouponCart=array(); //团购
			$diningCart=array();   //餐饮
			$productsByKey=array(); 
			//
			$orderName='';
			if (count($ids)){
				//查询出购买的商品的详细信息
				$products=$this->product_model->where(array('id'=>array('in',$ids)))->select();
				if ($products){
					$t=0;
					foreach ($products as $p){
						$productsByKey[$p['id']]=$p;
						if ($t==0){
							$orderName=$p['name'];
						}
						$t++;
					}
				}
				foreach ($carts as $k=>$c){
					$thisProduct=$productsByKey[$k];  //由此可知，$productsByKey 跟$carts 其实是同一个数组
					if($thisProduct){
						//团购，订餐，购物
						if ($thisProduct['groupon']==1){
							$grouponCart[$k]=$c;
							$carts[$k]['type']='groupon';
						}else {
							if ($thisProduct['dining']==1){
								$diningCart[$k]=$c;
								$carts[$k]['type']='dining';
							}else {
								$normalCart[$k]=$c; //存入商品ID
								$carts[$k]['type']='normal';
							}
						}
						$cartsCount++;
					}
				}
			}
			//p($carts);	
			//p($normalCart); //打印出来的结果跟原来的$carts一样

			//$orderid=time();
			//订单号的处理
			$randLength=6;
			$chars='abcdefghijklmnopqrstuvwxyz';
			$len=strlen($chars);
			$randStr='';
			for ($i=0;$i<$randLength;$i++){
				$randStr.=$chars[rand(0,$len-1)];
			}
			$orderid=$randStr.time();
			//======
			$row['orderid']=$orderid;
			$orderid=$row['orderid']; //订单号 后面有用到$orderid
			//
			$row['truename']=$this->_post('truename');
			$row['tel']=$this->_post('tel');
			$row['address']=$this->_post('address');
			$row['email']=$this->_post('email');
			$row['payment']=$this->_post('payment');//支付方式  1 货到付款  2 微信支付
			//状态处理 标识ostatus默认是1，未付款。如果选择的是货到付款，需要修改标识为2 待发货
			if($this->_post('payment')==1){
			$row['ostatus']=2;
			}
			//物流信息
			$arr = explode(',',$this->_post('logisticsInfo'));
			$row['logistics'] =  $arr[0];
			$row['logfee'] =  $arr[1];
			//=======================
			$row['token']=$this->token;
			$row['wecha_id']=$this->wecha_id;
			
			/*
			//订桌用到 $buytimestamp
			$buytimestamp=$this->_post('buytimestamp');//购买时间
			if ($buytimestamp){
				$row['year']=date('Y',$buytimestamp);
				$row['month']=date('m',$buytimestamp);
				$row['day']=date('d',$buytimestamp);
				$row['hour']=$this->_post('hour');
			}
			*/
			
			$time=time();
			$row['time']=$time;
			//分别加入3类订单
			$orderids=array();//用于存储插入的各类订单id
			$product_cart_model=M('product_cart');
			if ($cartsCount){
				
				//购物
				if (count($normalCart)){
					$calCartInfo=$this->calCartInfo($normalCart);
					$row['total']=$calCartInfo[0]; //总的数量
					$row['price']=$calCartInfo[1]; //总价
					//这个是店铺id
					//如果 $this->store_id 是0，存值0；如果是自己的id，存fid;如果是fid，存fid。
					//总结上面就是存$this->storeInfo['fid']
					//$row['store_id']=$this->store_id;
					$row['store_id'] = ($this->store_id == $this->storeInfo['id']) ? $this->storeInfo['fid'] : $this->store_id;
					//如果买家是有店铺的，写入ownstatus=1,否则默认0
					$map['token']=$this->token;
					$map['wecha_id']=$this->wecha_id;
					$map['status']=1; //已经通过审核，代表拥有自己的店铺
					$storeInfo=M('store')->where($map)->find();
					if(isset($storeInfo)){
						$row['ownstatus']=1;	
					}
					//=========================
					//抵扣  我的微币$storeInfo['income']  购物的消费金额是$calCartInfo[1]
					//如果在前面勾选了使用微币抵现，那么就执行下面的操作
					//支付成功之后，再扣除对应的微币（需要weibi大于0）
					if($storeInfo['income']>0 && $_POST['deduct'] == '1'){
						$setInfo=M('distribution_set')->where(array('token'=>$this->token))->find();
						$cash=$this->storeInfo['income']/$setInfo['cash'];	//微币转成金钱
						if(($calCartInfo[1]+$arr[1])>$cash){
							//微币不够抵扣，扣光微币
							$row['paymoney']=$calCartInfo[1]+$arr[1]-$cash; //总价+物流费用
							$row['deduct']=$cash;
							$row['weibi']=$cash*$setInfo['cash'];
							$row['otype']=2;
						}else{
							//微币＞=消费总额，扣除部分微币。消费金额0.01
							$row['paymoney']=0.01;
							$row['deduct']=$calCartInfo[1]+$arr[1];
							$row['weibi']=($calCartInfo[1]+$arr[1])*$setInfo['cash'];
							$row['otype']=2;
						}
						
					}else{
						$row['paymoney']=$calCartInfo[1]+$arr[1];
						$row['otype']=1;
					}
					//=========================
					$row['info']=serialize($normalCart);
					$row['logourl'] = $productsByKey[key($normalCart)]['logourl'];
					//p($arr);p($row);exit;					
					$normal_rt=$product_cart_model->add($row);

					$orderids['normal']=$normal_rt;
				}
			}else {
				/*
				//订桌
				if (intval($this->_post('tableid'))&&$this->_post('buytimestamp')){//只是预定餐桌
					$row['total']=0;
					$row['price']=0;
					//
					$row['diningtype']=intval($this->_post('diningtype'));
					$row['buytime']=$buytimestamp?$row['month'].'月'.$row['day'].'日'.$row['hour'].'点':'';
					$row['tableid']=intval($this->_post('tableid'));
					$row['info']=serialize($diningCart);
					//
					$row['groupon']=0;
					$row['dining']=1;
					$orderDining_rt=$product_cart_model->add($row);
				}
				*/
			}
			//购物成功之后
			//if ($normal_rt||$groupon_rt||$dining_rt||$orderDining_rt){
			if ($normal_rt){	
				$product_model=M('product');
				$product_cart_list_model=M('product_cart_list');
				$crow=array();
				if ($cartsCount){
					foreach ($carts as $k=>$c){
						$crow['cartid']=intval($orderids[$c['type']]);
						$crow['productid']=$k;
						$crow['price']=$c['price'];
						$crow['total']=$c['count'];
						$crow['wecha_id']=$row['wecha_id'];
						$crow['token']=$row['token'];
						$crow['time']=$time;
						$product_cart_list_model->add($crow);
						$product_model->where(array('id'=>$k))->setInc('salecount',$c['count']); //销售量+1(购买量，不是真正的售出量)
						$isNum=$product_model->where(array('id'=>$k))->getField('num');
						if($isNum != ''){
							$product_model->where(array('id'=>$k))->setDec('num',$c['count']); //库存-1
						}	
					}
				}
				$_SESSION['session_cart_products']='';//清空购物车
				//保存个人信息
				if ($_POST['saveinfo']){
					$userRow=array('tel'=>$row['tel'],'truename'=>$row['truename'],'address'=>$row['address'],'email'=>$row['email']);
					if ($thisUser && !$thisUser['id']){
						$userinfo_model->where(array('id'=>$thisUser['id']))->save($userRow);
					}else {
						//下面这段代码是否有存在的必要？？
						$userRow['token']=$this->token;
						$userRow['wecha_id']=$this->wecha_id;
						$userRow['wechaname']='';
						$userRow['qq']=0;
						$userRow['sex']=-1;
						$userRow['age']=0;
						$userRow['birthday']='';
						$userRow['info']='';
						//
						$userRow['total_score']=0;
						$userRow['sign_score']=0;
						$userRow['expend_score']=0;
						$userRow['continuous']=0;
						$userRow['add_expend']=0;
						$userRow['add_expend_time']=0;
						$userRow['live_time']=0;
						$userinfo_model->add($userRow);
					}
				}
			

			//增加返利操作 start
			//$this->rebate($normal_rt);
			//增加返利操作 end
			//增加自动成为店主操作
			//$this->autoStore();
			//end
			}
			
			
			//增加微信回复
			//$this->wechaResponse();
			$content = $this->sms();

			$access_token = getAccessToken($this->token);
			if($access_token["status"]){
				$data='{"touser":"'.$this->wecha_id.'","msgtype":"text","text":{"content":"'.$content.'"}}';
				$url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token['info'];
				$this->api_notice_increment($url,$data);
			}else{
				Log::write("Wap/ProductAction.orderCart - getAccessToken:".$access_token["info"]);
		    }

			//结束
			// 增加 发送短信
			$info=M('Wxuser')->where(array('token'=>$this->token))->find();
			$phone=$info['phone'];

			$user=$info['smsuser'];//短信平台帐号
			$pass=md5($info['smspassword']);//短信平台密码
			$smsstatus=$info['smsstatus'];//短信平台状态

			

			if ($smsstatus == 1) {
				if ($content) {
					$smsrs = file_get_contents('http://api.smsbao.com/sms?u='.$user.'&p='.$pass.'&m='.$phone.'&c='.urlencode($content));
					//$log = file_get_contents('http://www.test.com/test.php?u=' . $user . '&p=' . $pass . '&m=' . $phone . '&test=' . urlencode($content));
				}
			}
			// 结束

			

			// 增加 发送邮件

			$email=$info['email'];
			$emailuser=$info['emailuser'];
			$emailpassword=$info['emailpassword'];
			$emailstatus=$info['emailstatus'];
			$emailsmtp=$info['emailsmtp'];

			if ($emailstatus == 1) {
				if ($content) {
					$this->sentmail($emailuser,$emailpassword,$emailsmtp,$email,$content);
					$this->sentmail($emailuser,$emailpassword,$emailsmtp,$row['email'],$content);
				}
			}

			// 结束
			//下面可以增加 返利 操作（是否放到微支付里进行这个操作比较合适？）
                        //$this->redirect(U('Demo/pay',array('body'=>trim($orderName),'out_trade_no'=>$orderid,'total_fee'=>$totalFee,'openid'=>$this->wecha_id,'showwxpaytitle'=>1)));
						
						
header('Location:/wxpay/index.php/Wap/Wxpay/pay/?id='.$normal_rt.'&store_id='.$this->store_id.'&token='.$this->token.'&showwxpaytitle=1');

						//$this->redirect(U('Product/my',array('token'=>$_GET['token'])));
						
//			if ($alipayConfig['open']){
//				$this->redirect(U('Alipay/pay',array('token'=>$this->token,'wecha_id'=>$this->wecha_id,'success'=>1,'price'=>$totalFee,'orderName'=>$orderName,'orderid'=>$orderid)));
//			}else {
//				//（我的）全部订单
//				echo '支付成功之后，跑到这里';
//				exit;
//				$this->redirect(U('Product/my',array('token'=>$_GET['token'],'wecha_id'=>$_GET['wecha_id'],'success'=>1)));
//			}
		}else {
			/*已经去掉订餐的代码*/
			$this->assign('metaTitle','购物车结算');
			$this->display();
		}
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
	
	public function sentmail($zh,$mm,$smtp,$tomail,$html){
		import('Think.Behavior.Smtp');
		$smtpserver = $smtp;//SMTP服务器
		$smtpserverport =25;//SMTP服务器端口
		$smtpusermail = $zh;//SMTP服务器的用户邮箱
		$smtpemailto = $tomail;//发送给谁
		$smtpuser = $zh;//SMTP服务器的用户帐号
		$smtppass = $mm;//SMTP服务器的用户密码
		$mailsubject = "QQ微信-您有新订单";//邮件主题
		$mailbody = $html;//邮件内容
		$mailtype = "HTML";//邮件格式（HTML/TXT）,TXT为文本邮件
		$smtp = new smtp($smtpserver,$smtpserverport,true,$smtpuser,$smtppass);//这里面的一个true是表示使用身份验证,否则不使用身份验证.
		$smtp->debug = FALSE;//是否显示发送的调试信息
		$smtp->sendmail($smtpemailto, $smtpusermail, $mailsubject, $mailbody, $mailtype);
	}

	// 短信内容 增加内容起 //
	public function sms(){
		$where['token']=$this->token;
		$where['wecha_id']=$this->wecha_id;
		$where['printed']=0;
		$this->product_cart_model=M('product_cart');
		$count      = $this->product_cart_model->where($where)->count();
		$orders=$this->product_cart_model->where($where)->order('time DESC')->limit(0,1)->select();
		
		$now=time();
		if ($orders){
			$thisOrder=$orders[0];
			switch ($thisOrder['diningtype']){
				case 0:
					$orderType='购物';
					break;
				case 1:
					$orderType='点餐';
					break;
				case 2:
					$orderType='外卖';
					break;
				case 3:
					$orderType='预定餐桌';
					break;
			}
			
			//订餐信息
			$product_diningtable_model=M('product_diningtable');
			if ($thisOrder['tableid']) {
				$thisTable=$product_diningtable_model->where(array('id'=>$thisOrder['tableid']))->find();
				$thisOrder['tableName']=$thisTable['name'];
			}else{
				$thisOrder['tableName']='未指定';
			}
			//==
			$str="订单类型：".$orderType."\r\n订单编号：".$thisOrder['orderid']."\r\n姓名：".$thisOrder['truename']."\r\n电话：".$thisOrder['tel']."\r\n地址：".$thisOrder['address']."\r\n下单时间：".date('Y-m-d H:i:s',$thisOrder['time'])."\r\n";
			//
			$carts=unserialize($thisOrder['info']);

			//
			$totalFee=0;
			$totalCount=0;
			$products=array();
			$ids=array();
			foreach ($carts as $k=>$c){
				if (is_array($c)){
					$productid=$k;
					$price=$c['price'];
					$count=$c['count'];
					//
					if (!in_array($productid,$ids)){
						array_push($ids,$productid);
					}
					$totalFee+=$price*$count;
					$totalCount+=$count;
				}
			}
			if (count($ids)){
				$products=$this->product_model->where(array('id'=>array('in',$ids)))->select();
			}
			if ($products){
				$i=0;
				foreach ($products as $p){
					$products[$i]['count']=$carts[$p['id']]['count'];
					$str.=$p['name']."  ".$products[$i]['count']."份  单价：".$p['price']."元\r\n";
					$i++;
				}
			}
			$str.="合计：".$thisOrder['price']."元";
			/* if ($thisOrder['paid']==0){
				//未付款
				$url="<a href='http://xulin.v-hang.cn/index.php?g=Wap&m=Product&a=my&token=".$this->token."&wecha_id=".$this->wecha_id."'>我的订单</a>";
				$str.="\r\n状态：未付款"."\r\n去付款".$url;
			}
			if ($thisOrder['paid']==1){
				//未付款
				$url="<a href='http://xulin.v-hang.cn/index.php?g=Wap&m=Product&a=my&token=".$this->token."&wecha_id=".$this->wecha_id."'>我的订单</a>";
				$str.="\r\n状态：已付款"."\r\n查看".$url;
			} */
			
			$url="<a href='".C('site_url')."/index.php?g=Wap&m=Product&a=my&token=".$this->token."&store_id=".$this->store_id."'>我的订单</a>";
			$str.="\r\n查看".$url;
			return $str;
		}else {
			return '';
		}
	}

	//增加内容止//

	//（我的）全部订单
	public function my(){
		$product_cart_model=M('product_cart');
		//$this->wecha_id
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		switch($_GET['ostatus']){
			case 1:
			//$map['ostatus']=1;
			//break;
			case 2:
			//$map['ostatus']=2;
			//break;
			case 3:
			//$map['ostatus']=3;
			//break;
			case 4:
			$map['ostatus']=$_GET['ostatus'];
			break;
		}
		$map['showstatus']=1; //是否显示  1显示 0 不显示（删除订单）
		$orders=$product_cart_model->where($map)->order('time DESC')->select();
		$this->setOrdersCount();
		//p($nopayCount);
		//echo $product_cart_model->getLastSql();
		//exit;
		if ($orders){
			foreach ($orders as $o){
				$products=unserialize($o['info']);
				//$firstProductID=$products
			}
		} 
		//p($orders);
		//exit;
		$this->assign('orders',$orders);
		$this->assign('metaTitle','我的订单');
		//
		//是否要支付
		$alipay_config_db=M('Alipay_config');
		$alipayConfig=$alipay_config_db->where(array('token'=>$this->token))->find();
		$this->assign('alipayConfig',$alipayConfig);
		//
		$this->display();
	}
	//updateOrder()改成payDetail（）
	public function payDetail(){
		$this->setOrdersCount();
		$product_cart_model=M('product_cart');
		$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		if($thisOrder['paytime']+604800< time()){
			//订单已经超过七天
			$outdate=1;
			
		}else{
			$outdate=0;
		}
		$this->assign('outdate',$outdate);
		//检查权限
		if ($thisOrder['wecha_id']!=$this->wecha_id){
			exit();
		}
		//
		$this->assign('thisOrder',$thisOrder);
		$carts=unserialize($thisOrder['info']);
		//
		$totalFee=0;
		$totalCount=0;
		$products=array();
		$ids=array();
		foreach ($carts as $k=>$c){
			if (is_array($c)){
				$productid=$k;
				$price=$c['price'];
				$count=$c['count'];
				//
				if (!in_array($productid,$ids)){
					array_push($ids,$productid);
				}
				$totalFee+=$price*$count;
				$totalCount+=$count;
			}
		}
		if (count($ids)){
			$list=$this->product_model->where(array('id'=>array('in',$ids)))->select();
		}
		if ($list){
			$i=0;
			foreach ($list as $p){
				$list[$i]['count']=$carts[$p['id']]['count'];
				$i++;
			}
		}
		$this->assign('products',$list);
		//
		$this->assign('totalFee',$totalFee);
		
		$this->assign('metaTitle','订单详情');
		//
		//是否要支付
		$alipay_config_db=M('Alipay_config');
		$alipayConfig=$alipay_config_db->where(array('token'=>$this->token))->find();
		$this->assign('alipayConfig',$alipayConfig);
		//
		$this->display();
	}
	public function deleteOrder(){
		$product_model=M('product');
		$product_cart_model=M('product_cart');
		$product_cart_list_model=M('product_cart_list');
		$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		//检查权限
		$id=$thisOrder['id'];
		if ($thisOrder['wecha_id']!=$this->wecha_id||$thisOrder['handled']==1){
			exit();
		}
		//
		//删除订单和订单列表
		$product_cart_model->where(array('id'=>$id))->delete();
		$product_cart_list_model->where(array('cartid'=>$id))->delete();
		//商品销量做相应的减少
		$carts=unserialize($thisOrder['info']);
		foreach ($carts as $k=>$c){
			if (is_array($c)){
				$productid=$k;
				$price=$c['price'];
				$count=$c['count'];
				$product_model->where(array('id'=>$k))->setDec('salecount',$c['count']);
			}
		}
		$this->redirect(U('Product/my',array('token'=>$_GET['token'],'wecha_id'=>$_GET['wecha_id'])));
	}
	/* 
	public function index(){
		$token_open=M('token_open')->field('queryname')->where(array('token'=>$this->token))->find();
		$this->assign('shopOpen',1);
		$this->assign('diningOpen',1);
		$this->assign('grouponOpen',1);
		if(!strpos($token_open['queryname'],'shop')){
            $this->assign('shopOpen',0);
		}
		if(!strpos($token_open['queryname'],'dx')){
            $this->assign('diningOpen',0);
		}
		if(!strpos($token_open['queryname'],'etuan')){
            $this->assign('grouponOpen',0);
		}
		$this->assign('metaTitle','微信购物');
		$this->display();
	} */
	public function dining(){
		//是否外卖预定等
				$diningConfig =M('Reply_info')->where(array('infotype'=>'Dining','token'=>$this->token))->find();
				$this->assign('diningConfig',$diningConfig);
		$this->assign('metaTitle','订餐');
		$this->display();
	}
	public function a(){
		$where['token']=$this->token;
		$where['diningtype']=array('gt',0);
		

		$where['printed']=0;
		$this->product_cart_model=M('product_cart');
		$count      = $this->product_cart_model->where($where)->count();
		$orders=$this->product_cart_model->where($where)->order('time ASC')->limit(0,1)->select();
		
		$now=time();
		if ($orders){
			$thisOrder=$orders[0];
			switch ($thisOrder['diningtype']){
				case 1:
					$orderType='点餐';
					break;
				case 2:
					$orderType='外卖';
					break;
				case 3:
					$orderType='预定餐桌';
					break;
			}
			
			//订餐信息
			$product_diningtable_model=M('product_diningtable');
			if ($thisOrder['tableid']) {
				$thisTable=$product_diningtable_model->where(array('id'=>$thisOrder['tableid']))->find();
				$thisOrder['tableName']=$thisTable['name'];
			}else{
				$thisOrder['tableName']='未指定';
			}
			$str="订单类型：".$orderType."\r\n订单编号：".$thisOrder['id']."\r\n姓名：".$thisOrder['truename']."\r\n电话：".$thisOrder['tel']."\r\n地址：".$thisOrder['address']."\r\n桌台：".$thisOrder['tableName']."\r\n下单时间：".date('Y-m-d H:i:s',$thisOrder['time'])."\r\n打印时间：".date('Y-m-d H:i:s',$now)."\r\n--------------------------------\r\n";
			//
			$carts=unserialize($thisOrder['info']);

			//
			$totalFee=0;
			$totalCount=0;
			$products=array();
			$ids=array();
			foreach ($carts as $k=>$c){
				if (is_array($c)){
					$productid=$k;
					$price=$c['price'];
					$count=$c['count'];
					//
					if (!in_array($productid,$ids)){
						array_push($ids,$productid);
					}
					$totalFee+=$price*$count;
					$totalCount+=$count;
				}
			}
			if (count($ids)){
				$products=$this->product_model->where(array('id'=>array('in',$ids)))->select();
			}
			if ($products){
				$i=0;
				foreach ($products as $p){
					$products[$i]['count']=$carts[$p['id']]['count'];
					$str.=$p['name']."  ".$products[$i]['count']."份  单价：".$p['price']."元\r\n";
					$i++;
				}
			}
			$str.="\r\n--------------------------------\r\n合计：".$thisOrder['price']."元\r\n     谢谢惠顾，欢迎下次光临\r\n";
			//店铺信息
			$member_card_info_model=M('Member_card_info');
			$thisCompany=$member_card_info_model->where(array('token'=>$this->token))->find();
			$str.="     ".$thisCompany['info']."\r\n
			";
			//
			$str=iconv('utf-8','gbk',$str);
			//设置为打印过了
			$this->product_cart_model->where(array('id'=>$thisOrder['id']))->save(array('printed'=>1));
			echo "CMD=01	FLAG=0	MESSAGE=成功	DATETIME=".date('YmdHis',$now)."	ORDERCOUNT=".$count."	ORDERID=".$thisOrder['id']."	PRINT=".$str;
		}else {
			echo "CMD=01	FLAG=1	MESSAGE=no order now	DATETIME=".date('YmdHis',time())."\r\n
	";
		}
	}
	/**
	 * 检查某时间段内是否已经有和桌子被预定
	 *
	 */
	public function ajaxTables(){
		$token=$this->_get('token');
		$time=$this->_get('time');
		$hour=intval($this->_get('hour'));
		$year=date('Y',$time);
		$month=date('m',$time);
		$day=date('d',$time);
		$occupiedTables=array();
		$product_cart_model=M('product_cart');
		$otableWhere=array();
		$otableWhere['token']=$token;
		$otableWhere['hour']=array('between',array($hour-3,$hour+3));//三个小时内不能再定
		$otableWhere['year']=$year;
		$otableWhere['month']=$month;
		$otableWhere['day']=$day;
		$otables=$product_cart_model->where($otableWhere)->select();
		$str='';
		$comma='';
		if ($otables){
			foreach ($otables as $t){
				if (!in_array($t['tableid'],$occupiedTables)){
					$str.=$comma.$t['tableid'];
					array_push($occupiedTables,$t['tableid']);
					$comma=',';
				}
			}
		}
		echo $str;
	}
	//个人中心
	public function personalCenter(){
		//先判断是否已经获得用户个人信息
		if(!$this->storeInfo['nickname']){
			//$this->error('请先点击成为会员后再申请！',U('Wap/Product/personalCenter',array('token'=>$this->token)));
			$wxuserInfo=M('wxuser_people')->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->field('nickname,headimgurl')->find();
			if($wxuserInfo){
				$storeAddInfo=M('store')->where(array('wecha_id'=>$this->wecha_id,'token'=>$this->token))->save(array('nickname'=>$wxuserInfo['nickname'],'headimgurl'=>$wxuserInfo['headimgurl']));
			}				
		}
		//个人微店铺信息
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
		$this->assign('peppleInfo',$this->storeInfo);
		$this->assign('vipMap',$vipMap);
		$this->assign('metaTitle','个人中心');
		$this->setOrdersCount();
		$this->display();
	}
	
	
	//确认收货
	public function confirmGood()
    {
        $id = $this->_get('id');
        $where = array('id' => $id, 'token' =>$this->token);
        $data['confirmtime'] = time();
        $data['ostatus'] = 4;
        $back = M('product_cart')->where($where)->save($data);
        //$this->redirect("Wap/Product/my", $_GET);
		$this->redirect('Product/my', array('token' => $this->token,'ostatus'=>3));
    }
	//确认收款
	public function moneyBack()
    {
        $id = $this->_get('id');
        $where = array('id' => $id, 'token' =>$this->token);
        $data['confirmtime'] = time();
        $data['ostatus'] = 6;
        $back = M('product_cart')->where($where)->save($data);
        //$this->redirect("Wap/Product/my", $_GET);
		$this->redirect('Product/my', array('token' => $this->token));
    }
	//取消订单（这个取消订单，确实应该把库存还回去的）
	public function  cancelOrder()
    {
        $id = $this->_get('id');
        $where = array('id' => $id, 'token' =>$this->token);
        //$data['confirmtime'] = time();
        $data['ostatus'] =8; //取消订单
        $back = M('product_cart')->where($where)->save($data);
        //$this->redirect("Wap/Product/my", $_GET);
		$this->redirect('Product/my', array('token' => $this->token));
    }
	//删除订单
	public function delOrder()
    {
        $id = $this->_get('id');
        $where = array('id' => $id, 'token' => $this->token);
        $data['showstatus'] = 0; //不显示出来
        $back = M('product_cart')->where($where)->save($data);
		$this->redirect('Product/my', array('token' => $this->token));
    }
	//催促卖家urge
	public function urgeSeller()
    {
        $id = $this->_post('id');
        $where = array('pcid' => $id,);
		//$productCartInfo=M('product_cart')->where($where)->find();
		$urge = M("urge");
		$oldUrge=$urge->where($where)->max("urgetime");//最新的催促时间
		if(empty($oldUrge) || intval($oldUrge) + 86400 < time()){
			$now=time();
			$data['token'] =  $this->token;
			$data['wecha_id'] =  $this->wecha_id;
			$data['pcid'] =  $id;
			$data['urgetime'] = $now;
			//$data['urgecount'] = $now;
			$result=$urge->add($data);
			/* if($result){
				$urge->where(array('id'=>$result))->setInc('urgecount',1); // 催促次数加1
			} */
			echo'催促成功，卖家会尽快给您发货！';
		}
		else{
			echo'催促失败,你24小时内已经催促过了';
		}
		
    }
	//申请退款 填写申请表单
	public function refund(){
		$product_cart_model=M('product_cart');
		$thisOrder=$product_cart_model->where(array('id'=>intval($_REQUEST['id'])))->find();
		$this->assign('metaTitle','退货申请');
		//p($thisOrder['time']+604800);
		//echo time();
		//exit;
		//先查下订单的时间是否已经超出七天，如果是，无法申请退货。
		if(intval($thisOrder['paytime']) + 604800 < time()){
			//$this->error('该订单已经超出时间，无法退货！',U('Wap/Product/my',array('token'=>$this->token)));	
			echo '该订单已经超出时间，无法退货！';exit;
		}else{
			//订单的状态ostatus=4(交易成功)，才可以申请退货。（其实该链接本来就是从ostatus=4进入的，下面多做一层验证，保险点）
			
			if($thisOrder['ostatus']==4 || $thisOrder['ostatus']==2){
				if (IS_POST){
						$arr['id'] = $this->_post('id');
						$arr['reason'] =  $this->_post('reason');
						$arr['refundtime'] = time();
						$arr['ostatus'] = 9; // 状态由“完成”变为“退货审核中”
						$back = M('product_cart')->save($arr);
						//返还记录表
						$rebateRec=M('rebate_record')->where(array('orderid'=>$thisOrder['orderid']))->setField('status',2);
						if($back){
							echo'提交申请成功！';
							exit;
						}
						else{
							echo'您已经提交过申请！';
							exit;
						}
				}else{
					//echo '订单合法！';
					//===显示申请的页面信息
					$product_cart_model=M('product_cart');
					$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
					$carts=unserialize($thisOrder['info']);
					//
					$totalFee=0;
					$totalCount=0;
					$products=array();
					$ids=array();
					foreach ($carts as $k=>$c){
						if (is_array($c)){
							$productid=$k;
							$price=$c['price'];
							$count=$c['count'];
							//
							if (!in_array($productid,$ids)){
								array_push($ids,$productid);
							}
							$totalFee+=$price*$count;
							$totalCount+=$count;
						}
					}
					//======
					$this->assign('thisOrder',$thisOrder);
					$this->assign('totalFee',$totalFee);
					$this->display();
					//=======
				}		
			}
			else{
				echo '订单不合法！';exit;
			}
		}		
	}
	//申请微店 1、是不是已经提交过申请 
	//2、分析该用户是不是有父类fid，爷类gid，祖父类ggid（在_initialize处理了，这里不需要再考虑）
	public function applyStore(){
		//先判断是否已经获得用户个人信息
		if(!$this->storeInfo['nickname']){
			$this->error('请先点击成为会员后再申请！',U('Wap/Product/personalCenter',array('token'=>$this->token)));
				
		}
		//个人订单信息
		$product_cart_model=M('product_cart');
		//$this->wecha_id
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		//$map['ostatus']=4;//交易完成
		$map['ostatus']  = array('in','2,3,4');//用户体验说支付成功之后的订单就可当申请凭据
		$orders=$product_cart_model->where($map)->order('time DESC')->field('orderid,price')->select();
		$this->assign('orders',$orders);
		//p($orders);
		//exit;		
		//未申请，可以进入填写页面  1是审核中，0或者3可以申请 2是已经通过审核，根本看不到“申请链接” 所以只需要考虑1，0，3情况
		if($this->storeInfo['status']==3){
			$this->error('审核中，请等待！',U('Wap/Product/personalCenter',array('token'=>$this->token)));	
		}
		elseif($this->storeInfo['status']==1){
			$this->error('审核通过，你已经是微店主！',U('Wap/Product/personalCenter',array('token'=>$this->token)));
		}else{
			if (IS_POST){
					$store=M('store');
					$data['id'] =  $this->storeInfo['id'];//店铺ID
					$data['username'] =  $this->_post('username');
					$data['telphone'] =  $this->_post('telphone');
					//$data['wechat'] =  $this->_post('wechat');
					//$data['qq'] =  $this->_post('qq');
					//$data['email'] =  $this->_post('email');
					//$data['orderid'] =  $this->_post('orderid');
					$arr = explode(',',$this->_post('orderid'));//把订单的价格也传递过来
					$data['orderid'] =  $arr[0];
					//$data['price'] =  $arr[1];					
					$data['applytime'] = time();//申请时间
					$data['status'] = 3;   //状态由未申请（0）变成审核中（3）  0未申请 3审核中 1申请通过 2申请拒绝
					//自动通过申请============
					//读取代理资格配置表
					$setInfo=M('distribution_agent')->where(array('token'=>$this->token))->find();
					if(empty($setInfo)){$this->error("商家未设置代理资格，无法自动审核申请，请稍后再试");exit;}
					if($arr[1]>=$setInfo['level3']){
						//钻眼代理 vip=3
						$data['vip'] = 3;    
						$data['status'] = 1;
						$data['applyhandled'] = time();//处理申请的时间
						
					}elseif($arr[1]>=$setInfo['level2']){
						//金眼代理 vip=2
						$data['vip'] = 2;    
						$data['status'] = 1;
						$data['applyhandled'] = time();//处理申请的时间
						
					}elseif($arr[1]>=$setInfo['level1']){
						//银眼代理 vip=1
						$data['vip'] = 1;    
						$data['status'] = 1;
						$data['applyhandled'] = time();//处理申请的时间
						
					}else{
						//不符合资格，交给后台审核
						$data['status'] = 3;	
					}				
					//========================
					$result=$store->save($data);
					if($result){
						//如果userinfo表中还没填写个人信息，把申请的个人信息插入表中
						$userinfo_model=M('Userinfo');
						$thisUser=$userinfo_model->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
						if (!$thisUser){
							$userRow['token']=$this->token;
							$userRow['wecha_id']=$this->wecha_id;
							$userRow['truename'] =  $this->_post('username');
							$userRow['telphone'] =  $this->_post('telphone');
							//$userRow['wechaname']=$this->_post('wechat');
							//$userRow['qq']=$this->_post('qq');
							//$userRow['email']=$this->_post('email');
							
							//$userRow['sex']=-1;
							//$userRow['age']=0;
							//$userRow['birthday']='';
							//$userRow['info']='';
							//
							$userinfo_model->add($userRow);
						}
						echo'提交申请成功！';
						exit;
					}else{
						echo'提交申请失败！';
						exit;
					}
			
			}	
		}
		$this->assign('metaTitle','申请成为微店主');	
		$this->display();
	}

	//我的收藏
	public function myCollect(){
		$map['token']=$_GET['token'];
		$map['wecha_id']=$this->wecha_id ;
		//$collectInfo=M('Collect')->where($map)->select();
		$collectPid=M('Collect')->distinct(true)->where($map)->getField('pid',true);
		if(!empty($collectPid)){
		//====商品详情=========				
		$db=M('product');		
		$condition['id']=array('in',$collectPid);
		$count=$db->where($condition)->count();
		$collectInfo=$db->where($condition)->order('time desc')->limit('4')->select();
		$this->assign('count',$count);
		$this->assign('collectInfo',$collectInfo);
		}
		$this->assign('metaTitle','我的收藏列表');
		$this->display();
		
	}
	public function ajaxMycollect(){
		$map['token']=$_GET['token'];
		$map['wecha_id']=$this->wecha_id ;
		$page=isset($_GET['page'])&&intval($_GET['page'])>1?intval($_GET['page']):2;
		$pageSize=isset($_GET['pagesize'])&&intval($_GET['pagesize'])>1?intval($_GET['pagesize']):5;
		//$collectInfo=M('Collect')->where($map)->select();
		$collectPid=M('Collect')->distinct(true)->where($map)->getField('pid',true);
		if(!empty($collectPid)){
			//====商品详情=========				
			$db=M('product');		
			$condition['id']=array('in',$collectPid);
			$start=($page-1)*$pageSize;
			$products=$db->where($condition)->order('time desc')->limit($start.','.$pageSize)->select();
		}
		
		$str='{"products":[';
		if ($products){
			$comma='';
			foreach ($products as $p){
				$str.=$comma.'{"id":"'.$p['id'].'","catid":"'.$p['catid'].'","storeid":"'.$p['storeid'].'","name":"'.$p['name'].'","price":"'.$p['price'].'","token":"'.$p['token'].'","keyword":"'.$p['keyword'].'","salecount":"'.$p['salecount'].'","logourl":"'.$p['logourl'].'","time":"'.$p['time'].'","oprice":"'.$p['oprice'].'"}';
				$comma=',';
			}
		}
		$str.=']}';
		$this->show($str);
	}
	//添加到“我的收藏”
	public function addCollect(){
		$CollectModel=M('Collect');
		//判断这个是否已经收藏
		$map['token']=$_GET['token'];
		$map['wecha_id']=$this->wecha_id ;
		$map['pid']=$this->_get('productid');
		$collectPid=$CollectModel->distinct(true)->where($map)->getField('pid',true);
		if(empty($collectPid)){
			//===============
			$data=array();
			$data['pid'] 		= $this->_get('productid');
			$data['token'] 		= $this->_get('token');
			$data['wecha_id'] = $this->wecha_id;
			$data['createtime'] = time(); 
			$data['store_id'] 		= $this->store_id;
			//$data['collect_url'] = $_SERVER['REQUEST_URI'];
			$data['url'] = "/index.php?g=Wap&m=Product&a=index&token=".$this->_get('token')."&id=".$this->_get('productid');
			$res=$CollectModel->add($data);
			if($res){
				echo'收藏成功';
				exit;
			} else {
				echo'收藏失败';
			}
		}
		else{
			echo'已收藏';
		}	
		
	}
	
	//个人资料
	public function myAdress(){
		$this->peopleInfo();
		$this->assign('metaTitle','个人资料');
		$this->display();
	}
	//个人资料填写处理
	public function myAdressDeal(){
		$userinfo_model=M('Userinfo');
		$thisUser=$userinfo_model->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
		if(IS_POST){
			$data['token']=$this->token;
			$data['wecha_id']=$this->wecha_id;
			$data['truename']=$this->_post('truename');
			$data['wechaname']=$this->_post('wechaname');
			$data['tel']=$this->_post('tel');
			$data['qq']=$this->_post('qq');
			$data['email']=$this->_post('email');
			$data['address']=$this->_post('address');
			$data['sex']=$this->_post('sex');
			$data['womanLog']=$this->_post('womanLog');
			if(empty($thisUser)){
				//执行add（）
				$res=$userinfo_model->add($data);
			}
			else{
				//执行save
				$data['id']=$thisUser['id'];
				$res=$userinfo_model->save($data);
			}
			if($res){
				//$this->success("操作成功",U('Distribution/set',array('token'=>$this->token)));
				//echo M('distribution_set')->getLastSql();
				echo '操作成功';
	
			}
			else{
				//$this->error("操作失败,请确认输入的值是否有改动",U('Distribution/set',array('token'=>$this->token)));
				//echo "res:".$res;
				//echo $setModel->getLastSql();
				echo '操作失败';
			}
		}
		else{
			$this->assign('thisUser',$thisUser);
			$this->display();
		}
	}
	//银行卡
	public function myBank(){
		$this->peopleInfo();
		$this->assign('metaTitle','我的银行卡');
		$this->display();
	}
	//银行卡资料填写处理
	public function myBankDeal(){
		$userinfo_model=M('Userinfo');
		$thisUser=$userinfo_model->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
		if(IS_POST){
			$data['token']=$this->token;
			$data['bankcardusername']=$this->_post('bankcardusername');
			$data['bankcard']=$this->_post('bankcard');
			$data['bankname']=$this->_post('bankname');
			$data['subbankname']=$this->_post('subbankname');
			
			if(empty($thisUser)){
				//执行add（）
				$res=$userinfo_model->add($data);
			}
			else{
				//执行save
				$data['id']=$thisUser['id'];
				$res=$userinfo_model->save($data);
			}
			if($res){
				//$this->success("操作成功",U('Distribution/set',array('token'=>$this->token)));
				//echo M('distribution_set')->getLastSql();
				echo '操作成功';
	
			}
			else{
				//$this->error("操作失败,请确认输入的值是否有改动",U('Distribution/set',array('token'=>$this->token)));
				//echo "res:".$res;
				//echo $setModel->getLastSql();
				echo '操作失败';
			}
		}
		else{
			$this->assign('thisUser',$thisUser);
			$this->display();
		}
	}
	//支付宝
	public function myZhifubao(){
		$this->peopleInfo();
		$this->assign('metaTitle','我的支付宝');
		$this->display();
	}
	//支付宝资料填写处理
	public function myZhifubaoDeal(){
		$userinfo_model=M('Userinfo');
		$thisUser=$userinfo_model->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
		if(IS_POST){
			$data['token']=$this->token;
			$data['zhifubaousername']=$this->_post('zhifubaousername');
			$data['zhifubao']=$this->_post('zhifubao');
			
				
			if(empty($thisUser)){
				//执行add（）
				$res=$userinfo_model->add($data);
			}
			else{
				//执行save
				$data['id']=$thisUser['id'];
				$res=$userinfo_model->save($data);
			}
			if($res){
				//$this->success("操作成功",U('Distribution/set',array('token'=>$this->token)));
				//echo M('distribution_set')->getLastSql();
				echo '操作成功';
	
			}
			else{
				//$this->error("操作失败,请确认输入的值是否有改动",U('Distribution/set',array('token'=>$this->token)));
				//echo "res:".$res;
				//echo $setModel->getLastSql();
				echo '操作失败';
			}
		}
		else{
			$this->assign('thisUser',$thisUser);
			$this->display();
		}
	}
	//===============
	/*2015年3月2日 17:30:55*/
	//兑换专区
	public function package_list(){
		$db=M('product_jifen');
		$map['token']=$_GET['token'];
		$map['status']=1;
		$count=$db->where($map)->count();
		$productJifen=$db->where($map)->order('time desc')->limit('4')->select();
		$this->assign('count',$count);
		$this->assign('productJifen',$productJifen);
		$this->assign('metaTitle','商品兑换');
		$this->display();	
	}
	public function ajaxPackage(){
		$page=isset($_GET['page'])&&intval($_GET['page'])>1?intval($_GET['page']):2;
		$pageSize=isset($_GET['pagesize'])&&intval($_GET['pagesize'])>1?intval($_GET['pagesize']):5;

		//====商品详情=========				
		$db=M('product_jifen');		
		$map['token']=$_GET['token'];
		$map['status']=1;
		$start=($page-1)*$pageSize;
		$products=$db->where($map)->order('time desc')->limit($start.','.$pageSize)->select();

		$str='{"products":[';
		if ($products){
			$comma='';
			foreach ($products as $p){
				$str.=$comma.'{"id":"'.$p['id'].'","catid":"'.$p['catid'].'","storeid":"'.$p['storeid'].'","name":"'.$p['name'].'","price":"'.$p['price'].'","token":"'.$p['token'].'","keyword":"'.$p['keyword'].'","salecount":"'.$p['salecount'].'","logourl":"'.$p['logourl'].'","time":"'.$p['time'].'","oprice":"'.$p['oprice'].'"}';
				$comma=',';
			}
		}
		$str.=']}';
		$this->show($str);
	}
	public function package(){

		$where=array('token'=>$this->token);
		
		if (isset($_GET['id'])){
			$id=intval($_GET['id']);
			$where['id']=$id;
		}
		
		$productJifen=M('product_jifen')->where($where)->find();
		
		//p($productJifen);
		//exit;
		$this->assign('product',$productJifen);
		if ($product['endtime']){
			$leftSeconds=intval($product['endtime']-time());
			$this->assign('leftSeconds',$leftSeconds);
		}
		$this->assign('metaTitle',$product['name']);
		/* 
		$product['intro']=str_replace(array('&lt;','&gt;','&quot;','&amp;nbsp;'),array('<','>','"',' '),$product['intro']);
		$intro=$this->remove_html_tag($product['intro']);
		$intro=mb_substr($intro,0,30,'utf-8');
		 */
		$productJifen['intro']=str_replace(array('&lt;','&gt;','&quot;','&amp;nbsp;'),array('<','>','"',' '),$productJifen['intro']); 
		$intro=$productJifen['intro'];
		$this->assign('intro',$intro);
		//分店信息
		$company_model=M('Company');
		$branchStoreCount=$company_model->where(array('token'=>$this->token,'isbranch'=>1))->count();
		$this->assign('branchStoreCount',$branchStoreCount);
		//销量最高的商品
		$sameCompanyProductWhere=array('token'=>$this->token,'id'=>array('neq',$product['id']));
		if ($product['dining']){
			$sameCompanyProductWhere['dining']=1;
		}
		if ($product['groupon']){
			$sameCompanyProductWhere['groupon']=1;
		}
		if (!$product['groupon']&&!$product['dining']){
			$sameCompanyProductWhere['groupon']=array('neq',1);
			$sameCompanyProductWhere['dining']=array('neq',1);
		}
		if (isset($_GET['catid'])){
			$sameCompanyProductWhere['catid']=$_GET['catid'];
		}
		$products=M('product_jifen')->where($sameCompanyProductWhere)->limit('salecount DESC')->limit('0,5')->select();
		$this->assign('products',$products);
		$this->assign('metaTitle','商品详情页');
		$this->display();
	}
	//去兑换
	public function packageConfirm(){
		//个人信息
		$userinfo_model=M('Userinfo');
		$thisUser=$userinfo_model->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
		$this->assign('thisUser',$thisUser);		
		//兑换商品信息
		$thisProduct=M('Product_jifen')->where(array('token'=>$this->token,'id'=>$_GET['id']))->find();
		$this->assign('thisProduct',$thisProduct);
		//个人兑换点
		$this->store_model=M('Store');  //微店铺表
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		$storeInfo=$this->store_model->where($map)->find();
		$this->assign('storeInfo',$storeInfo);
		//
		$this->assign('metaTitle','商品兑换');
		$this->display();
	}
	//立即兑换处理
	public function packageBuy()
    {
		$id = $this->_post('id');
        $where = array('id' => $id);
		$ProductJifen=M("Product_jifen")->where($where)->find();//兑换商品的信息
		//兑换商品的剩余量
		if($ProductJifen['num']<1)
		{
			echo '已售完';exit;
		}
		//个人兑换点
		$this->store_model=M('Store');  //微店铺表
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		$storeInfo=$this->store_model->where($map)->find();
		if($storeInfo['coin']<$ProductJifen['price'])
		{
			echo '积分不足，无法兑换此商品';
		}else{
			//echo '可以兑换';
			$row=array();
			//订单号的处理
			$randLength=6;
			$chars='abcdefghijklmnopqrstuvwxyz';
			$len=strlen($chars);
			$randStr='';
			for ($i=0;$i<$randLength;$i++){
				$randStr.=$chars[rand(0,$len-1)];
			}
			$orderid=$randStr.time();
			//======
			$row['orderid']=$orderid;
			//
			$row['truename']=$this->_post('truename');
			$row['tel']=$this->_post('tel');
			$row['address']=$this->_post('address');
			//=======================
			$row['token']=$this->token;
			$row['wecha_id']=$this->wecha_id;
			
			//
			$time=time();
			$row['time']=$time;
			$row['pid']=$id;
			$row['total']=1;
			$row['price']=$ProductJifen['price'];
			$row['logourl']=$ProductJifen['logourl'];
			//
			$result=M('product_jifen_cart')->add($row);
			if($result){
				M('product_jifen')->where(array('id'=>$id))->setInc('salecount',1); //销售量+1
				$isNum=M('product_jifen')->where(array('id'=>$id))->getField('num');
				if($isNum != ''){
					M('product_jifen')->where(array('id'=>$id))->setDec('num',1); //库存-1
				}
				//M('product_jifen')->where(array('id'=>$id))->setDec('num',1); //库存-1
				//扣除对应的点数
				$storeRow=array('coin'=>$storeInfo['coin']-$ProductJifen['price']);
				$this->store_model->where($map)->save($storeRow);
				echo '兑换成功';
			}else{
				echo '插入数据失败';
			}
		}	
    }
	//我的兑换
	public function myPackage(){
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		$jifenOrders=M('product_jifen_cart')->where($map)->order('time DESC')->select();
		$this->assign('jifenOrders',$jifenOrders);
		$this->assign('metaTitle','我的兑换订单');
		$this->display();
	}
	//兑换商品详情
	public function packageDetail(){
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		$map['id']=$_GET['id'];
		$packageInfo=M('product_jifen_cart')->where($map)->find();
		$this->assign('packageInfo',$packageInfo);
		$this->assign('metaTitle','兑换商品详情');
		$this->display();
	}
	//签到积分
    public function signscore()
    {	
        $userScore   = 0;//签到积分（多多币）
        if ($this->storeInfo) {
            $userScore = $this->storeInfo['coin'];
        }
        $this->assign('storeInfo', $this->storeInfo);
        $this->assign('userScore', $userScore);
		//========
        $todaySigned = $this->_todaySigned();
        $this->assign('todaySigned', $todaySigned === 1);
		$this->display();
		/*
        $cardsign_db = M('Member_card_sign');
        $now         = time();
        $day         = date('d', $now);
        $year        = date('Y', $now);
        $month       = date('m', $now);
        if (isset($_GET['month'])) {
            $month = intval($_GET['month']);
        }
        $firstSecondOfMonth = mktime(0, 0, 0, $month, $day, $year);
        $lastSecondOfMonth  = mktime(23, 59, 59, $month, $day, $year);
        $signRecords        = $cardsign_db->where('token=\'' . $this->token . '\' AND wecha_id=\'' . $this->wecha_id . '\' AND sign_time>' . $firstSecondOfMonth . ' AND sign_time<' . $lastSecondOfMonth)->order('sign_time DESC')->select();
        $this->assign('signRecords', $signRecords);
		*/
        
    }
	public function addSign()
    {
        $signed = $this->_todaySigned();
		//查看签到参数是否已经设置
		//sign_in 签到设置表
        $set_exchange = M('sign_in')->where(array(
            'token' => $this->token,
        ))->find();
		if(empty($set_exchange)){
			echo '{"success":0,"msg":"签到还未设置好，暂时不能签到!"}';
			exit();		
		}
		if(empty($set_exchange['status'])){
			echo '{"success":0,"msg":"签到奖励未开启!"}';
			exit();		
		}
        $this->assign('set_exchange', $set_exchange);

        if ($signed === 1) {
            echo '{"success":0,"msg":"您今天已经签到了"}';
            exit();
        }
		//未签到，执行下面操作
        $cardsign_db = M('distribution_sign');
		
		$storeDb=M('store');
		$where=array(
            'token' => $this->token,
            'wecha_id' => $this->wecha_id
        );
        $userinfo           = $storeDb->where($where)->find();
		//========
		//判断是否连续签到
		$continuous =$this->_continuousSigned($signed);
		
        $data['sign_time']  = time();
        $data['is_sign']    = 1;
        $data['score_type'] = 1;
        $data['token']      = $this->token;
        $data['wecha_id']   = $this->wecha_id;
		if($continuous){
			//加上今天签到就是第七天了
			if(($userinfo['continuous']+1)%7==0){
				$data['expense']    = intval($set_exchange['continuation']);//连续七天签到积分		
			}else{
				$data['expense']    = intval($set_exchange['everyday']);//每天签到积分		
			}	
		}else{
			$data['expense']    = intval($set_exchange['everyday']);//每天签到积分	
		}
        $rt                 = $cardsign_db->add($data);
		//p($rt);
		//exit;
        if ($rt) {
			//$da['income'] = $userinfo['income'] + $data['expense'];//积分=原有积分+签到积分
			//$da['sign_score']  = $userinfo['sign_score'] + $data['expense'];  //签到获得积分
			$storeDb->where($where)->setInc('coin',$data['expense']); // 积分=原有积分+签到积分
			$storeDb->where($where)->setInc('sign_score',$data['expense']);
			
			if($continuous){
				//连续签到
				//$da['continuous']  = $userinfo['continuous']+1;	
				$storeDb->where($where)->setInc('continuous');
			}else{
				//不是连续签到
				//$da['continuous']  = 1;
				//$storeDb->where($where)->save();
				$storeDb-> where($where)->setField('continuous',1);
			}
			$result = array("success"=>1,"msg"=>"签到成功，成功获取了{$data['expense']}积分");
			$result['coin'] = $userinfo['coin'] + $data['expense'];
			$result['sign_score'] = $userinfo['sign_score'] + $data['expense'];
            echo json_encode($result);
        } else {
            echo '{"success":0,"msg":"暂时无法签到"}';
        }
    }
	//二维码
	public function tcode() {
		/* if($this->wecha_id!='oFD4ys_Kk-3LIsrtJSXh-qDQBIPA'){
			$this->error('功能还没开放！',U('Distribution/index',array('token'=>$this->token)));
		} */
		//个人信息
		$url=C(site_url).'/index.php?g=Wap&m=Product&a=index&token='.$this->token.'&store_id='.$this->store_id;
		$url=urlencode($url);
		echo '<img src="'.U('Product/qrcode', array('token'=>$this->token,'store_id'=>$this->store_id,'str'=>$url)).'" width="100%"/><br/>';
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
	//=======私有方法========
	private function setOrdersCount(){
		$map = array("token"=>$this->token, "wecha_id"=>$this->wecha_id,"showstatus"=>1);
		$ordersCount=M('product_cart')->where($map)->group("ostatus")->getField("ostatus, count(1)");
		$ordersCount[0]=array_sum($ordersCount);//统计总数 array_sum()
		//p($orderCount);
		$this->assign('ordersCount',$ordersCount);
	}
	private function peopleInfo(){
		$userinfo_model=M('Userinfo');
		$thisUser=$userinfo_model->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
		$this->assign('thisUser',$thisUser);
	}
	//今天签到
    private function _todaySigned()
    {
        //$signined            = 0;
        $now                 = time();
        $distribution_sign_db = M('distribution_sign');
        $where               = array(
            'token' => $this->token,
            'wecha_id' => $this->wecha_id,
            'score_type' => 1
        );
		//最新一条签到记录
        $signined                = $distribution_sign_db->where($where)->order('sign_time desc')->find();
        $today               = date('Y-m-d', $now);
        $itoday              = date('Y-m-d', intval($signined['sign_time']));
		//查到签到记录并且签到时间=现在时间（年月日），标志为已经签到
        if ($signined && $itoday == $today) {
            $signined = 1;
        }
        return $signined;
    }
	//是否连续签到
	//public function continuousSigned()
	private function _continuousSigned($sign)
    {
        $continuous            = 0;
        //$now                 = time();
		$yesterday           = date("Y-m-d",strtotime("-1 day"));
        /**$distribution_sign_db = M('distribution_sign');
        $where               = array(
            'token' => $this->token,
            'wecha_id' => $this->wecha_id,
            'score_type' => 1
        );*/
		//最新一条签到记录
        //$sign                = $distribution_sign_db->where($where)->order('sign_time desc')->find();dump($sign);
        //$today               = date('Y-m-d', time());
        $itoday              = date('Y-m-d', intval($sign['sign_time']));
		//echo $yesterday.'  '.$itoday;
		//查到签到记录并且签到时间=现在时间（年月日），标志为已经签到
        if ($sign && $itoday == $yesterday) {
            $continuous = 1;
        }
        return $continuous;
		//echo $continuous;
    }
    //微信回复 下面方法不成功，待解决
    private function wechaResponse(){
    	$access_token = getAccessToken($this->token);
		if($access_token["status"]){
			$data='{"touser":"'.$this->wecha_id.'","msgtype":"text","text":{"content":"'.$content.'"}}';
			$url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token['info'];
			$this->api_notice_increment($url,$data);
		}else{
			Log::write("Wap/ProductAction.orderCart - getAccessToken:".$access_token["info"]);
	    }
    }
	//支付成功之后调用此方法。自动审核成为对应等级的微店主
	private function autoStore($openid,$token){
	$this->wecha=$openid;
	$this->token=$token;
	
	//查询在关注表中是否有此用户的信息	
	$peopleInfo=M('wxuser_people')->where($where)->field('nickname,headimgurl')->find();
	//查出该openid的微店主的信息
	$this->store_model=M('Store');  //微店铺表
	$where['token']=$this->token;
	$where['wecha_id']=$this->wecha_id;
	$storeInfo=$this->store_model->where($where)->find();
	if($storeInfo['status']==1){
		//('你已经是微店主')
		exit;
	}
	if($storeInfo['status']==3){
		//('微店主申请审核中')
		exit;
	}
	//统计个人消费金额
	$product_cart_model=M('product_cart');
	$map['token']=$this->token;
	$map['wecha_id']=$this->wecha;
	$map['ostatus']=array('in','2,3,4');//订单（待发货，待收货，交易完成）消费总额
	$pcInfo=$product_cart_model->where($map)->field('truename,tel')->select();
	$pcSum=$product_cart_model->where($map)->field('price')->sum('binary price');
	//支付成功代表肯定是有一条订单数据的，所以$pcSum不是空的
	if(empty($pcSum)){
		exit('订单有错误，价格不能是空');
	}else{
		//echo '这里执行自动审核操作。';
		//读取代理资格配置表
		$setInfo=M('distribution_agent')->where(array('token'=>$this->token))->find();
		if(empty($setInfo)){$this->error("商家未设置代理资格，无法自动审核成为店主，请稍后再试");exit;}
		$store=M('store');
		$data['id'] =  $storeInfo['id'];//店铺ID
		$data['username'] =  $pcInfo['truename'];
		$data['telphone'] =  $pcInfo['tel'];				
		$data['applytime'] = time();//申请时间
		$data['status'] = 3;   //状态由未申请（0）变成审核中（3）  0未申请 3审核中 1申请通过 2申请拒绝
		if($pcSum>=$setInfo['level3']){
			//钻眼代理 vip=3
			$data['vip'] = 3;    
			$data['status'] = 1;
			$data['applyhandled'] = time();//处理申请的时间
			
		}elseif($pcSum>=$setInfo['level2']){
			//金眼代理 vip=2
			$data['vip'] = 2;    
			$data['status'] = 1;
			$data['applyhandled'] = time();//处理申请的时间
			
		}elseif($pcSum>=$setInfo['level1']){
			//银眼代理 vip=1
			$data['vip'] = 1;    
			$data['status'] = 1;
			$data['applyhandled'] = time();//处理申请的时间
			
		}else{
			//不符合资格，交给后台审核
			$data['status'] = 3;	
		}	
		$result=$store->save($data);
		if($result){
			//如果userinfo表中还没填写个人信息，把申请的个人信息插入表中
			$userinfo_model=M('Userinfo');
			$thisUser=$userinfo_model->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
			if (!$thisUser){
				$userRow['token']=$this->token;
				$userRow['wecha_id']=$this->wecha_id;
				$userRow['truename'] =  $pcInfo['truename'];
				$userRow['telphone'] =  $pcInfo['tel'];
				$userinfo_model->add($userRow);
			}
		}
	}	
	}
	
}

?>