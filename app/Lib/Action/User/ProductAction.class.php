<?php
class ProductAction extends UserAction{
	public $token;
	public $product_model;
	public $product_cat_model;
	public $isDining;
	public function _initialize() {
		parent::_initialize();		
		//flag表示 订单账号（order uid=114） 从微信公众号功能管理进入 重写session token
		if($_GET['flag']==1){
			session('token', $_GET['token']);
		}
		$this->token= session('token');
		$this->assign('token',$this->token);
		$token_open=M('token_open')->field('queryname')->where(array('token'=>session('token')))->find();
		if((!isset($_GET['dining'])&&!strpos($token_open['queryname'],'shop'))||(isset($_GET['dining'])&&!strpos($token_open['queryname'],'dx'))){
           // $this->error('您还未开启该模块的使用权,请到功能模块中添加',U('Function/index',array('token'=>session('token'),'id'=>session('wxid'))));
		}
		//是否是餐饮
		if (isset($_GET['dining'])&&intval($_GET['dining'])){
			$this->isDining=1;
		}else {
			$this->isDining=0;
		}
		$this->assign('isDining',$this->isDining);
	}
	public function index(){		
		$catid=intval($_GET['catid']);
		$catid=$catid==''?0:$catid;
		$product_model=M('Product');
		$product_cat_model=M('Product_cat');
		$where=array('token'=>session('token'));
		if ($catid){
			$where['catid']=$catid;
		}
		$where['dining']=$this->isDining;
		$where['groupon']=array('neq',1);
        if(IS_POST){
            $key = $this->_post('searchkey');
            if(empty($key)){
                $this->error("关键词不能为空");
            }

            $map['token'] = $this->get('token'); 
            $map['name|intro|keyword'] = array('like',"%$key%"); 
            $list = $product_model->where($map)->select(); 
            $count      = $product_model->where($map)->count();       
            $Page       = new Page($count,20);
        	$show       = $Page->show();
        }else{
        	$count      = $product_model->where($where)->count();
        	$Page       = new Page($count,20);
        	$show       = $Page->show();
        	$list = $product_model->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        }
		$this->assign('page',$show);		
		$this->assign('list',$list);
		$this->assign('isProductPage',1);
		
		$this->display();		
	}
	public function cats(){		
		/*
		$token_open=M('token_open')->field('queryname')->where(array('token'=>session('token')))->find();

		if(!strpos($token_open['queryname'],'adma')){
            $this->error('您还未开启该模块的使用权,请到功能模块中添加',U('Function/index',array('token'=>session('token'),'id'=>session('wxid'))));}
		 */
		$parentid=intval($_GET['parentid']);
		$parentid=$parentid==''?0:$parentid;
		$data=M('Product_cat');
		$where=array('parentid'=>$parentid,'token'=>session('token'));
		$where['dining']=$this->isDining;
        if(IS_POST){
            $key = $this->_post('searchkey');
            if(empty($key)){
                $this->error("关键词不能为空");
            }

            $map['token'] = $this->get('token'); 
            $map['name|des'] = array('like',"%$key%"); 
            $list = $data->where($map)->select(); 
            $count      = $data->where($map)->count();       
            $Page       = new Page($count,20);
        	$show       = $Page->show();
        }else{
        	$count      = $data->where($where)->count();
        	$Page       = new Page($count,20);
        	$show       = $Page->show();
        	$list = $data->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
        }
		$this->assign('page',$show);		
		$this->assign('list',$list);
		if ($parentid){
			$parentCat = $data->where(array('id'=>$parentid))->find();
		}
		$this->assign('parentCat',$parentCat);
		$this->assign('parentid',$parentid);
		$this->display();		
	}
	public function catAdd(){ 
		if(IS_POST){
			if ($this->isDining){
				$this->insert('Product_cat','/cats?dining=1&parentid='.$this->_post('parentid'));
			}else {
			$this->insert('Product_cat','/cats?parentid='.$this->_post('parentid'));
			}
		}else{
			$parentid=intval($_GET['parentid']);
			$parentid=$parentid==''?0:$parentid;
			$this->assign('parentid',$parentid);
			$this->display('catSet');
		}
	}
	public function catDel(){
		if($this->_get('token')!=session('token')){$this->error('非法操作');}
        $id = $this->_get('id');
        if(IS_GET){                              
            $where=array('id'=>$id,'token'=>session('token'));
            $data=M('Product_cat');
            $check=$data->where($where)->find();
            if($check==false)   $this->error('非法操作');
            $product_model=M('Product');
            $productsOfCat=$product_model->where(array('catid'=>$id))->select;
            if (count($productsOfCat)){
            	$this->error('本分类下有商品，请删除商品后再删除分类',U('Product/cats',array('token'=>session('token'),'dining'=>$this->isDining)));
            }
            $back=$data->where($wehre)->delete();
            if($back==true){
            	if (!$this->isDining){
                $this->success('操作成功',U('Product/cats',array('token'=>session('token'),'parentid'=>$check['parentid'])));
            	}else {
            		$this->success('操作成功',U('Product/cats',array('token'=>session('token'),'parentid'=>$check['parentid'],'dining'=>1)));
            	}
            }else{
                 $this->error('服务器繁忙,请稍后再试',U('Product/cats',array('token'=>session('token'))));
            }
        }        
	}
	public function catSet(){
        $id = $this->_get('id'); 
		$checkdata = M('Product_cat')->where(array('id'=>$id))->find();
		if(empty($checkdata)){
            $this->error("没有相应记录.您现在可以添加.",U('Product/catAdd'));
        }
		if(IS_POST){ 
            $data=D('Product_cat');
            $where=array('id'=>$this->_post('id'),'token'=>session('token'));
			$check=$data->where($where)->find();
			if($check==false)$this->error('非法操作');
			if($data->create()){
				if($data->where($where)->save($_POST)){
					if (!$this->isDining){
						$this->success('修改成功',U('Product/cats',array('token'=>session('token'),'parentid'=>$this->_post('parentid'))));
					}else {
						$this->success('修改成功',U('Product/cats',array('token'=>session('token'),'parentid'=>$this->_post('parentid'),'dining'=>1)));
					}
					
				}else{
					$this->error('操作失败');
				}
			}else{
				$this->error($data->getError());
			}
		}else{ 
			$this->assign('parentid',$checkdata['parentid']);
			$this->assign('set',$checkdata);
			$this->display();	
		
		}
	}
	public function add(){
		$setInfo=M('distribution_set')->where(array('token'=>session('token')))->find();
		if($setInfo==false){$this->error('请先设置相关参数',U('Distribution/set',array('token'=>session('token'))));}	
		//设置物流
		$logInfo=M('Logistics')->where(array('token'=>session('token')))->find();
		if($logInfo==false){$this->error('请先设置物流',U('Product/logistics',array('token'=>session('token'))));}	
		
		if(IS_POST){
			$this->all_insert('Product','/index?token='.session('token').'&dining='.$this->isDining);
		}else{
			//分类
			$data=M('Product_cat');
			$catWhere=array('parentid'=>0,'token'=>session('token'));
			if ($this->isDining){
				$catWhere['dining']=1;
			}else {
				$catWhere['dining']=0;
			}
			$cats=$data->where($catWhere)->select();
			if (!$cats){
				 $this->error("请先添加分类",U('Product/catAdd',array('token'=>session('token'),'dining'=>$this->isDining)));
				 exit();
			}
			$this->assign('cats',$cats);
			$catsOptions=$this->catOptions($cats,0);
			$this->assign('catsOptions',$catsOptions);
			//
			$this->assign('isProductPage',1);
			$this->display('set');
		}
	}
	/**
	 * 商品类别ajax select
	 *
	 */
	public function ajaxCatOptions(){
		$parentid=intval($_GET['parentid']);
		$data=M('Product_cat');
		$catWhere=array('parentid'=>$parentid,'token'=>session('token'));
		$cats=$data->where($catWhere)->select();
		$str='';
		if ($cats){
			foreach ($cats as $c){
				$str.='<option value="'.$c['id'].'">'.$c['name'].'</option>';
			}
		}
		$this->show($str);
	}
	public function set(){
        $id = $this->_get('id'); 
        $product_model=D('Product');
        $product_cat_model=D('Product_cat');
		$checkdata = $product_model->where(array('id'=>$id))->find();
		if(empty($checkdata)){
            $this->error("没有相应记录.您现在可以添加.",U('Product/add'));
        }
		if(IS_POST){ 
            $where=array('id'=>$this->_post('id'),'token'=>session('token'));
			$check=$product_model->where($where)->find();
			if($check==false)$this->error('非法操作');
			if($product_model->create()){
				if($product_model->where($where)->save($_POST)){
					$this->success('修改成功',U('Product/index',array('token'=>session('token'),'dining'=>$this->isDining)));
					$keyword_model=M('Keyword');
					$keyword_model->where(array('token'=>session('token'),'pid'=>$this->_post('id'),'module'=>'Product'))->save(array('keyword'=>$this->_post('keyword')));
				}else{
					$this->error('操作失败');
				}
			}else{
				$this->error($product_model->getError());
			}
		}else{
			//分类
			$catWhere=array('parentid'=>0,'token'=>session('token'));
			if ($this->isDining){
				$catWhere['dining']=1;
			}
			$cats=$product_cat_model->where($catWhere)->select();
			$this->assign('cats',$cats);
			
			$thisCat=$product_cat_model->where(array('id'=>$checkdata['catid']))->find();
			$childCats=$product_cat_model->where(array('parentid'=>$thisCat['parentid']))->select();
			$this->assign('thisCat',$thisCat);
			$this->assign('parentCatid',$thisCat['parentid']);
			$this->assign('childCats',$childCats);
			$this->assign('isUpdate',1);
			$catsOptions=$this->catOptions($cats,$checkdata['catid']);
			$childCatsOptions=$this->catOptions($childCats,$thisCat['id']);
			$this->assign('catsOptions',$catsOptions);
			$this->assign('childCatsOptions',$childCatsOptions);
			//
			$this->assign('set',$checkdata);
			$this->assign('isProductPage',1);
			$this->display();	
		
		}
	}
	//商品类别下拉列表
	public function catOptions($cats,$selectedid){
		$str='';
		if ($cats){
			foreach ($cats as $c){
				$selected='';
				if ($c['id']==$selectedid){
					$selected=' selected';
				}
				$str.='<option value="'.$c['id'].'"'.$selected.'>'.$c['name'].'</option>';
			}
		}
		return $str;
	}
	public function del(){
		$product_model=M('Product');
		if($this->_get('token')!=session('token')){$this->error('非法操作');}
        $id = $this->_get('id');
        if(IS_GET){                              
            $where=array('id'=>$id,'token'=>session('token'));
            $check=$product_model->where($where)->find();
            if($check==false)   $this->error('非法操作');

            $back=$product_model->where($wehre)->delete();
            if($back==true){
            	$keyword_model=M('Keyword');
            	$keyword_model->where(array('token'=>session('token'),'pid'=>$id,'module'=>'Product'))->delete();
                $this->success('操作成功',U('Product/index',array('token'=>session('token'),'dining'=>$this->isDining)));
            }else{
                 $this->error('服务器繁忙,请稍后再试',U('Product/index',array('token'=>session('token'))));
            }
        }        
	}
	//post方式提交查询
	public function ordersOld(){
		$product_cart_model=M('product_cart');
		if ($_GET['token']!=$this->_session('token')){
			exit();
		}
		$where=array('token'=>$this->_session('token'));
		$where['groupon']=array('neq',1);
		if(IS_POST){
			$key = $this->_post('searchkey');
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			$where['truename|orderid'] = array('like',"%$key%");
			$orders = $product_cart_model->where($where)->select();
			$count      = $product_cart_model->where($where)->limit($Page->firstRow.','.$Page->listRows)->count();
			$Page       = new Page($count,20);
			$show       = $Page->show();
		}else {
			if (isset($_GET['handled'])){
				$where['handled']=intval($_GET['handled']);
			}
			if (isset($_GET['wecha_id'])){
				$where['wecha_id']=$_GET['wecha_id'];
			}
			$count      = $product_cart_model->where($where)->count();
			$Page       = new Page($count,20);
			$show       = $Page->show();
			$orders=$product_cart_model->where($where)->order('time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		}


		$unHandledCount=$product_cart_model->where(array('token'=>$this->_session('token'),'handled'=>0))->count();
		$this->assign('unhandledCount',$unHandledCount);
		$this->assign('orders',$orders);
		$this->assign('page',$show);
		$this->display();
	}
	//修改为get方式提交查询
	public function orders(){
		$product_cart_model=M('product_cart');
		if ($_GET['token']!=$this->_session('token')){
			exit();
		}
		$where=array('token'=>$this->_session('token'));
		$where['groupon']=array('neq',1);
		//$where['showstatus']=1; //是否显示  1显示 0 不显示（删除订单）
			if($_GET['searchkey']){
				//$key = $this->_get('searchkey');
				$key = $_GET['searchkey'];
				if(empty($key)){
					$this->error("关键词不能为空");
				}
				$where['truename|orderid'] = array('like',"%$key%");
				
			}
			if($_GET['statdate']&&$_GET['enddate']){
				$statdate=$_GET['statdate'];
				$enddate=$_GET['enddate'];
				$stat=strtotime($statdate);
				$end=strtotime($enddate);
				$where['time'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间	
			}
			if($_GET['stat']&&$_GET['end']){
				$statdate=$_GET['stat'];
				$enddate=$_GET['end'];
				$stat=strtotime($statdate);
				$end=strtotime($enddate);
				$where['paytime'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间	
			}
			if($_GET['ostatus']){
				if($_GET['ostatus']!=0){
					$where['ostatus'] = $_GET['ostatus'];
				}
			}
			//查看客户的订单
			if (isset($_GET['wecha_id'])){
				$where['wecha_id']=$_GET['wecha_id'];
			}
			/*
			//后面两个if的意思是？
			if (isset($_GET['handled'])){
				$where['handled']=intval($_GET['handled']);
			}
			if (isset($_GET['wecha_id'])){
				$where['wecha_id']=$_GET['wecha_id'];
			}
			*/
			$count      = $product_cart_model->where($where)->count();
			$Page       = new Page($count,20);
			$show       = $Page->show();
			$orders=$product_cart_model->where($where)->order('time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
			foreach($orders as $k=>&$v){
				$map['token']=$this->_session('token');
				$map['wecha_id']=$v['wecha_id'];
				$storeInfo=M('store')->where($map)->field('id,vip')->find();
				$v['wecha_id']=$storeInfo['id'];
				$v['abnormal']=$storeInfo['vip'];
			}
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
		$this->assign('vipMap',$vipMap);
		
		$unHandledCount=$product_cart_model->where(array('token'=>$this->_session('token'),'handled'=>0))->count();
		$this->assign('unhandledCount',$unHandledCount);
		$this->assign('orders',$orders);
		$this->assign('page',$show);
		$this->display();
	}
	//导出订单数据
	public function ordersReport(){
		//p($_GET['statdate']);
		//exit;
		$product_cart_model=M('product_cart');
		if ($_GET['token']!=$this->_session('token')){
			exit();
		}
		$where=array('token'=>$this->_session('token'));
		$where['groupon']=array('neq',1);
		//$where['showstatus']=1; //是否显示  1显示 0 不显示（删除订单）
		if($_GET['searchkey']){
			//$key = $this->_get('searchkey');
			$key = $_GET['searchkey'];
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			$where['truename|orderid'] = array('like',"%$key%");
			
		}
		if($_GET['statdate']&&$_GET['enddate']){
			$statdate=$_GET['statdate'];
			$enddate=$_GET['enddate'];
			$stat=strtotime($statdate);
			$end=strtotime($enddate);
			$where['time'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间	
		}
		if($_GET['stat']&&$_GET['end']){
			$statdate=$_GET['stat'];
			$enddate=$_GET['end'];
			$stat=strtotime($statdate);
			$end=strtotime($enddate);
			$where['paytime'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间	
		}
		if($_GET['ostatus']){
			if($_GET['ostatus']!=0){
				$where['ostatus'] = $_GET['ostatus'];
			}
		}	
		$orders=$product_cart_model->where($where)->order('time DESC')->field('id,orderid,truename,tel,total,price,address,sent,ostatus,logistics,logisticsid,time,paytime')->select();
		foreach ($orders as &$item){
			switch ($item['sent'])
			{
				case 0:
				$item['sent'] ='未发货';
				break;
				case 1:
				$item['sent'] ='已发货';
				break;
			}
    		switch ($item['ostatus'])
			{
				case 1:
				$item['ostatus'] ='待付款';
				break;
				case 2:
				$item['ostatus'] ='待发货';
				break;
				case 3:
				$item['ostatus'] ='待收货';
				break;
				case 4:
				$item['ostatus'] ='交易完成';
				break;
				case 5:
				$item['ostatus'] ='退货中';
				break;
				case 6:
				$item['ostatus'] ='退货完成';
				break;
				case 7:
				$item['ostatus'] ='已过期';
				break;
				case 8:
				$item['ostatus'] ='已取消';
				break;
				case 9:
				$item['ostatus'] ='退货审核中';
				break;
				case 10:
				$item['ostatus'] ='拒绝退货';
				break;
			}
			
    		$item['time'] = $item['time'] ? date('Y-m-d H:i:s', $item['time']) : '';
			$item['paytime'] = $item['paytime'] ? date('Y-m-d H:i:s', $item['paytime']) : '';
    	}
		$tool = new ExcelUtils();
    	$tool->push($orders, 'id,orderid,truename,tel,total,price,address,sent,ostatus,,logistics,logisticsid,time,paytime', 'ID,订单号,姓名,电话,数量,总价（元）,送货地址,发货状态,状态,快递公司,快递单号,下单时间,支付时间', 
    			'订单报表'.date("YmdHis"), '', array(5,20,15,15,10,10,40,10,10,20,20,30,30));	
	}
	public function orderInfo(){
		$this->product_model=M('Product');
		$this->product_cat_model=M('Product_cat');
		$product_cart_model=M('product_cart');
		$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		//p($thisOrder['id']);
		//exit;
		//检查权限
		if (strtolower($thisOrder['token'])!=strtolower($this->_session('token'))){
			exit();
		}
		if (IS_POST){
			if (intval($_POST['sent'])){
				$_POST['handled']=1;
			}
			//状态改成已发货（ostatus=3）
			$res=$product_cart_model->where(array('id'=>$thisOrder['id']))->save(array('sent'=>intval($_POST['sent']),'logistics'=>$_POST['logistics'],'logisticsid'=>$_POST['logisticsid'],'ostatus'=>'3','senttime'=>time(),'handled'=>1));
			
			if($_POST['sent']==1){
				//微信多客服消息回复
				/*
				$str="阁下之订单\r\n（".$thisOrder['orderid']."）已于".date('Y-m-d H:i:s',time())."发货！\r\n快递公司是：".$_POST['logistics']."\r\n快递单号".$_POST['logisticsid']."\r\n预计3~5天左右到货，请注意查收！";
				//微信回复
				$api=M('Diymen_set')->where(array('token'=>$thisOrder['token']))->find();
				$access_token = getAccessToken($this->_session('token'));
				$data='{"touser":"'.$thisOrder['wecha_id'].'","msgtype":"text","text":{"content":"'.$str.'"}}';
				$url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token['info'];
				$this->api_notice_increment($url,$data);
				*/
				//=======
				//模板消息回复
				$url='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';
				$str=array('first'=>array('value'=>urlencode("阁下之订单已发货！"),'color'=>"#173177"),'keyword1'=>array('value'=>urlencode($thisOrder['orderid']),'color'=>"#FF0000"),'keyword2'=>array('value'=>urlencode($_POST['logistics']),'color'=>"#C4C400"),'keyword3'=>array('value'=>urlencode($_POST['logisticsid']),'color'=>"#0000FF"),'remark'=>array('value'=>urlencode("\\n请您注意包裹查收，如有问题请联系客服！"),'color'=>"#008000"));
				
				$access_token = getAccessToken($this->_session('token'));
				$data='{"touser":"'.$thisOrder['wecha_id'].'","template_id":"rn_8CtIuPzIlwWZExgCQZHeEf5uwjd9i1OiAMkSBJ5w","url":"'.C(site_url).'/index.php?g=Wap&m=Product&a=payDetail&token='.$this->_session('token').'&id='.$thisOrder['id'].'&ostatus=3","topcolor":"#FF0000","data":'.urldecode(json_encode($str)).'}';
				$url=$url.$access_token['info'];
				$this->api_notice_increment($url,$data);
				//===========
				
				
			}
			
			//如果是催促管理提交的发货，需要处理催促信息。如果是订单管理,不需理会催促信息。
			if(isset($_GET['urgeid'])){
				$id = $_GET['urgeid']; //这个id应该是urge的id，不是product_cart的id
				$where = array('id' => $id, 'token' => $this->token);
				$data['handledtime'] = time();
				$data['urgestatus'] = 1;
				$back = M('urge')->where($where)->save($data);				
			}
			$this->success('修改成功',U('Product/orderInfo',array('token'=>session('token'),'id'=>$thisOrder['id'])));
		}else {
			//订餐信息
			$product_diningtable_model=M('product_diningtable');
			if ($thisOrder['tableid']) {
				$thisTable=$product_diningtable_model->where(array('id'=>$thisOrder['tableid']))->find();
				$thisOrder['tableName']=$thisTable['name'];
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
			$this->display();
		}
	}
	//模板消息
	public function template(){
		Log::write("access_token：".$this->getAccessTokenJiesuan());
		$this->wecha_id='oFD4ys_Kk-3LIsrtJSXh-qDQBIPA';
		
	//=========
	$url='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';
	$str=array('first'=>array('value'=>urlencode("恭喜您，您的提现申请已经提交！"),'color'=>"#173177"),'keyword1'=>array('value'=>urlencode("测试银行"),'color'=>"#FF0000"),'keyword2'=>array('value'=>urlencode("123456789123456789"),'color'=>"#C4C400"),'keyword3'=>array('value'=>urlencode("200"),'color'=>"#0000FF"),'keyword4'=>array('value'=>urlencode(date("Y-m-d H:i:s")),'color'=>"#0000FF"),'keyword5'=>array('value'=>urlencode("审核中"),'color'=>"#0000FF"),'remark'=>array('value'=>urlencode("\\n尊敬的客户：您的微币提现申请已成功提交，每月，1、11、21日结算，工作日将于24小时内内到账，如遇节假日则顺延"),'color'=>"#008000"));
	
	//$access_token = $this->getAccessToken();
	$access_token =$this->getAccessTokenJiesuan();
	$data='{"touser":"'.$this->wecha_id.'","template_id":"9rxZEhwNjENNowBmSyw9QNibZLD76z8dWlh2lJ-gy-0","url":"","topcolor":"#FF0000","data":'.urldecode(json_encode($str)).'}';
	$url=$url.$access_token;
	//$url=$url.$access_token['info'];
	$this->api_notice_increment($url,$data);
	//============	
	}
	//多客服接口消费
	public function dzk(){
		$thisOrder['wecha_id']='on9FcswsgewCVkNbjmX5QcswxMqc';
		$str="多客服消息测试";
		//微信回复
		$access_token = getAccessToken($this->_session('token'));
		p($access_token['info']);
		$data='{"touser":"'.$thisOrder['wecha_id'].'","msgtype":"text","text":{"content":"'.$str.'"}}';
		$url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token['info'];
		$this->api_notice_increment($url,$data);
	}
	public function deleteOrder(){
		$product_model=M('product');
		$product_cart_model=M('product_cart');
		$product_cart_list_model=M('product_cart_list');
		$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		//检查权限
		$id=$thisOrder['id'];
		if ($thisOrder['token']!=$this->_session('token')){
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
		$this->success('操作成功',$_SERVER['HTTP_REFERER']);
	}
	//删除订单(不是真正的删掉，只是隐藏)
	public function delOrder()
    {
        $id = $this->_get('id');
        $where = array('id' => $id, 'token' => $this->_session('token'));
        $data['showstatus'] = 0; //不显示出来
        $back = M('product_cart')->where($where)->save($data);
		$this->redirect('Product/orders', array('token' => $this->_session('token')));
    }
	//桌台管理
	public function tables(){
		$product_diningtable_model=M('product_diningtable');
		if (IS_POST){
			if ($_POST['token']!=$this->_session('token')){
				exit();
			}
			for ($i=0;$i<40;$i++){
				if (isset($_POST['id_'.$i])){
					$thiCartInfo=$product_cart_model->where(array('id'=>intval($_POST['id_'.$i])))->find();
					if ($thiCartInfo['handled']){
					$product_cart_model->where(array('id'=>intval($_POST['id_'.$i])))->save(array('handled'=>0));
					}else {
						$product_cart_model->where(array('id'=>intval($_POST['id_'.$i])))->save(array('handled'=>1));
					}
				}
			}
			$this->success('操作成功',U('Product/orders',array('token'=>session('token'))));
		}else{
			

			$where=array('token'=>$this->_session('token'));
			if(IS_POST){
				$key = $this->_post('searchkey');
				if(empty($key)){
					$this->error("关键词不能为空");
				}

				$where['truename|address'] = array('like',"%$key%");
				$orders = $product_cart_model->where($where)->select();
				$count      = $product_cart_model->where($where)->count();
				$Page       = new Page($count,20);
				$show       = $Page->show();
			}else {
				
				$count      = $product_diningtable_model->where($where)->count();
				$Page       = new Page($count,20);
				$show       = $Page->show();
				$tables=$product_diningtable_model->where($where)->order('taxis ASC')->limit($Page->firstRow.','.$Page->listRows)->select();
			}

			$this->assign('tables',$tables);
			$this->assign('page',$show);
			$this->display();
		}
	}
	public function tableAdd(){ 
		if(IS_POST){
			$this->insert('Product_diningtable','/tables?dining=1');
		}else{
			$this->display('tableSet');
		}
	}
	public function tableSet(){
		$product_diningtable_model=M('product_diningtable');
        $id = $this->_get('id'); 
		$checkdata = $product_diningtable_model->where(array('id'=>$id))->find();
		if(IS_POST){ 
            $where=array('id'=>$this->_post('id'),'token'=>session('token'));
			$check=$product_diningtable_model->where($where)->find();
			if($check==false)$this->error('非法操作');
			if($product_diningtable_model->create()){
				if($product_diningtable_model->where($where)->save($_POST)){
					$this->success('修改成功',U('Product/tables',array('token'=>session('token'),'dining'=>1)));
				}else{
					$this->error('操作失败');
				}
			}else{
				$this->error($data->getError());
			}
		}else{
			$this->assign('set',$checkdata);
			$this->display();	
		
		}
	}
	public function tableDel(){
		if($this->_get('token')!=session('token')){$this->error('非法操作');}
        $id = $this->_get('id');
        if(IS_GET){                              
            $where=array('id'=>$id,'token'=>session('token'));
            $product_diningtable_model=M('product_diningtable');
            $check=$product_diningtable_model->where($where)->find();
            if($check==false)   $this->error('非法操作');
           
            $back=$product_diningtable_model->where($wehre)->delete();
            if($back==true){
            	$this->success('操作成功',U('Product/tables',array('token'=>session('token'),'dining'=>1)));
            }else{
                 $this->error('服务器繁忙,请稍后再试',U('Product/tables',array('token'=>session('token'),'dining'=>1)));
            }
        }        
	}
	//退货管理
	public function refund(){
		if(empty($_GET['status'])){
			//退货申请
			$conditon['ostatus']=9;;
		}
		elseif($_GET['status']==1){
			//退货处理
			$conditon['ostatus']=5;	
		}else{
			//退货结果
			$conditon['ostatus']=array('in',array(6,10));	
		}
		if(IS_POST){
            $key = $this->_post('searchkey');
            if(empty($key)){
                $this->error("请输入订单号");
            }

            $conditon['token']=$this->token;
			
            $conditon['orderid|truename'] = array('like',"%$key%");
        }else{
			$conditon['token']=$this->token;
			
        }
		$count      = M("product_cart")->where($conditon)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		
		$refundInfo = M("product_cart")->where($conditon)
		->limit($Page->firstRow.','.$Page->listRows)
		->select();
		$this->assign('refundInfo',$refundInfo);
		$this->assign('page',$show);
		$this->display();
	}
	//申请退货对应的订单信息
	public function refundInfo(){
		$this->product_model=M('Product');
		$product_cart_model=M('product_cart');
		//订单详情
		$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		if (strtolower($thisOrder['token'])!=strtolower($this->_session('token'))){
			exit();
		}
		if (IS_POST){	
			//$arr['ostatus'] = 5; // 状态退货审核中（ostatus=9）改为退货中（ostatus=5）
			//$arr['ostatus'] = 10; // 状态退货审核中（ostatus=9）改为拒绝退货（ostatus=10）
			$arr['id'] = $_GET['id']; //订单的ID
			$arr['ostatus'] = $_POST['ostatus']; 
			$back = M('product_cart')->save($arr);
			//===如果是同意退货，就需要扣除这条订单返还的微币
			if($_POST['ostatus']==5){
				$this->deduction();
			}else{
				//返还记录表
				$rebateRec=M('rebate_record')->where(array('orderid'=>$thisOrder['orderid']))->setField('status',0);
			}
			//====
			if($back){
				$this->success('修改成功',U('Product/refundInfo',array('token'=>session('token'),'id'=>$thisOrder['id'])));	
			}
			else{
				$this->error('修改失败',U('Product/refundInfo',array('token'=>session('token'),'id'=>$thisOrder['id'])));	
			}
			
		}else {
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
			$this->display();
		}
		
	}
	//refundInfo()备份
	public function refundInfoBak(){
		$this->product_model=M('Product');
		$this->product_cat_model=M('Product_cat');
		$product_cart_model=M('product_cart');
		$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		$refund=M('refund');
		$refundInfo=$refund->where(array('id'=>intval($_GET['refundid'])))->find();
		$this->assign('refundInfo',$refundInfo);
		//检查权限
		if (strtolower($thisOrder['token'])!=strtolower($this->_session('token'))){
			exit();
		}
		if (IS_POST){
			//通过申请 审核中（audit=1）改为审核拒绝（audit=2） 状态退货审核中（ostatus=9）改为退货中（ostatus=5）.
			//通过买家提供的订单号收到货后给买家打款。退货中（ostatus=5）变为退货完成（ostatus=6）
			//通过拒绝 状态退货审核中（ostatus=9）改变为原来的状态，交易完成（ostatus=4）。审核中（audit=1）改为审核拒绝（audit=3）
			//根据两种结果分别处理
			$refund->startTrans();	//启用事务
			$flag=0;
			$data=array();
			$data['id']=$_GET['refundid'];
			$data['audit']=$_POST['audit'];
			$data['refundstatus']=1;
			$data['handledaudit']=time();
			$result=$refund->save($data);
			if($_POST['audit']==2){
				if($result){
					$arr['id'] = $_GET['id'];
					$arr['ostatus'] = 5; // 状态退货审核中（ostatus=9）改为退货中（ostatus=5）
					$back = M('product_cart')->save($arr);
					if($back){
						$refund->commit();
						$flag=1;
					}
					else{
						$refund->rollback();
					}
				}
			}
			else{
				if($result){
					$arr['id'] = $_GET['id'];
					$arr['ostatus'] = 4; // 状态退货审核中（ostatus=9）改为原来的状态，交易完成（ostatus=4）
					$back = M('product_cart')->save($arr);
					if($back){
						$refund->commit();
						$flag=1;
					}
					else{
						$refund->rollback();
					}
				} 
				else {
					$this->error('操作失败');
				}	
			}	
			if($flag==1){
				$this->success('修改成功',U('Product/refundInfo',array('token'=>session('token'),'id'=>$thisOrder['id'])));
			}
			
		}else {
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
			$this->display();
		}
	}
	//确认退货操作 标记为已经收到货，并且给卖家打款了。
	//通过买家提供的订单号收到货后给买家打款。退货中（ostatus=5）变为退货完成（ostatus=6）
	public function refundHandled()
    {			
		$arr['id'] = $_GET['id'];
		$arr['ostatus'] = 6; // 状态退货中（ostatus=5）变为退货完成（ostatus=6）
		$arr['refundhandledtime'] = time();
		$back = M('product_cart')->save($arr);
		if($back){
			$this->success('操作成功');
		}
		else{
			$this->error('操作失败');
		}
		
    }
	//催促管理
	public function urge(){
		if(IS_POST){
            $key = $this->_post('searchkey');
            if(empty($key)){
                $this->error("请输入订单号");
            }

            $conditon['u.token']=$this->token;
            $conditon['pc.orderid|pc.truename'] = array('like',"%$key%");
        }else{
			$conditon['u.token']=$this->token;	
        }
		$count      = M() ->table("tp_urge as u")
		->join("tp_product_cart as pc on u.pcid=pc.id ")
		->where($conditon)
		->count();
		
		$Page       = new Page($count,20);
		$show       = $Page->show();  
		$urgeInfo = M() ->table("tp_urge as u")
		->join("tp_product_cart as pc on u.pcid=pc.id ")
		->where($conditon)
		->order('urgetime desc')
		->field("pc.truename,pc.tel,pc.price,pc.sent,pc.info,pc.orderid,pc.ostatus,pc.handled,u.*")
		->limit($Page->firstRow.','.$Page->listRows)
		->select();
		//为处理的催促信息总数
		
		$urgeCount=M() ->table("tp_urge as u")
		->join("tp_product_cart as pc on u.pcid=pc.id ")
		->where(array('u.token'=>$this->token,'u.urgestatus'=>0,'pc.handled'=>array('neq',1)))
		->count('1');
		$this->assign('urgeCount',$urgeCount);
		$this->assign('urgeInfo',$urgeInfo);
		$this->assign('page',$show);
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
		$this->redirect('Product/orders', array('token' => $this->token));
    }
	//退货扣除微币
	private function deduction(){
		//读取配置表
		$setInfo=M('distribution_set')->where(array('token'=>$this->token))->find();
		if(empty($setInfo)){$this->error('请设置盈利的参数',U('Distribution/set',array('token'=>$this->token)));exit;}
		//===============
		$store=M('store');
		$id = $_GET['id']; //订单的id
		//$where = array('id' => $id, 'token' =>$this->token,'moneystatus'=>0);//moneystatus=0 订单未操作返利
		$where = array('id' => $id, 'token' =>$this->token);//交易成功就返微币，所以退款必须扣除返的微币
		$financeInfo=M('product_cart')->where($where)->find(); //该订单的详细信息
		//退款订单
		//混搭模式下，返还微币
		$rebateWb=M('rebate_record')->where(array('orderid'=>$financeInfo['orderid'],'type'=>4))->find();
		//得到的$rebateWb['noincome']是负数
		if($rebateWb){
			$StoreInfo=$store->where(array('token'=>$this->token,'id'=>$rebateWb['storeid']))->setInc('income',-$rebateWb['noincome']);
			Log::write("消费者ID".$rebateWb['storeid']."返还的微币".$rebateWb['noincome'], Log::DEBUG, 3, LOG_PATH.'rebateWb.log');
			//写入微币明细表
			$record['storeid'] 	= $rebateWb['store_id'];
			$record['orderid'] 	= $financeInfo['orderid'];
			$record['noincome'] = -$rebateWb['noincome'];  //消费是正，代表是退货的，是负数，代表是消费
			$record['status'] 	= 2;
			$record['recordtime'] 	= time();
			$record['type'] 	= 4;
			M('rebate_record')->add($record);	
		}
		
		//扣除积分、返还给上级的微币、直1，广告圈消费业绩（应该扣掉累积，不要扣新增的？）
		$rebateRec=M('rebate_record')->where(array('orderid'=>$financeInfo['orderid'],'type'=>1))->select();
		if($rebateRec){
			foreach($rebateRec as $k=>$v){
			$StoreInfo1=$store->where(array('token'=>$this->token,'id'=>$v['storeid']))->find();
			Log::write("返还者ID".$v['storeid']."返还者之前的微币".$StoreInfo1['noincome'], Log::DEBUG, 3, LOG_PATH.'rebateRec.log');
				$StoreInfo=$store->where(array('token'=>$this->token,'id'=>$v['storeid']))->setDec('noincome',$v['noincome']);
				Log::write("返还者ID".$v['storeid']."扣掉的微币".$v['noincome'], Log::DEBUG, 3, LOG_PATH.'rebateRec.log');
				//写入微币明细表
				$record['storeid'] 	= $v['store_id'];
				$record['orderid'] 	= $financeInfo['orderid'];
				$record['noincome'] = -$v['noincome'];  //消费是正，代表是退货的，是负数，代表是消费
				$record['status'] 	= 2;
				$record['recordtime'] 	= time();
				$record['type'] 	= 7; //推荐奖励退货扣除
				M('rebate_record')->add($record);
			}
			
			Log::write("返还者ID".$v['storeid']."返还者扣除后的微币".($StoreInfo1['noincome']-$v['noincome']), Log::DEBUG, 3, LOG_PATH.'rebateRec.log');
		}
		$coinRec=M('coin_record')->where(array('orderid'=>$financeInfo['orderid']))->find();
		if($coinRec){
			$StoreInfo=$store->where(array('token'=>$this->token,'id'=>$coinRec['storeid']))->setDec('coin',$coinRec['coin']);
			Log::write("积分返还者ID".$coinRec['storeid']."积分".$coinRec['coin'], Log::DEBUG, 3, LOG_PATH.'jifenRec.log');
		}
		
		//上面积分扣除，微币也扣。那直1消费和广告圈消费怎么扣除。订单表shore
		//有bug，如果微信支付后面执行不成功，不返利，不加销售额，这里扣就不合理。所以加上$rebateRec
		if($financeInfo['store_id'] && $rebateRec){
			
			//扣除广告圈消费、广告圈新增消费
			$parent['fid'] = $financeInfo['store_id'];
			$parents = array();
			$i=0;
			while(!empty($parent['fid'])){
				if($parents[$parent['fid']]){
					echo 'id:'.$parent['id'].'  fid:'.$parent['fid'];break;
				}
				$parent = $store->where(array('token'=>$this->token,'id'=>$parent['fid']))->find();
				$parent['adCountSale'] = $parent['adCountSale']-$financeInfo['price'];
				$parent['newAdCountSale'] = $parent['newAdCountSale']-$financeInfo['price'];
				if($i == 0){
					//扣掉直1消费、直1新增消费
					$parent['directlySale'] = $parent['directlySale']-$financeInfo['price'];
					$parent['newDirectlySale'] = $parent['newDirectlySale']-$financeInfo['price'];
					Log::write('===========直1storeid为'.$parent['id'].'扣掉'.$financeInfo['price']);
				}
				if($i == 1){
					//（j1CountSale）间1销售额累加
					$parent['j1CountSale'] = $parent['j1CountSale'] - $financeInfo['price'];
					Log::write('===========间1storeid为'.$parent['id'].'扣掉'.$financeInfo['price']);
				}
				if($i == 2){
					//（j2CountSale）间2销售额累加
					$parent['j2CountSale'] = $parent['j2CountSale'] - $financeInfo['price'];
					Log::write('===========间2storeid为'.$parent['id'].'扣掉'.$financeInfo['price']);
				}
				$store->save($parent);
				$parents[$parent['id']] = $parent;
				Log::write('===========广告圈'.$i.'storeid为'.$parent['id'].'扣掉'.$financeInfo['price']);
				$i++;
			}
		}
		
		
	}
	//商城回复配置
	public function reply(){
		$db = D('reply_info');
		$setInfo=$db->where(array('token'=>$this->token,'infotype'=>'Shop'))->find();
		if(IS_POST){
			if($db ->create()){
				if(empty($setInfo)){
					//执行add（）
					$res=$db->add();	
				}
				else{
					//执行save
					$data['id']=$setInfo['id'];
					$res=$db->save();	
				}
				if($res){
					$this->success("操作成功",U('Product/index',array('token'=>$this->token)));
					//echo M('distribution_set')->getLastSql();
				}
				else{		
					$this->error("操作失败,请确认输入的值是否有改动",U('Product/index',array('token'=>$this->token)));
					//echo "res:".$res;
					//echo $db->getLastSql();
				}
			}else{
                $this->error($db->getError());
            }
		}
		else{
			$this->assign('setInfo',$setInfo);
			$this->display();
		}
		
	}
	//商城公告
	public function notice(){
		$db = D('notice');
		$setInfo=$db->where(array('token'=>$this->token))->find();
		if(IS_POST){
			if($db ->create()){
				if(empty($setInfo)){
					//执行add（）
					$res=$db->add();	
				}
				else{
					//执行save
					$data['id']=$setInfo['id'];
					$res=$db->save();	
				}
				if($res){
					$this->success("操作成功",U('Product/notice',array('token'=>$this->token)));
					//echo M('distribution_set')->getLastSql();
				}
				else{		
					$this->error("操作失败,请确认输入的值是否有改动",U('Product/notice',array('token'=>$this->token)));
					//echo "res:".$res;
					//echo $db->getLastSql();
				}
			}else{
                $this->error($db->getError());
            }
		}
		else{
			$this->assign('setInfo',$setInfo);
			$this->display();
		}
		
	}
	//物流管理
	public function logistics(){
		$logModel = D('Logistics');
		$count=$logModel->count();
		$page=new Page($count,10);
		$info=$logModel->where(array('token'=>session('token')))->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('info',$info);
		$this->display();	
	}
	public function addLogistics(){
		$logModel = D('Logistics');
		if(!empty($_POST)){
			$map['token']=session('token');
			$map['chk']=1;
			$map['status']=1;
			$cnt=$logModel->where($map)->count();			
			$count=$cnt+$_POST['chk'];

			if($count>1){
				$this->error('已经选择了默认其他物流，请先关闭其他物流后再设置此物流为默认！', U('Product/logistics',array('token'=>session('token')))); 
			}
			
			if($logModel ->create()){
				$res = $logModel ->add();
				if($res){
					$this->success('新增物流成功', U('Product/logistics',array('token'=>session('token'))));
				}
				else{
					$this->error('添加失败',U('Product/logistics',array('token'=>session('token'))));
				}
			}else{
				$this->error($logModel->getError());
			}
		}else {
			$this->display();
		}
	}
	public function editLogistics(){
		$logModel = D('Logistics');
		$where['id']=$this->_get('id','intval');
		$info=$logModel->where($where)->find();
		if(!empty($_POST)){
			$map['token']=session('token');
			$map['status']=1;
			$map['chk']=1;
			$cnt=$logModel->where($map)->count();
			
			if($_POST['status']==1){
				$count=$cnt+$_POST['chk'];
			}else{
				$count=$cnt;
			}
			if($count>1 ){
				$this->error('已经选择了默认其他物流，请先关闭其他物流后再设置此物流为默认！', U('Product/logistics',array('token'=>session('token')))); 
			}
			$logModel->create();
			$res = $logModel ->save();
			if($res){
				
				$this->success('编辑成功', U('Product/logistics'));
				
			}else{
				$this->error('编辑失败',U('Product/logistics',array('token'=>session('token'))));
			}
		}else{
			$this->assign('info',$info);
			$this->display('addLogistics');
        }
	}
	public function delLogistics(){
		$where['id']=$this->_get('id','intval');
		$logModel = D('Logistics');
		if($logModel->where($where)->delete()){
			$this->success('删除成功', U('Product/logistics'));
		}else{
			$this->error('删除失败',U('Product/logistics'));
		}
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
		//echo $tmpInfo;
		if (curl_errno($ch)) {
			return false;
		}else{

			return true;
		}
	}
	//结算服务号微信模板消息调用
	private function getAccessTokenJiesuan(){
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
}


?>