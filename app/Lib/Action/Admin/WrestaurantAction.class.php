<?php
//西餐厅控制器  修改控制器名字WrestaurantAction 和 catid=WRID
class WrestaurantAction extends BackAction{
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

		//菜谱信息
		$product=M('Product');
		$map['catid']=WRID;
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

		$p_info=M('Product')->getById($this->_get('id'));
		$p_info['intro']=str_replace(array('&lt;','&gt;','&quot;','&amp;nbsp;'),array('<','>','"',' '),$p_info['intro']);
		$intro=$this->remove_html_tag($p_info['intro']);
		$intro=mb_substr($intro,0,30,'utf-8');
		$intro=$intro.'......';
		$this->assign('p_info',$p_info);
		$this->assign('intro',$intro);
		$this->display();
	}

	//订单的信息
	function order(){
		//得到中餐厅的订单号
		$product_model=M('Product');
		$where['token']=TOKEN;
		$where['catid']=WRID;
		$product_info=$product_model->where($where)->getField('id',true);

		$cart_list_model=M('Product_cart_list');
		$map['token']=TOKEN;
		$map['productid']  = array('in',$product_info);
		$cart_list_info=$cart_list_model->where($map)->getField('cartid',true);

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

			$this->assign('thisOrder',$thisOrder);

			$carts=unserialize($thisOrder['info']);

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
				$map['id']  = array('in',$ids);
				$list=$this->product_model->where($map)->select();
				//同等于 $list=$this->product_model->where(array('id'=>array('in',$ids)))->select();



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

	}
	//删除订单
	function deleteOrder(){
		$product_model=M('product');
		$product_cart_model=M('product_cart');
		$product_cart_list_model=M('product_cart_list');
		$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();


		//检查权限
		$id=$thisOrder['id'];

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
		$this->success('操作成功',$_SERVER['HTTP_REFERER']);
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

		//得到中餐厅的订单号
		$product_model=M('Product');
		$where['token']=TOKEN;
		$where['catid']=WRID;
		$product_info=$product_model->where($where)->getField('id',true);

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
		$where['catid']=WRID;
		$product_info=$product_model->where($where)->getField('id',true);

		$cart_list_model=M('Product_cart_list');
		$map['token']=TOKEN;
		$map['productid']  = array('in',$product_info);
		$cart_list_info=$cart_list_model->where($map)->getField('cartid',true);

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

	//会计部看到的订单列表页面
	public function verification(){
		$pid=$_GET['pid'];
		$level=$_GET['level'];
		//得到中餐厅的订单号
		$product_model=M('Product');
		$where['token']=TOKEN;
		$where['catid']=WRID;
		$product_info=$product_model->where($where)->getField('id',true);

		$cart_list_model=M('Product_cart_list');
		$map['token']=TOKEN;
		$map['productid']  = array('in',$product_info);
		$cart_list_info=$cart_list_model->where($map)->getField('cartid',true);

		//属于中餐厅的订单详情
		$cart_model=M('Product_cart');
		$condition['token']=TOKEN;
		$condition['id']  = array('in',$cart_list_info);
		$condition['paid']  =1;//已经付款

		$cnt=$cart_model->where($condition)->count();
		$Page       = new Page($cnt,5);// 实例化分页类 传入总记录数
		// 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show       = $Page->show();// 分页显示输出
		$cart_info = $cart_model->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();

		$this->assign('cart_info',$cart_info);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('pid',$pid);
		$this->assign('level',$level);
		$this->display();
	}

	//excel表格导出全部订单的数据
	public function orderExcel(){

		$name=date("Y-m-d H:i:s", time()) ;
		header('Content-Type: text/html; charset=utf-8');
        header('Content-type:application/vnd.ms-execl');
        header("Content-Disposition:filename=$name.xls");
        $letterArr = explode(',', strtoupper('a,b,c,d,e,f'));
        $arr = array(array('en' => 'orderid', 'cn' => '订单号'), array('en' => 'truename', 'cn' => '姓名'), array('en' => 'tel', 'cn' => '电话'), array('en' => 'total', 'cn' => '数量'), array('en' => 'price', 'cn' => '总价（元）'), array('en' => 'time', 'cn' => '创建时间'));

        $i = 0;
        $fieldCount = count($arr);
        $s = 0;
        foreach ($arr as $f) {
            if ($s < $fieldCount - 1) {
                echo iconv('utf-8', 'gbk', $f['cn']) . '	';
            } else {
                echo iconv('utf-8', 'gbk', $f['cn']) . '
';
            }
            $s++;
        }

		//================================================================================
		//数据信息
		//得到中餐厅的订单号
		$product_model=M('Product');
		$where['token']=TOKEN;
		$where['catid']=WRID;
		$product_info=$product_model->where($where)->getField('id',true);

		$cart_list_model=M('Product_cart_list');
		$map['token']=TOKEN;
		$map['productid']  = array('in',$product_info);
		$cart_list_info=$cart_list_model->where($map)->getField('cartid',true);

		//属于中餐厅的订单详情
		$cart_model=M('Product_cart');
		$condition['token']=TOKEN;
		$condition['id']  = array('in',$cart_list_info);
		$condition['paid']  =1;//已经付款
		$sns = $cart_model->where($condition)->order('id desc')->select();

        //================================================================================
        if ($sns) {
            if ($sns[0]['token'] != TOKEN) {
                die('no permission');
            }
            foreach ($sns as $sn) {
                $j = 0;
                foreach ($arr as $field) {
                    $fieldValue = $sn[$field['en']];
	                    switch ($field['en']) {
	                    default:
	                        break;

	                    case 'time':
	                        if ($fieldValue) {
	                            $fieldValue = date('Y-m-d H:i:s', $fieldValue);
	                        } else {
	                            $fieldValue = '';
	                        }
	                        break;

	                    case 'truename':
	                        $fieldValue = iconv('utf-8', 'gbk', $fieldValue);
	                        break;
	                    }
                    if ($j < $fieldCount - 1) {
                        echo $fieldValue . '	';
                    } else {
                        echo $fieldValue . '
';
                    }
                    $j++;
                }
                $i++;
            }
        }
        die;
	}


	//核销---按时间段查看
	public function verificationTime(){
		$pid=$_GET['pid'];
		$level=$_GET['level'];

		//得到中餐厅的订单号
		$product_model=M('Product');
		$where['token']=TOKEN;
		$where['catid']=WRID;
		$product_info=$product_model->where($where)->getField('id',true);

		$cart_list_model=M('Product_cart_list');
		$map['token']=TOKEN;
		$map['productid']  = array('in',$product_info);
		$cart_list_info=$cart_list_model->where($map)->getField('cartid',true);

		//属于中餐厅的订单详情
		$cart_model=M('Product_cart');
		//获取查询的时间
		$statdate=$_GET['statdate'];
		$enddate=$_GET['enddate'];
		$stat=strtotime($statdate);
		$end=strtotime($enddate);
		$condition['token']=TOKEN;
		$condition['id']  = array('in',$cart_list_info);
		$condition['paid']  =1;//已经付款
		$condition['time'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间
		$cnt=$cart_model->where($condition)->count();
		$Page       = new Page($cnt,5);// 实例化分页类 传入总记录数
		// 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show       = $Page->show();// 分页显示输出
		$cart_info = $cart_model->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();

		$this->assign('cart_info',$cart_info);
		$this->assign('page',$show);// 赋值分页输出

		$this->assign('statdate',$statdate);
		$this->assign('enddate',$enddate);
		$this->assign('pid',$pid);
		$this->assign('level',$level);
		$this->assign('cnt',$cnt);

		$this->display();
	}
	//导出按照时间段查询出来的数据
	//导出订单的excel表格
	public function orderExceltime(){
		$name=date("Y-m-d H:i:s", time()) ;
		header('Content-Type: text/html; charset=utf-8');
        header('Content-type:application/vnd.ms-execl');
        header("Content-Disposition:filename=$name.xls");


        $letterArr = explode(',', strtoupper('a,b,c,d,e,f'));
        $arr = array(array('en' => 'orderid', 'cn' => '订单号'), array('en' => 'truename', 'cn' => '姓名'), array('en' => 'tel', 'cn' => '电话'), array('en' => 'total', 'cn' => '数量'), array('en' => 'price', 'cn' => '总价（元）'), array('en' => 'time', 'cn' => '创建时间'));

        $i = 0;
        $fieldCount = count($arr);
        $s = 0;
        foreach ($arr as $f) {
            if ($s < $fieldCount - 1) {
                echo iconv('utf-8', 'gbk', $f['cn']) . '	';
            } else {
                echo iconv('utf-8', 'gbk', $f['cn']) . '
';
            }
            $s++;
        }

		//================================================================================
		//数据信息
		//得到中餐厅的订单号
		$product_model=M('Product');
		$where['token']=TOKEN;
		$where['catid']=WRID;
		$product_info=$product_model->where($where)->getField('id',true);

		$cart_list_model=M('Product_cart_list');
		$map['token']=TOKEN;
		$map['productid']  = array('in',$product_info);
		$cart_list_info=$cart_list_model->where($map)->getField('cartid',true);

		//属于中餐厅的订单详情
		$cart_model=M('Product_cart');

		$statdate=$_GET['sdate'];
		$enddate=$_GET['edate'];
		$stat=strtotime($statdate);
		$end=strtotime($enddate);
		$condition['token']=TOKEN;
		$condition['id']  = array('in',$cart_list_info);
		$condition['paid']  =1;//已经付款
		$condition['time'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间

		$sns = $cart_model->where($condition)->order('id desc')->select();

        //================================================================================
        if ($sns) {
            if ($sns[0]['token'] != TOKEN) {
                die('no permission');
            }
            foreach ($sns as $sn) {
                $j = 0;
                foreach ($arr as $field) {
                    $fieldValue = $sn[$field['en']];
	                    switch ($field['en']) {
	                    default:
	                        break;

	                    case 'time':
	                        if ($fieldValue) {
	                            $fieldValue = date('Y-m-d H:i:s', $fieldValue);
	                        } else {
	                            $fieldValue = '';
	                        }
	                        break;

	                    case 'truename':
	                        $fieldValue = iconv('utf-8', 'gbk', $fieldValue);
	                        break;
	                    }
                    if ($j < $fieldCount - 1) {
                        echo $fieldValue . '	';
                    } else {
                        echo $fieldValue . '
';
                    }
                    $j++;
                }
                $i++;
            }
        }
        die;
	}
}