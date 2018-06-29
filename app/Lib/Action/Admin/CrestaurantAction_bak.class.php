<?php
//中餐厅控制器
class CrestaurantAction extends BackAction{
	public function _initialize() {
        parent::_initialize();  //RBAC 验证接口初始化
    }

	public function index(){

		//确定用户所属分组
		$condition['user_id']=($_SESSION['userid']);
    	$r_info=M()->table("tp_role as a")->join("INNER JOIN tp_role_user as b on a.id=b.role_id")->where($condition)->getField('a.name');
    	$this->assign('r_info',$r_info);

        $this->display();
    }
	//中餐信息
    public function showlist(){

		//中餐厅ID号是：11,这个公众号的token是fatqrj1401869433
		//菜谱信息
		$product=M('Product');
		$map['catid']=CRID;
		$map['token']=TOKEN;
		$p_cnt=$product->where($map)->count();
		$Page       = new Page($p_cnt,5);// 实例化分页类 传入总记录数
		// 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show       = $Page->show();// 分页显示输出
		$p_info=$product->where($map)->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('p_info',$p_info);
		$this->assign('page',$show);// 赋值分页输出
		$this->display();
    }
	//中餐信息-查看操作(菜的详细信息)
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

	//菜的详细信息
	public function cai(){
		//$id = $this->_get('id');
		//$map['id']=$this->_get('id');
		$p_info=M('Product')->getById($this->_get('id'));

		//$p_info['intro']=str_replace(array('&lt;','&gt;','&quot;','&amp;nbsp;'),array('<','>','"',' '),$p_info['intro']);
		//p($p_info['intro']);

		$p_info['intro']=str_replace(array('&lt;','&gt;','&quot;','&amp;nbsp;'),array('<','>','"',' '),$p_info['intro']);
		$intro=$this->remove_html_tag($p_info['intro']);
		$intro=mb_substr($intro,0,30,'utf-8');
		$intro=$intro.'......';
		$this->assign('p_info',$p_info);
		$this->assign('intro',$intro);
		$this->display();
	}
	//订单的信息
	function order1111(){
		$product_cart_model=M('Product_cart');
		//$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		//$carts=unserialize($thisOrder['info']);



		$where['dining']=1;
		$where['token']=TOKEN;
		$cnt=$product_cart_model->where($where)->count();
		$Page       = new Page($cnt,5);// 实例化分页类 传入总记录数
		// 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show       = $Page->show();// 分页显示输出
		$orders = $product_cart_model->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$this->assign('orders',$orders);
		$this->assign('page',$show);// 赋值分页输出
		$this->display();

	}

	function order222(){
		$cart_list_model=M('Product_cart_list');
		$where['token']=TOKEN;
		$where['productid']=31;
		$cart_list_info=$cart_list_model->where($where)->getField('cartid',true);
		p($cart_list_info);
		//exit;
		$cart_model=M('Product_cart');
		$map['id']  = array('in',$cart_list_info);
		$cart_info=$cart_model->where($map)->select();
		//p($cart_info);
		$this->assign('cart_info',$cart_info);
		$this->display();

	}
	function order(){
		//得到中餐厅的订单号
		$product_model=M('Product');
		$where['token']=TOKEN;
		$where['catid']=CRID;
		$product_info=$product_model->where($where)->getField('id',true);
		//p($product_info);

		$cart_list_model=M('Product_cart_list');
		$map['token']=TOKEN;
		$map['productid']  = array('in',$product_info);
		$cart_list_info=$cart_list_model->where($map)->getField('cartid',true);
		//p($cart_list_info);

		//属于中餐厅的订单详情
		$cart_model=M('Product_cart');
		$condition['token']=TOKEN;
		$condition['id']  = array('in',$cart_list_info);

		$cnt=$cart_model->where($condition)->count();
		$Page       = new Page($cnt,5);// 实例化分页类 传入总记录数
		// 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show       = $Page->show();// 分页显示输出
		$cart_info = $cart_model->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();

		//$cart_info=$cart_model->where($condition)->select();
		//p($cart_info);

		$this->assign('cart_info',$cart_info);
		$this->assign('page',$show);// 赋值分页输出
		$this->display();

	}

