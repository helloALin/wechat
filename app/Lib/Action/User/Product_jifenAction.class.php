<?php
class Product_jifenAction extends UserAction{
	public $token;
	public $product_model;
	public $product_cat_model;
	public $isDining;
	public function _initialize() {
		parent::_initialize();
		//
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
		$product_model=M('Product_jifen');
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
		if(IS_POST){
			$this->all_insert('Product_jifen','/index?token='.session('token').'&dining='.$this->isDining);
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
        $product_model=M('Product_jifen');
        $product_cat_model=M('Product_cat');
		$checkdata = $product_model->where(array('id'=>$id))->find();
		if(empty($checkdata)){
            $this->error("没有相应记录.您现在可以添加.",U('Product_jifen/add'));
        }
		if(IS_POST){ 
            $where=array('id'=>$this->_post('id'),'token'=>session('token'));
			$check=$product_model->where($where)->find();
			if($check==false)$this->error('非法操作');
			if($product_model->create()){
				if($product_model->where($where)->save($_POST)){
					$this->success('修改成功',U('Product_jifen/index',array('token'=>session('token'),'dining'=>$this->isDining)));
					$keyword_model=M('Keyword');
					$keyword_model->where(array('token'=>session('token'),'pid'=>$this->_post('id'),'module'=>'Product_jifen'))->save(array('keyword'=>$this->_post('keyword')));
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
	public function orders(){
		$product_cart_model=M('product_jifen_cart');		
		$where=array('token'=>$this->_session('token'));
		$where['groupon']=array('neq',1);
		if(IS_POST){
			$key = $this->_post('searchkey');
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			//$where['truename|address'] = array('like',"%$key%");
			$where['truename|orderid'] = array('like',"%$key%");
		}else {
			if (isset($_GET['handled'])){
				$where['handled']=intval($_GET['handled']);
			}
			if (isset($_GET['wecha_id'])){
				$where['wecha_id']=$_GET['wecha_id'];
			}	
		}
		$count      = $product_cart_model->where($where)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$orders=$product_cart_model->where($where)->order('time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();

		$unHandledCount=$product_cart_model->where(array('token'=>$this->_session('token'),'handled'=>0))->count();
		$this->assign('unhandledCount',$unHandledCount);
		$this->assign('orders',$orders);
		$this->assign('page',$show);
		$this->display();
	}
	public function orderInfo(){
		$this->product_model=M('Product_jifen');
		$product_cart_model=M('product_jifen_cart');
		$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		//检查权限
		if (strtolower($thisOrder['token'])!=strtolower($this->_session('token'))){
			exit();
		}
		if (IS_POST){
			if (intval($_POST['sent'])){
				$_POST['handled']=1;
			}
			//状态改成已发货（ostatus=3）
			$res=$product_cart_model->where(array('id'=>$thisOrder['id']))->save(array('sent'=>intval($_POST['sent']),'logistics'=>$_POST['logistics'],'logisticsid'=>$_POST['logisticsid'],'senttime'=>time(),'handled'=>1));
			$this->success('修改成功',U('Product_jifen/orderInfo',array('token'=>session('token'),'id'=>$thisOrder['id'])));
		}else {
			$list=$this->product_model->where(array('id'=>$thisOrder['pid']))->find();
			$this->assign('thisOrder',$thisOrder);
			$this->assign('products',$list);
			$this->display();
		}
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
			
            $conditon['orderid'] = array('like',"%$key%");
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
		$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
		if (strtolower($thisOrder['token'])!=strtolower($this->_session('token'))){
			exit();
		}
		if (IS_POST){	
			$arr['id'] = $_GET['id'];
			$arr['ostatus'] = 5; // 状态退货审核中（ostatus=9）改为退货中（ostatus=5）
			$back = M('product_cart')->save($arr);
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
            $conditon['pc.orderid'] = array('like',"%$key%");
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
        $id = $this->_get('pcid');
        $where = array('id' => $id, 'token' =>$this->token);
        $data['confirmtime'] = time();
        $data['ostatus'] = 4;
        $back = M('product_cart')->where($where)->save($data);
        //$this->redirect("Wap/Product/my", $_GET);
		$this->redirect('Product/urge', array('token' => $this->token));
    }	
}


?>