	//订单详情
	function orderInfo(){
		$this->product_model=M('Product');
		$this->product_cat_model=M('Product_cat');
		$product_cart_model=M('product_cart');
		$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		if (IS_POST){
			//如果sent值不是0或者空，那么这张订单的状态是已处理。
			if (intval($_POST['sent'])){
				$_POST['handled']=1;
			}
			$product_cart_model->where(array('id'=>$thisOrder['id']))->save(array('sent'=>intval($_POST['sent']),'handled'=>1));

			$this->success('修改成功',U('Crestaurant/order',array('id'=>$thisOrder['id'])));

		}
		else {
			//订餐信息
			$product_diningtable_model=M('product_diningtable');
			if ($thisOrder['tableid']) {
				$thisTable=$product_diningtable_model->where(array('id'=>$thisOrder['tableid']))->find();
				$thisOrder['tableName']=$thisTable['name'];
			}
			//
			$this->assign('thisOrder',$thisOrder);
			//p($thisOrder);
			$carts=unserialize($thisOrder['info']);
			//p($carts);
			//$this->assign('carts',$carts);
			//$this->display();
			//exit;

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
			//p( $k);
			//exit;
			//p($ids);//$ids是订单商品的id.
			//p(count($ids));
			//exit;
			if (count($ids)){
				$map['id']  = array('in',$ids);
				//$map['id']  = CRID; //只显示属于自己餐厅的信息
				//echo CRID;
				//p($ids);
				//$sid=implode('-',$ids);
				//p($sid);
				//$this->product_model=M('Product');
				$list=$this->product_model->where($map)->select();
				//$list=$this->product_model->where(array('id'=>array('in',$ids)))->select();



			}
			if ($list){
				$i=0;
				foreach ($list as $p){
					$list[$i]['count']=$carts[$p['id']]['count'];
					$i++;
				}
			}
			$this->assign('products',$list);

			$this->assign('totalFee',$totalFee);
			$this->display();
		}

		//$this->assign('thisOrder',$thisOrder);
		//$this->display();
	}
	//删除订单
	function deleteOrder(){
		echo 'delete order';
	}
	//订单搜索
	function orderSearch(){

		$schkey=$_POST;
		if(empty($_POST)){
			$p=0;
		}
		else{
			$p=1;
		}
		$key=$_POST['searchkey'];
		//p($schkey);
		//p($key);
		//得到中餐厅的订单号
		$product_model=M('Product');
		$where['token']=TOKEN;
		$where['catid']=CRID;
		$product_info=$product_model->where($where)->getField('id',true);
		//p($product_info);

		$cart_list_model=M('Product_cart_list');
		$map['token']=TOKEN;
		$map['productid']  = array('in',$product_info);
		$cart_list_info=$cart_list_model->where($map)->getField('cartid',true);
		//p($cart_list_info);

		//属于中餐厅的订单详情
		$cart_model=M('Product_cart');
		$condition['token']=TOKEN;
		$condition['dining']=1;
		$condition['orderid']=$key;
		$condition['id']  = array('in',$cart_list_info);

		$cnt=$cart_model->where($condition)->count();
		$Page       = new Page($cnt,5);// 实例化分页类 传入总记录数
		// 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show       = $Page->show();// 分页显示输出
		//$cart_info=$cart_model->where($condition)->select();
		$orders = $cart_model->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$this->assign('orders',$orders);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('cnt',$cnt);
		$this->assign('key',$key);
		$this->assign('p',$p);
		$this->display();
	}

	//订单搜索
	function orderSearch111(){
		$product_cart_model=M('product_cart');
		$schkey=$_POST;
		if(empty($_POST)){
			$p=0;
		}
		else{
			$p=1;
		}
		$key=$_POST['searchkey'];
		//p($schkey);
		//p($key);
		$where['dining']=1;
		$where['token']=TOKEN;
		$where['orderid']=$key;


		$cnt=$product_cart_model->where($where)->count();

		$Page       = new Page($cnt,5);// 实例化分页类 传入总记录数
		// 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show       = $Page->show();// 分页显示输出
		$orders = $product_cart_model->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$this->assign('orders',$orders);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('cnt',$cnt);
		$this->assign('key',$key);
		$this->assign('p',$p);
		$this->display();
	}
	//按照时间段查询订单
	public function timeOrder(){
		$product_cart_model=M('product_cart');


		$pid=$_GET['pid'];
		$level=$_GET['level'];

		$statdate=$_GET['statdate'];
		$enddate=$_GET['enddate'];
		$stat=strtotime($statdate);
		$end=strtotime($enddate);

		if(empty($_GET['statdate'])){
			$gg=0;
		}
		else{
			$gg=1;
		}


		//得到中餐厅的订单号
		$product_model=M('Product');
		$where['token']=TOKEN;
		$where['catid']=CRID;
		$product_info=$product_model->where($where)->getField('id',true);
		//p($product_info);

		$cart_list_model=M('Product_cart_list');
		$map['token']=TOKEN;
		$map['productid']  = array('in',$product_info);
		$cart_list_info=$cart_list_model->where($map)->getField('cartid',true);
		//p($cart_list_info);

		//属于中餐厅的订单详情
		$cart_model=M('Product_cart');
		$condition['token']=TOKEN;
		$condition['dining']=1;
		$condition['time'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间
		$condition['id']  = array('in',$cart_list_info);


		$cnt=$product_cart_model->where($condition)->count();

		$Page       = new Page($cnt,5);// 实例化分页类 传入总记录数
		// 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show       = $Page->show();// 分页显示输出
		$orders = $product_cart_model->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$this->assign('orders',$orders);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('cnt',$cnt);

		$this->assign('statdate',$statdate);
		$this->assign('enddate',$enddate);
		$this->assign('pid',$pid);
		$this->assign('level',$level);
		$this->assign('gg',$gg);
		$this->display();
	}
	//按照时间段查询订单
	public function timeOrder222(){
		$days=7;
		$time=time();
		$secondsOfDay=24*60*60;
		$dateTimes=array();
		for ($i=0;$i<$days;$i++){
			array_push($dateTimes,$time+$i*$secondsOfDay);
		}
		$this->assign('dateTimes',$dateTimes);
		$hours=array();
		for ($i=0;$i<24;$i++){
			array_push($hours,$i);
		}
		$this->assign('hours',$hours);
		p($_POST);
		$this->display();
	}
    //确定用户所属分组
    public function tt(){
    	  $condition['user_id']=($_SESSION['userid']);
    	  $r_info=M()->table("tp_role as a")->join("INNER JOIN tp_role_user as b on a.id=b.role_id")->where($condition)->getField('a.name');
		  p($r_info);
    }
    public function mm(){
    	//图片加上域名
    	$p_info=M('Product')->getById($this->_get('id'));
    	$p_info['intro']=str_replace(array('&lt;','&gt;','&quot;','&amp;nbsp;'),array('<','>','"',' '),$p_info['intro']);
    	//$p_info['intro']=preg_replace("/(<img.*?src=\")\s*(\/upload\/)/U","\\1http://www.zisai2.com\\2",$p_info['intro']);

    	$str=$p_info['intro'];

    	//preg_match('/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i',$str,$match);
    	//preg_match('/<img.+src=\"?(.+\.(jpg|gif|bmp|bnp|png))\"?.+>/i',$str,$match);
    	//p($match);
    	exit;

    	p($p_info['intro']);
    }
    public function dd(){
    	//$a=TT;
    	//echo $a;
    	$product=M('Product');
		$map['catid']=CRID;
		$map['token']=TOKEN;
    	$p_info=$product->where($map)->select();
    	p($p_info);
    }
    public function ii(){
    	$a=0;
    	//echo $a;
    	if (intval($a))
    	{
    		echo '000';
    	}
    	else
    	{
    		echo '1111';
    	}
    }

	public function  myorder(){
		//token ewerun1402039733   tp_product_cart  tp_wxuser_people
		$condition['token']='ewerun1402039733';
		$myorder_info=M()->table("tp_product_cart as a")->join("INNER JOIN tp_wxuser_people as b on a.wecha_id=b.role_wecha_id")->where($condition)->select();
		$this->assign('myorder',$myorder_info);
		$this->display();
	}
	public function ss(){
		$name=$_SESSION['username'];
		echo $name;
		echo '<br />';

		$token=$_SESSION['token'];
		echo $token;
		echo '<br />';
	}
	public function timejq(){
		$this->Yuyue_model=M('yuyue');
		$this->type="Jiudian";
		$checkdata = $this->Yuyue_model->where(array('token'=>TOKEN,'type'=>$this->type))->find();
		p($_POST);
		$statdate=$_POST['statdate'];
		$enddate=$_POST['enddate'];
		//$id = $this->Yuyue_model->add($_POST);
		//p($id);
		$this->display();
	}
	public function info(){

		$cart_info=M('Product_cart')->select();

		p($cart_info);

	}


}