<?php
/**
 *文本回复
**/
class DistributionAction extends UserAction{
	public $token;
	public $store;
	public function _initialize() {
		parent::_initialize();
		//flag表示 财务账号（caiwu uid=106） 从微信公众号功能管理进入 重写session token
		if($_GET['flag']==1){
			session('token', $_GET['token']);
		}
		$this->token= session('token');
		$this->assign('token',$this->token);
	}

	/**
	 * 微信帐号设置操作
	 */
	public function account(){
		$model = M('AssistPublic');
		$info = $model->find($this->token);
		if(IS_POST){
			$data = array('token'=>$this->token);
			$data['appid'] = $this->_post('appid', 'trim');
			$data['appsecret'] = $this->_post('appsecret', 'trim');
			if(empty($data['appid']) || empty($data['appsecret'])){
				$this->error('APPID, APPSECRET不能为空！');
			}else{
				if($_FILES['qrcode'] && $_FILES['qrcode']['name']){
					import('@.ORG.UploadFile');
					$upload = new UploadFile();// 实例化上传类
					$upload->maxSize  = 3145728 ;// 设置附件上传大小
					$upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
					$upload->savePath =  './uploads/';// 设置附件上传目录
					$upload->saveRule =  'qrcode_'.$this->token;// 保存文件名
					$upload->uploadReplace = true;		//替换原来的图片
					$file = $upload->uploadOne($_FILES['qrcode']);
					if($file == false) {// 上传错误提示错误信息
						$this->error($upload->getErrorMsg());
					}else{// 上传成功 获取上传文件信息
						$data['qrcode'] = $file[0]['savepath'].$file[0]['savename'];
					}
				}
				//生成公众号菜单
				$url_get = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $data['appid'] . '&secret=' . $data['appsecret'];
				$token = curlGet($url_get);
				$json = json_decode($token);
				if($json && $json->access_token){
					curlGet('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$json->access_token);
					
					$url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$json->access_token;
					$menu = '{"button":[{"type":"view", "name":"个人中心", "url":"'.C('site_url').U('Wap/Distribution/index',array('token'=>$this->token)).'"},{ "name":"联系客服","sub_button":[{"type":"view", "name":"QQ客服", "url":"http://wpa.qq.com/msgrd?v=3&uin=2644314672&site=qq&menu=yes"]}}]}';
					Log::write('submit menu str: '.$menu);
					$submit_menu = curlPost($url,$menu);
					Log::write('submit menu str: '.$submit_menu);
					$submit_menu = json_decode($submit_menu);
					if(!$submit_menu && $submit_menu->errcode != 0){
						$this->error('生成辅助公众号菜单失败！');
					}
				}else{
					Log::write('获取accessToken失败：'.$token);
					$this->error('操作失败，请确认填写的APPID和APPSECRET是否正确');
				}
				
				if($info){
					$data['update_time'] = time();
					$result = $model->save($data);
				}else{
					$data['create_time'] = time();
					$result = $model->add($data);
				}
				if($result !== false)
					$this->success('设置成功！', U('Distribution/account'));
				else
					$this->error('设置失败！');
			}
		}else{
			$this->assign('info', $info);
			$this->display();
		}
	}
	
	public function index(){
		$storeModel=M('store');
		$where['token']=$this->token;
		
		if($_GET['searchkey']){
			//$key = $this->_get('searchkey');
			$key = $_GET['searchkey'];
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			$where['id|username'] = array('like',"%$key%");
			
		}
		if($_GET['status']>-1){
			
			$where['status'] = $_GET['status'];
				
		}
		if($_GET['vip']>-1){
			
			$where['vip'] = $_GET['vip'];
				
		}
		
		if($_GET['statdate']&&$_GET['enddate']){
			$statdate=$_GET['statdate'];
			$enddate=$_GET['enddate'];
			$stat=strtotime($statdate);
			$end=strtotime($enddate);
			$where['createtime'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间	
		}
		
		$count      = $storeModel->where($where)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$storeInfo=$storeModel->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
		$this->assign('vipMap',$vipMap);
		$this->assign('page',$show);
		$this->assign('storeInfo',$storeInfo);
		$this->display();
	}
	public function peopleInfo(){
		$map['token']=$this->token;
		$map['wecha_id']=$_GET['openid'];	
		$userInfo=M('userinfo')->where($map)->find();
		$this->assign('userInfo',$userInfo);
		$sexMap = M('Dictionary')->where("type='sex'")->getField("keyvalue,keyname");
		$this->assign('sexMap',$sexMap);
		$this->assign('openid',$_GET['openid']);
		$this->display();
	}
	//导出个人信息表
	public function infoReport(){
		$map['token']=$this->token;
		if($_GET['openid']){
			$map['wecha_id']=$_GET['openid'];	
		}
		$userInfo=M('userinfo')->where($map)->select();
		foreach ($userInfo as &$item){
    		switch ($item['sex'])
			{
				case 0:
				$item['sex'] ='其他';
				break;
				case 1:
				$item['vip'] ='男';
				break;
				case 2:
				$item['vip'] ='女';
				break;
			}   		
    	}
		$tool = new ExcelUtils();
    	$tool->push($userInfo, 'truename,sex,womanLog,tel,wechaname,email,qq,bankcardusername,bankcard,bankname,subbankname,zhifubaousername,zhifubao', '姓名,性别,生理期,手机号,微信号,邮箱,QQ,银行卡姓名,银行卡账号,开户银行,开户银行所属分行,支付宝姓名,支付宝账号', 
    			'个人信息表'.date("YmdHis"), '', array(10,10,10,15,20,25,15,15,25,20,20,20,20));
	}

	//编辑个人信息
	public function editPeopleInfo(){
		$userinfoModel=D('userinfo');
		$map['token']=$this->token;
		$map['wecha_id']=$_GET['openid'];	
		$userInfo=$userinfoModel->where($map)->find();
		if(empty($userInfo)){
			$this->error("暂时不能编辑个人信息！");
		}else{
			if(IS_POST){
				if($userinfoModel ->create()){
					
					//执行save
					$condition['id'] = $userInfo['id'];
					$res=$userinfoModel->where($condition)->save();		
					
					if($res){
						$this->success("操作成功");
						//echo M('distribution_set')->getLastSql();
					}
					else{		
						$this->error("操作失败,请确认输入的值是否有改动");
					}
				}else{
					$this->error($userinfoModel->getError());
				}
			}
			else{
				$this->assign('userInfo',$userInfo);
				$this->assign('openid',$_GET['openid']);
				$this->display();
			}
		}
	}
	//编辑个人信息
	public function editStoreInfo(){
		exit('该功能已经取消');
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
		$this->assign('vipMap',$vipMap);
		$userinfoModel=D('store');
		$map['token']=$this->token;
		$map['id']=$_GET['storeid'];	
		$userInfo=$userinfoModel->where($map)->find();
		//Log::write("编辑前个人数据:".json_encode($userInfo), LOG_PATH.'userInfoEdit.log');
		Log::write("编辑前个人数据".json_encode($userInfo), Log::DEBUG, 3, LOG_PATH.'userInfoEdit.log');
		if(empty($userInfo)){
			$this->error("暂时不能编辑个人信息！");
		}else{
			//先有申请退货，再有修改个人信息。功能未开发
			if(IS_POST){
				if($userinfoModel ->create()){
					
					//执行save
					$condition['id'] = $userInfo['id'];
					$res=$userinfoModel->where($condition)->save();		
					
					if($res){
						$this->success("操作成功");
						//echo M('distribution_set')->getLastSql();
					}
					else{		
						$this->error("操作失败,请确认输入的值是否有改动");
					}
				}else{
					$this->error($userinfoModel->getError());
				}
			}
			else{
				$this->assign('userInfo',$userInfo);
				
				$this->display();
			}
		}
	}
	//删除个人数据
	public function delStore(){
		exit('该功能已经取消');
		$id = $_GET['id'];
		if(IS_GET){
			$db = D('store');
			if($db->delete($id)){
				$this->success('删除成功',U('Distribution/index',array('token'=>$this->token)));
			}else{
				$this->error('删除失败');
			}
			
		}else{
			$this->error('非法操作');
		}
	}
	//输入订单号查是否是店主（未完成）
	public function orderStore(){
		$this->display();
	}
	//审核用户
	public function check(){
		$map['token']=$this->token;
		$map['id']=$_GET['id'];
		$storeInfoSingle=M('store')->where($map)->find();
		//查询该订单的具体信息
		$condition['orderid']=$storeInfoSingle['orderid'];
		$condition['wecha_id']=$storeInfoSingle['wecha_id'];
		$orderInfo=M('product_cart')->where($condition)->find();
		$this->assign('storeInfoSingle',$storeInfoSingle);
		$this->assign('orderInfo',$orderInfo);
		if (IS_POST){
			$data['id'] = $_GET['id'];
			$data['status'] =$_POST['status'];          //$this->_post('status');//店铺的状态
			$data['vip'] = $_POST['vip'];               //$this->_post('vip');//店铺的状态
			if($_POST['status']==2){
				//审核店主资格拒绝
				$data['vip'] = 0;
			}
			$data['refusereason'] = $_POST['refusereason'];   // $this->_post('refusereason');
			$data['applyhandled'] = time();
			$result=M('store')->save($data);
			if($result){
				$this->success('修改成功',U('Distribution/check',array('token'=>$this->token,'id'=>$_GET['id'])));
			}else{
				$this->error('操作失败');
			}
		}
		$this->display();
	}
	
	//查看客户
	public function customer(){
		$id=$_GET['id'];
		if(empty($id)){$this->error('操作失败');}
		else{
			/*
			*天使银章资格：直1消费9000元，广告圈10万元 
			*天使金章资格：直1消费9万元，广告圈200万元
			*天使银章资格：直1消费18万元，广告圈500万元
			*/
			$y1='9000';$y2='100000';
			$j1='90000';$j2='2000000';
			$z1='180000';$z2='5000000';
			//========
			$store=M('store')->where(array('id'=>$id))->find();
			if($store['newDirectlySale']<=$y1||$store['newAdCountSale']<=$y2){
				$reward='没有';
			}
			elseif($y1<$store['newDirectlySale']||$y2<$store['newAdCountSale']){
				$reward='银章';
			}
			
			elseif($j1<$store['newDirectlySale']||$j2<$store['newAdCountSale']){
				$reward='金章';
			}
			elseif($z1<$store['newDirectlySale']||$z2<$store['newAdCountSale']){
				$reward='钻章';
			}
			$store['reward']=$reward;
			$this->assign('store',$store);
			$map['fid|gid|ggid']=$id;
			$map['token']=$this->token;
			$customers=M('store')->field("id,fid,gid,ggid")->where($map)->select();
			if(empty($customers)){$this->error('您暂时还没有客户');}
			
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
					$map["id"] = array("in", $customersId);
			}
			$count      = M("store")->where($map)->field("id,nickname,wecha_id,fid,gid,ggid,createtime,level,vip")->count();
			$Page       = new Page($count,20);
			$show       = $Page->show();
			$customerList = M("store")->where($map)->field("id,status,nickname,wecha_id,fid,gid,ggid,createtime,level,headimgurl,vip,newDirectlySale,newAdCountSale")->select();
			foreach($customerList as &$v){
				if($v['fid'] == $id){$v['level']=1;}				
				if($v['gid'] == $id){$v['level']=2;}
				if($v['ggid'] == $id){$v['level']=3;}
				
				if($v['newDirectlySale']<=$y1||$v['newAdCountSale']<=$y2){
					$reward='没有';
				}
				elseif($y1<$v['newDirectlySale']||$y2<$v['newAdCountSale']){
					$reward='银章';
				}
				
				elseif($j1<$v['newDirectlySale']||$j2<$v['newAdCountSale']){
					$reward='金章';
				}
				elseif($z1<$v['newDirectlySale']||$z2<$v['newAdCountSale']){
					$reward='钻章';
				}
				$v['reward']=$reward;
			}
			//echo "level1: ".$level1."  level2: ".$level2."  level3: ".$level3;
			//p($customerList);
			$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
			$this->assign('vipMap',$vipMap);
			
			$this->assign('count',$count);
			$this->assign('page',$show);
			$this->assign("level1", $level1);
			$this->assign("level2", $level2);
			$this->assign("level3", $level3);
			$this->assign("customers", $customers);
			$this->assign("customerList", $customerList);
		}
		$this->display();
	}
	//盈利设置
	public function set(){
		$setModel=D('Distribution_set');
		$setInfo=$setModel->where(array('token'=>$this->token))->find();
		if(IS_POST){
			if($setModel ->create()){
				/* $data['token']=$this->token;
				$data['level1']=$_POST['level1'];
				$data['level2']=$_POST['level2'];
				$data['level3']=$_POST['level3'];
				$data['own']=$_POST['own']; */
				if(empty($setInfo)){
					//执行add（）
					$res=$setModel->add();	
				}
				else{
					//执行save
					$data['id']=$setInfo['id'];
					$res=$setModel->save();	
				}
				if($res){
					$this->success("操作成功",U('Distribution/set',array('token'=>$this->token)));
					//echo M('distribution_set')->getLastSql();
				}
				else{		
					$this->error("操作失败,请确认输入的值是否有改动",U('Distribution/set',array('token'=>$this->token)));
					//echo "res:".$res;
					//echo $setModel->getLastSql();
				}
			}else{
                $this->error($setModel->getError());
            }
		}
		else{
			$this->assign('setInfo',$setInfo);
			$this->display();
		}
		
	}
	//
	//代理资格设置
	public function agentSet(){
		$setModel=D('Distribution_agent');
		$setInfo=$setModel->where(array('token'=>$this->token))->find();
		if(IS_POST){
			if($setModel ->create()){
				if(empty($setInfo)){
					//执行add（）
					$res=$setModel->add();	
				}
				else{
					//执行save
					$data['id']=$setInfo['id'];
					$res=$setModel->save();	
				}
				if($res){
					$this->success("操作成功",U('Distribution/agentSet',array('token'=>$this->token)));
					//echo M('distribution_set')->getLastSql();
				}
				else{		
					$this->error("操作失败,请确认输入的值是否有改动",U('Distribution/agentSet',array('token'=>$this->token)));
					//echo "res:".$res;
					//echo $setModel->getLastSql();
				}
			}else{
                $this->error($setModel->getError());
            }
		}
		else{
			$this->assign('setInfo',$setInfo);
			$this->display();
		}
		
	}
	//财务管理  七天之后才开始算返利（七天之后不可以退货）把钱打到用户小钱包
	public function financeOld(){
		$product_cart_model=M('product_cart');
		$where=array('token'=>$this->token);
		//$where['ostatus']=array('in',array(4,6));
		$where['ostatus']=4;
		if(isset($_GET['moneystatus'])){
			$where['moneystatus']=$_GET['moneystatus'];
		}
		if(IS_POST){
			$key = $this->_post('searchkey');
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			$where['orderid'] = array('like',"%$key%");	
		}
		$count      = $product_cart_model->where($where)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$orders=$product_cart_model->where($where)->order('time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('orders',$orders);
		$this->assign('page',$show);
		$this->display();
	}
	//返积分，返微币（此订单是有效的成功交易订单）
	public function finance(){
		$product_cart_model=M('product_cart');
		$where=array('token'=>$this->token);
		//$where['ostatus']=array('in',array(4,6));
		$where['ostatus']=4;
		$where['store_id']=array('neq',0);//store_id=0 属于顶级，不需要给上级返利
		if(isset($_GET['moneystatus'])){
			$where['moneystatus']=$_GET['moneystatus'];
		}
		if(IS_POST){
			$key = $this->_post('searchkey');
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			$where['orderid'] = array('like',"%$key%");	
		}
		$count      = $product_cart_model->where($where)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$orders=$product_cart_model->where($where)->order('time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('orders',$orders);
		$this->assign('page',$show);
		$this->display();
	}
	//财务处理 结算
	public function financeDealOld(){
		//读取配置表
		$setInfo=M('distribution_set')->where(array('token'=>$this->token))->find();
		if(empty($setInfo)){$this->error('请设置盈利的参数',U('Distribution/set',array('token'=>$this->token)));exit;}
		//===============
		$store=M('store');
		//1、获得该订单的店铺store_id，如果店铺id是0，则无需返利。如果不是0，则要返利给其fid
		//返利方式已修改：获得该订单的店铺store_id（此store_id是购买者的fid,）
		//如果store_id=0 表示没有父类，无需给父类返利，给自己返利（必须是开了店铺的才给自己返利）
		//如果store_id不等于0 需给父类id=store_id fid gid返利，给自己返利
		$id = $this->_get('id'); //订单的id
        $where = array('id' => $id, 'token' =>$this->token,'moneystatus'=>0);
		$financeInfo=M('product_cart')->where($where)->find();
		//=====给自己返利（必须有店铺）订单ownstatus=1 代表是有店铺的时候购买商品===
		$where = array('id' => $id, 'token' =>$this->token,'moneystatus'=>0,'ownstatus'=>1);		
		$financePeople=M('product_cart')->where($where)->find();
		//此订单是在有店铺的情况下购买的
		if(isset($financePeople)){
			$storeInfo=$store->where(array('token'=>$this->token,'wecha_id'=>$financePeople['wecha_id']))->find();
			$arr['id'] = $storeInfo['id'];
			$arr['income'] = $storeInfo['income']+$financePeople['price']*$setInfo['own']/100;
			$res=$store->save($arr);
		}	
		//==================
		if(empty($financeInfo['store_id'])){
			/* $data['id'] = $id;
			$data['moneystatus'] = 1;//已操作、已结算
			$data['moneytime'] = time();
			$back = M('product_cart')->save($data);//无论如何都需要把这个订单置为已经操作。那如果$res操作失败也保存？ */
			if(empty($financePeople)){
				$data['id'] = $id;
				$data['moneystatus'] = 1;//已操作、已结算
				$data['moneytime'] = time();
				$back = M('product_cart')->save($data);//无论如何都需要把这个订单置为已经操作。那如果$res操作失败也保存？
				$this->success('不需要给上级返利',U('Distribution/finance',array('token'=>$this->token)));
				exit;
			}
			if($res){
				$data['id'] = $id;
				$data['moneystatus'] = 1;//已操作、已结算
				$data['moneytime'] = time();
				$back = M('product_cart')->save($data);//无论如何都需要把这个订单置为已经操作。那如果$res操作失败也保存？
				$this->success('操作成功',U('Distribution/finance',array('token'=>$this->token)));
			}
			else{
				$this->error('操作失败',U('Distribution/finance',array('token'=>$this->token)));
			}
			
		}
		else{
			$storeId=$financeInfo['store_id'];
			$storeInfo=$store->where(array('id'=>$storeId))->find(); //订购者的父亲级
			//考虑到父id可能在后面取消了店主资格，所以再做一次判断，如果不是店主，不给他返利。
			if(($storeInfo['status']==1)){
			//给id=storeId的分销商返利结算
				$arr1['id'] = $storeId;
				$arr1['income'] = $storeInfo['income']+$financeInfo['price']*$setInfo['level1']/100;
				$res1=$store->save($arr1);
			//给id=storeId的fid分销商返利结算	
			}
			if(!empty($storeInfo['fid'])){
				$storeInfoFid=$store->where(array('id'=>$storeInfo['fid']))->find();
				if(($storeInfoFid['status']==1)){
					$arr2['id'] =$storeInfoFid['id'];
					$arr2['income'] =$storeInfo['income']+ $financeInfo['price']*$setInfo['level2']/100;
					$res2=$store->save($arr2);
				}
			}
			//给id=storeId的gid分销商返利结算(如果把自销看成一级，这就属于第四级分化返利了)
			if(!empty($storeInfo['gid'])){
				$storeInfoGid=$store->where(array('id'=>$storeInfo['gid']))->find();
				if(($storeInfoGid['status']==1)){
					$arr3['id'] = $storeInfoGid['id'];
					$arr3['income'] = $storeInfoGid['income']+ $financeInfo['price']*$setInfo['level3']/100;
					$res3=$store->save($arr3);
				}
			}
			//=================================
			if($res1){
				$data['id'] = $id;
				$data['moneystatus'] = 1;//已结算
				$data['moneytime'] = time();
				$back = M('product_cart')->save($data);
			}
			else{
				$this->error('保存失败',U('Distribution/finance',array('token'=>$this->token)));
			}
			
		}
		if($back){
			$this->redirect('Distribution/finance', array('token' => $this->token));
		}
	}
	public function financeDeal(){
		//读取配置表
		$setInfo=M('distribution_set')->where(array('token'=>$this->token))->find();
		if(empty($setInfo)){$this->error('请设置盈利的参数',U('Distribution/set',array('token'=>$this->token)));exit;}
		//===============
		$store=M('store');
		//1、获得该订单的店铺store_id，如果店铺id是0，则无需返利。如果不是0，则要返利给其fid
		//返利方式已修改：获得该订单的店铺store_id（此store_id是购买者的fid,）
		//如果store_id=0 表示没有父类，无需返利
		//如果store_id不等于0 需给父类id=store_id 返利，再给gid,ggid返利
		$id = $this->_get('id'); //订单的id
        $where = array('id' => $id, 'token' =>$this->token,'moneystatus'=>0);//moneystatus=0 订单未操作返利
		$financeInfo=M('product_cart')->where($where)->find();
		//自己消费返利（返还积分）
		//必须有店铺，订单ownstatus=1 代表是有店铺的时候购买商品===
		//$where = array('id' => $id, 'token' =>$this->token,'moneystatus'=>0,'ownstatus'=>1);
		//只要消费就返还微币，不管是不是店主
		$where = array('id' => $id, 'token' =>$this->token,'moneystatus'=>0);	
		$financePeople=M('product_cart')->where($where)->find();
		//此订单是在有店铺的情况下购买的
		if(isset($financePeople)){
			//echo '有自己的店铺';
			$ownStoreInfo=$store->where(array('token'=>$this->token,'wecha_id'=>$financePeople['wecha_id']))->find();
			$arr['id'] = $ownStoreInfo['id'];
			$arr['income'] = $ownStoreInfo['income']+$financePeople['price']/$setInfo['own']; //10积分返还1微币
			$res=$store->save($arr);
		}else{
			//echo '自己没有店铺';
		}
		//==================
		if(!empty($financeInfo['store_id'])){
			$storeId=$financeInfo['store_id'];
			$storeInfo=$store->where(array('id'=>$storeId))->find(); //订购者的父亲级
			//考虑到父id可能在后面取消了店主资格，所以再做一次判断，如果不是店主，不给他返利。
			if(($storeInfo['status']==1)){
			//给id=storeId的分销商返利结算
				$arr1['id'] = $storeId;
				$arr1['income'] = $storeInfo['income']+$financeInfo['price']*$setInfo['level1']/100;
				//这里的属于直推销售额
				$arr1['directlySale'] = $storeInfo['directlySale']+$financeInfo['price'];
				$arr1['source'] = 1;
				$res1=$store->save($arr1);
				//把销售额的细节存起来，方便以后当日记查看。
				if($res1){
					$data1['token'] = $this->token;
					$data1['storeid'] = $storeId;
					$data1['wecha_id'] = $storeInfo['wecha_id'];
					$data1['orderid'] = $financeInfo['orderid'];
					$data1['directlySale'] = $financeInfo['price'];
					$data1['status'] = 0; //未重置清空
					$data1['createtime'] = time();
					$result1 = M('sale_details')->add($data1);
				}
			}
			//给id=storeId的fid分销商返利结算	
			if(!empty($storeInfo['fid'])){
				$storeInfoFid=$store->where(array('id'=>$storeInfo['fid']))->find();
				if(($storeInfoFid['status']==1)){
					$arr2['id'] =$storeInfoFid['id'];
					$arr2['income'] =$storeInfoFid['income']+ $financeInfo['price']*$setInfo['level2']/100;
					//这里的属于散下销售额
					$arr2['indirectlySale'] = $storeInfoFid['indirectlySale']+$financeInfo['price'];
					$arr2['source'] = 1;
					$res2=$store->save($arr2);
					//把销售额的细节存起来，方便以后当日记查看。
					if($res2){
						$data2['token'] = $this->token;
						$data2['storeid'] = $storeInfoFid['id'];
						$data2['wecha_id'] = $storeInfoFid['wecha_id'];
						$data2['orderid'] = $financeInfo['orderid'];
						$data2['indirectlySale'] = $financeInfo['price'];
						$data2['status'] = 0; //未重置清空
						$data2['createtime'] = time();
						$result2 = M('sale_details')->add($data2);
					}
				}
			}
			//给id=storeId的gid分销商返利结算(如果把自销看成一级，这就属于第四级分化返利了)
			if(!empty($storeInfo['gid'])){
				$storeInfoGid=$store->where(array('id'=>$storeInfo['gid']))->find();
				if(($storeInfoGid['status']==1)){
					$arr3['id'] = $storeInfoGid['id'];
					$arr3['income'] = $storeInfoGid['income']+ $financeInfo['price']*$setInfo['level3']/100;
					//这里的属于散下销售额
					$arr3['indirectlySale'] = $storeInfoGid['indirectlySale']+$financeInfo['price'];
					$arr3['source'] = 1;
					$res3=$store->save($arr3);
					//把销售额的细节存起来，方便以后当日记查看。
					if($res3){
						$data3['token'] = $this->token;
						$data3['storeid'] = $storeInfoGid['id'];
						$data3['wecha_id'] = $storeInfoGid['wecha_id'];
						$data3['orderid'] = $financeInfo['orderid'];
						$data3['indirectlySale'] = $financeInfo['price'];
						$data3['status'] = 0; //未重置清空
						$data3['createtime'] = time();
						$result3 = M('sale_details')->add($data3);
					}
				}
			}
			//=================================
			if($res1){
				$data['id'] = $id;
				$data['moneystatus'] = 1;//已结算
				$data['moneytime'] = time();
				$back = M('product_cart')->save($data);
			}
			else{
				$this->error('保存失败',U('Distribution/finance',array('token'=>$this->token)));
			}
			
		}
		if($back){
			$this->redirect('Distribution/finance', array('token' => $this->token));
		}
	}
	//用户结算 把钱从用户小钱包打到支付宝之后的记录
	public function getMoney(){
		$map['token']=$this->token;
		$map['status']=1;
		/* if($_GET['id']){
			$map['id']=$_GET['id'];
		} */
		$count      = M('store')->where($map)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$storeInfo=M('store')->where($map)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");		
		$this->assign('page',$show);
		$this->assign('storeInfo',$storeInfo);
		$this->assign('vipMap',$vipMap);
		$this->display();
	}
	//结算处理
	public function moneyDeal(){
		$map['token']=$this->token;
		$map['id']=$_GET['id'];
		$storeInfoSingle=M('store')->where($map)->find();
		$this->assign('storeInfoSingle',$storeInfoSingle);
		//=======
		
		if (IS_POST){
			if($_POST['extract']>$storeInfoSingle['income']){$this->error('超出结算金额！');}
			if(empty($_POST['extract'])){$this->error('请输入大于0的值！');}
			$store=M('store');
			$store->startTrans();	//启用事务
			$flag=0;
			$data['id'] = $storeInfoSingle['id'];
			$data['extract'] =$storeInfoSingle['extract']+$_POST['extract'];
			$data['income'] =$storeInfoSingle['income']-$_POST['extract'];
			
			$result=$store->save($data);
			if($result){
				$arr['store_id'] = $storeInfoSingle['id'];	
				$arr['token'] = $this->token;		
				$arr['wecha_id'] = $storeInfoSingle['wecha_id'];		
				$arr['username'] = $storeInfoSingle['username'];		
				$arr['money'] = $_POST['extract'];  
				$arr['payment'] = $_POST['payment']; 
				$arr['time'] = time();
				$back = M('finance')->add($arr);
				if($back){
					$store->commit();
					$flag=1;
				}
				else{
					$store->rollback();
				}
				if($flag){
					$this->success('操作成功');
				}				
			}else{
				$this->error('操作失败');
			}
		}
		$this->display();
	}
	//结算详细
	public function moneyDetail(){
		$wbMap = M('Dictionary')->where("type='weibi'")->getField("keyvalue,keyname");	
		
		if($_GET['id']){
			$where['storeid']=$_GET['id'];
		} 
		if($_GET['statdate']&&$_GET['enddate']){
			$statdate=$_GET['statdate'];
			$enddate=$_GET['enddate'];
			$stat=strtotime($statdate);
			$end=strtotime($enddate);
			$where['recordtime'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间	
		}
		//$where['token']=$this->token;
		$where['status']=array('neq',2);
		$count      = M('rebate_record')->where($where)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$moneyDetail=M('rebate_record')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$this->assign('page',$show);
		$this->assign('moneyDetail',$moneyDetail);
		$this->assign('wbMap',$wbMap);
		$this->display();
	}
	//微币明细导出
	public function moneyDetailReport(){
		if($_GET['id']){
			$where['storeid']=$_GET['id'];
		} 
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
		$wbMap = M('Dictionary')->where("type='weibi'")->getField("keyvalue,keyname");	
		
		
		foreach ($moneyDetail as &$v){
			
			$v['type'] =$wbMap[$v['type']];	
			$v['recordtime'] = $v['recordtime'] ? date('Y-m-d H:i:s', $v['recordtime']) : '';
    	}		
		$tool = new ExcelUtils();
    	$tool->push($moneyDetail, 'recordtime,storeid,type,noincome', '时间,店铺ID,类别,微币', 
    			'微币明细表'.date("YmdHis"), '', array(25,15,15,15));
		
		$this->display();
	}
	//个人财务
	public function myFinance(){
		//if(session('uid')!='105'){$this->error('你没有权限操作该页面！',U('Distribution/index',array('token'=>$this->token)));}
		$where['token']=$this->token;
		$where['status']=1;
		/* if($_GET['id']){
			$where['id']=$_GET['id'];
		} */
		
		if($_GET['searchkey']){
			//$key = $this->_get('searchkey');
			$key = $_GET['searchkey'];
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			$where['id|username'] = array('like',"%$key%");
			
		}
		if($_GET['vip']>-1){
			
			$where['vip'] = $_GET['vip'];
				
		}
		
		if($_GET['statdate']&&$_GET['enddate']){
			$statdate=$_GET['statdate'];
			$enddate=$_GET['enddate'];
			$stat=strtotime($statdate);
			$end=strtotime($enddate);
			$where['createtime'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间	
		}
		$count      = M('store')->where($where)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$storeInfo=M('store')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");		
		$this->assign('page',$show);
		$this->assign('storeInfo',$storeInfo);
		$this->assign('vipMap',$vipMap);
		$this->display();
	}
	//个人微币增加
	public function addIncome(){
		$map['token']=$this->token;
		$map['id']=$_GET['id'];
		$storeInfoSingle=M('store')->where($map)->find();
		$this->assign('storeInfoSingle',$storeInfoSingle);
		
		if (IS_POST){
			if(empty($_POST['extract'])){$this->error('请输入大于0的值！');}
			$store=M('store');
			$store->startTrans();	//启用事务
			$flag=0;
			$data['id'] = $storeInfoSingle['id'];
			$data['income'] =$storeInfoSingle['income']+$_POST['extract'];
			$result=$store->save($data);
			if($result){
				//微币明细查询
				$record['storeid'] = $storeInfoSingle['id'];
				$record['noincome'] = $_POST['extract'];  
				$record['type'] = 3;//电脑
				$record['orderid'] = $_POST['remark']; //使用orderid字段来当备注字段
				$record['recordtime'] = time();
				$back=M('rebate_record')->add($record);
				
				if($back){
					$store->commit();
					$flag=1;
				}
				else{
					$store->rollback();
				}
				if($flag){
					$this->success('操作成功');
				}				
			}else{
				$this->error('操作失败');
			}
		}
		$this->display();
	}
	//个人微币扣除
	public function cutIncome(){
		$map['token']=$this->token;
		$map['id']=$_GET['id'];
		$storeInfoSingle=M('store')->where($map)->find();
		$this->assign('storeInfoSingle',$storeInfoSingle);
		
		if (IS_POST){
			if($_POST['extract']>$storeInfoSingle['income']){$this->error('超出个人拥有数！');}
			if(empty($_POST['extract'])){$this->error('请输入大于0的值！');}
			$store=M('store');
			$store->startTrans();	//启用事务
			$flag=0;
			$data['id'] = $storeInfoSingle['id'];
			$data['extract'] =$storeInfoSingle['extract']+$_POST['extract'];
			$data['income'] =$storeInfoSingle['income']-$_POST['extract'];
			
			$result=$store->save($data);
			if($result){
				$arr['store_id'] = $storeInfoSingle['id'];	
				$arr['token'] = $this->token;		
				$arr['wecha_id'] = $storeInfoSingle['wecha_id'];		
				$arr['username'] = $storeInfoSingle['username'];		
				$arr['cutdown'] = $_POST['extract'];  
				$arr['remark'] = $_POST['remark'];
				$arr['time'] = time();
				$back = M('finance')->add($arr);
				//微币明细查询
				$record['storeid'] = $storeInfoSingle['id'];
				$record['noincome'] = -$_POST['extract'];  
				$record['type'] = 3;//电脑
				$record['orderid'] = $_POST['remark']; //使用orderid字段来当备注字段
				$record['recordtime'] = time();
				M('rebate_record')->add($record);
				
				if($back){
					$store->commit();
					$flag=1;
				}
				else{
					$store->rollback();
				}
				if($flag){
					$this->success('操作成功');
				}				
			}else{
				$this->error('操作失败');
			}
		}
		$this->display();
	}
	//个人财务处理（下面方法myFinanceDeal()已丢弃不使用）
	public function myFinanceDeal(){
		//if(session('uid')!='105'){$this->error('你没有权限操作该页面！',U('Distribution/index',array('token'=>$this->token)));}
		$map['token']=$this->token;
		$map['id']=$_GET['id'];
		$storeInfoSingle=M('store')->where($map)->find();
		$this->assign('storeInfoSingle',$storeInfoSingle);
		//个人的支付宝，银行卡信息
		$userInfo=M('userinfo')->where(array('token'=>$this->token,'wecha_id'=>$storeInfoSingle['wecha_id']))->find();
		$this->assign('userInfo',$userInfo);
		//=======
		
		if (IS_POST){
			if($_POST['extract']>$storeInfoSingle['income']){$this->error('超出结算金额！');}
			if(empty($_POST['extract'])){$this->error('请输入大于0的值！');}
			$store=M('store');
			$store->startTrans();	//启用事务
			$flag=0;
			$data['id'] = $storeInfoSingle['id'];
			$data['extract'] =$storeInfoSingle['extract']+$_POST['extract'];
			$data['income'] =$storeInfoSingle['income']-$_POST['extract'];
			
			$result=$store->save($data);
			if($result){
				$arr['store_id'] = $storeInfoSingle['id'];	
				$arr['token'] = $this->token;		
				$arr['wecha_id'] = $storeInfoSingle['wecha_id'];		
				$arr['username'] = $storeInfoSingle['username'];		
				$arr['money'] = $_POST['extract'];  
				$arr['payment'] = $_POST['payment'];
				$arr['zhifubao'] = $_POST['zhifubao']; 	
				$arr['bankcard'] = $_POST['bankcard']; 	
				$arr['transactionNo'] = $_POST['transactionNo'];
				$arr['time'] = time();
				$back = M('finance')->add($arr);
				if($back){
					$store->commit();
					$flag=1;
				}
				else{
					$store->rollback();
				}
				if($flag){
					$this->success('操作成功');
				}				
			}else{
				$this->error('操作失败');
			}
		}
		$this->display();
	}
	//个人销售
	public function mySale(){
		//if(session('uid')!='105'){$this->error('你没有权限操作该页面！',U('Distribution/index',array('token'=>$this->token)));}
		$where['token']=$this->token;
		$where['status']=1;
		/* if($_GET['id']){
			$where['id']=$_GET['id'];
		} */
		if($_GET['searchkey']){
			//$key = $this->_get('searchkey');
			$key = $_GET['searchkey'];
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			$where['id|username'] = array('like',"%$key%");
			
		}
		if($_GET['vip']>-1){
			
			$where['vip'] = $_GET['vip'];
				
		}
		$count      = M('store')->where($where)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$storeInfo=M('store')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		/*
		*天使银章资格：直1消费9000元，广告圈10万元 
		*天使金章资格：直1消费9万元，广告圈200万元
		*天使银章资格：直1消费18万元，广告圈500万元
		*/
		$y1='9000';$y2='100000';
		$j1='90000';$j2='2000000';
		$z1='180000';$z2='5000000';
		//========
		foreach($storeInfo as &$v){

			if($v['newDirectlySale']<=$y1||$v['newAdCountSale']<=$y2){
				$reward='没有';
			}
			elseif($y1<$v['newDirectlySale']||$y2<$v['newAdCountSale']){
				$reward='银章';
			}
			
			elseif($j1<$v['newDirectlySale']||$j2<$v['newAdCountSale']){
				$reward='金章';
			}
			elseif($z1<$v['newDirectlySale']||$z2<$v['newAdCountSale']){
				$reward='钻章';
			}
			$v['reward']=$reward;
		}
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");		
		$this->assign('page',$show);
		$this->assign('storeInfo',$storeInfo);
		$this->assign('vipMap',$vipMap);
		$this->display();
	}
	//销售奖励兑换
	public function saleReward(){
		//exit('功能未完善。');
		$store=M('store');
		$id=$_GET['id'];
		if(empty($id)){$this->error('参数出错!');}
		$storeInfo=$store->where(array('id'=>$id))->field('newDirectlySale,newAdCountSale')->find();
		Log::write("天使奖励兑换前直1新增和广告圈新增是：".json_encode($storeInfo), Log::DEBUG, 3, LOG_PATH.'mysale.log');
		$where = array('id' => $id, 'token' => $this->token); //'saleStatus'=>0
        $data['saleTime'] = time(); //销售额兑换奖品时间 
		$data['newDirectlySale'] = 0; //年终销售额
		$data['newAdCountSale'] = 0; //年终销售额
        $data['saleStatus'] = 1; //已经兑换过了
        $result = $store->where($where)->save($data);
		
        if ($result) {	
			$this->success('操作成功');
        } else {
            $this->error('操作失败！');
        }	
	}
	//销售重置
	public function saleReset(){
		exit('功能未完善。');
		$store=M('store');
		$store->startTrans();	//启用事务
		$flag=0;
		if($_GET['id']){
			$id=$_GET['id'];
		}
		
		$where = array('id' => $id, 'token' => $this->token);
		$data['directlySale'] = 0;//清空直推销售额
		$data['indirectlySale'] = 0;//清空散下销售额
        $data['saleStatus'] = 0; //年终销售额 0未兑换  1已兑换 奖品  重置之后，修改为未兑换
		$data['saleTime'] = '';  //清空兑换时间
        $result = $store->where($where)->save($data);
		
        if($result){
			$arr['saleStatus'] = 2;	//0 未兑换 1已兑换 2重置	
			$arr['teamtime'] = time(); //团队时间
			$back = M('sale_details')->where(array('storeid'=>$id,'saleStatus'=>0))->save($arr);
			if($back!==false){
				$store->commit();
				$flag=1;
			}
			else{
				$store->rollback();
			}
			if($flag){
				$this->success('操作成功');
			}else{
				$this->error('操作失败');
			}				
		}else{
			$store->rollback();
			$this->error('服务器繁忙，请稍后再尝试！');
		}
	}
	//导出销售报表
	public function saleReport(){
		//if(session('uid')!='105'){$this->error('你没有权限操作该页面！',U('Distribution/index',array('token'=>$this->token)));}
		$where['token']=$this->token;
		$where['status']=1;
		/* if($_GET['id']){
			$where['id']=$_GET['id'];
		} */
		if($_GET['searchkey']){
			//$key = $this->_get('searchkey');
			$key = $_GET['searchkey'];
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			$where['id|username'] = array('like',"%$key%");
			
		}
		if($_GET['vip']>-1){
			
			$where['vip'] = $_GET['vip'];
				
		}
		$list=M('store')->where($where)->order('id desc')->field('id,username,telphone,vip,directlySale,j1CountSale,j2CountSale,adCountSale,newAdCountSale,saleStatus,saleTime')->select();
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");	
		/*
		*天使银章资格：直1消费9000元，广告圈10万元 
		*天使金章资格：直1消费9万元，广告圈200万元
		*天使银章资格：直1消费18万元，广告圈500万元
		*/
		$y1='9000';$y2='100000';
		$j1='90000';$j2='2000000';
		$z1='180000';$z2='5000000';
		//========
		foreach ($list as &$v){
			
			$v['vip'] =$vipMap[$v['vip']];
			
			if($v['newDirectlySale']<=$y1||$v['newAdCountSale']<=$y2){
				$reward='没有';
			}
			elseif($y1<$v['newDirectlySale']||$y2<$v['newAdCountSale']){
				$reward='银章';
			}
			
			elseif($j1<$v['newDirectlySale']||$j2<$v['newAdCountSale']){
				$reward='金章';
			}
			elseif($z1<$v['newDirectlySale']||$z2<$v['newAdCountSale']){
				$reward='钻章';
			}
			$v['reward']=$reward;

    	}
		$tool = new ExcelUtils();
    	$tool->push($list, 'id,username,telphone,vip,directlySale,j1CountSale,j1CountSale,adCountSale,newAdCountSale,reward,', '店铺ID,店主,电话,会员等级,直1消费圈（累积）,间1消费圈,间2消费圈,广告圈（累积）,广告圈（新增）,天使勋章', 
    			'销售报表'.date("YmdHis"), '', array(10,15,15,15,15,15,15,15,15,10,20));
	
	}
	
	//提现申请
	public function withdrawMoney(){
		//if(session('uid')!='105'){$this->error('你没有权限操作该页面！',U('Distribution/index',array('token'=>$this->token)));}
		$where['token']=$this->token;
		if($_GET['searchkey']){
			//$key = $this->_get('searchkey');
			$key = $_GET['searchkey'];
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			$where['truename'] = array('like',"%$key%");
			
		}
		if($_GET['status']>-1){
			
			$where['status'] = $_GET['status'];
				
		}
		if($_GET['statdate']&&$_GET['enddate']){
			$statdate=$_GET['statdate'];
			$enddate=$_GET['enddate'];
			$stat=strtotime($statdate);
			$end=strtotime($enddate);
			$where['createtime'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间	
		}
		
		$count      = M('withdraw_details')->where($where)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$withdrawInfo=M('withdraw_details')->where($where)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		//echo M('withdraw_details')->getLastSql();
		$this->assign('page',$show);
		$this->assign('withdrawInfo',$withdrawInfo);
		$this->display();
	}
	//体现申请记录导出
	public function withdrawReport(){
		//if(session('uid')!='105'){$this->error('你没有权限操作该页面！',U('Distribution/index',array('token'=>$this->token)));}
		$where['token']=$this->token;
		if($_GET['searchkey']){
			//$key = $this->_get('searchkey');
			$key = $_GET['searchkey'];
			if(empty($key)){
				$this->error("关键词不能为空");
			}
			$where['truename'] = array('like',"%$key%");
			
		}
		if($_GET['status']>-1){
			
			$where['status'] = $_GET['status'];
				
		}
		if($_GET['statdate']&&$_GET['enddate']){
			$statdate=$_GET['statdate'];
			$enddate=$_GET['enddate'];
			$stat=strtotime($statdate);
			$end=strtotime($enddate);
			$where['createtime'] = array(array('gt',$stat),array('lt',$end),'and');//时间在在$stat---$end之间	
		}
		$withdrawInfo=M('withdraw_details')->where($where)->order('id desc')->select();	
		foreach ($withdrawInfo as &$item){

    		switch ($item['withdrawWay'])
			{
				case 0:
				$item['withdrawWay'] ='支付宝';
				$item['account']=$item['zhifubao'];
				break;
				case 1:
				$item['withdrawWay'] ='银行卡';
				$item['account']=$item['withdrawWay'];
				break;
				
			}
			switch ($item['status'])
			{
				case 0:
				$item['status'] ='未处理';
				break;
				
				case 1:
				$item['status'] ='成功';
				break;
				
				case 2:
				$item['status'] ='失败';
				break; 
			}
			
    		$item['createtime'] = $item['createtime'] ? date('Y-m-d H:i:s', $item['createtime']) : '';
			$item['handletime'] = $item['handletime'] ? date('Y-m-d H:i:s', $item['handletime']) : '';
    	}
		$tool = new ExcelUtils();
    	$tool->push($withdrawInfo, 'store_id,truename,tel,bankname,subbankname,bankcard,money,status,createtime,transactionNo,handletime', '店铺ID,收款人姓名,手机号码,开户银行,开户银行所属分行,账号/卡号,兑换金额(元),状态,申请时间,交易号,处理时间', 
    			'提现申请数据'.date("YmdHis"), '', array(10,10,15,20,20,25,15,15,25,20,25));
	}
	//提现申请处理
	public function withdrawDeal(){
		//if(session('uid')!='105'){$this->error('你没有权限操作该页面！',U('Distribution/index',array('token'=>$this->token)));}
		$map['token']=$this->token;
		$map['id']=$_GET['id'];
		$withdrawInfo=M('withdraw_details')->where($map)->find();
		$this->assign('withdrawInfo',$withdrawInfo);
		$payWayMap = M('Dictionary')->where("type='pay_way'")->getField("keyvalue,keyname");		
		$this->assign('payWayMap',$payWayMap);
		//个人财务信息
		$where = array('id' => $withdrawInfo['store_id'], 'token' => $this->token);
		$storeInfo=M('store')->where($where)->find();
		$this->assign('storeInfo',$storeInfo);
		//微币兑换成现金数量
		//读取配置表
		$setInfo=M('distribution_set')->where(array('token'=>$this->token))->find();
		if(empty($setInfo)){$this->error('商家未设置兑换比例，请稍后再试',U('Distribution/set',array('token'=>$this->token)));exit;}
		//===============
		$cash=$this->storeInfo['income']/$setInfo['cash'];
		$this->assign('cash',$cash);
		//============
		if (IS_POST){
			/* if($withdrawInfo['status']!=1){
				$this->error('该提现申请已经处理过了');exit
			} */
			if($withdrawInfo['status'] != 0){$this->error('该提现申请已经处理过了');exit;}
			$withdrawDb=M('withdraw_details');
			$id = $_GET['id'];
			//提现成功，不需额外操作。提现失败，需要返还申请提现的微币
			if($_POST['status']==1){
				$jieguo='提现成功';
				$remark='提现成功,交易号是：'.$_POST['transactionNo'].'\\n以具体到账时间为准';
				$data['id'] =$id;
				$data['transactionNo'] =$_POST['transactionNo'];
				$data['status'] =1;
				$data['handletime'] =time();
				$result=$withdrawDb->save($data);
				if($result){
					//微币明细查询
					$record['storeid'] = $withdrawInfo['store_id'];
					$record['noincome'] = $withdrawInfo['extract'];  
					$record['type'] = 5;//提现
					//$record['orderid'] = $_POST['remark']; //使用orderid字段来当备注字段
					$record['recordtime'] = time();
					$back=M('rebate_record')->add($record);
					$this->success('操作成功');
				}
				else{
					$this->error('操作失败');
				}
			}else{
				$jieguo='提现失败';
				$remark='提现失败,理由是：'.$_POST['reason'];
				$data['id'] =$id;
				$data['reason'] =$_POST['reason'];
				$data['status'] =2;
				$data['handletime'] =time();
				$result=$withdrawDb->save($data);
				if($result){
					//提现失败，需要返还申请提现的微币
					$arr['id'] = $storeInfo['id'];	
					$arr['token'] = $this->token;			
					$arr['income'] = $storeInfo['income']+$withdrawInfo['extract'];		
					$back = M('store')->save($arr);	
					if($back){
						$this->success('操作成功');
					}
					else{
						$this->error('操作失败');
					}	
				}
			}
			//推送模板消息   提现信息$withdrawInfo
			//=========
			$url='https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=';
			$str=array('first'=>array('value'=>urlencode("恭喜您，您的提现申请已经审核！"),'color'=>"#173177"),'keyword1'=>array('value'=>urlencode($withdrawInfo["bankname"]),'color'=>"#FF0000"),'keyword2'=>array('value'=>urlencode($withdrawInfo["bankcard"]),'color'=>"#C4C400"),'keyword3'=>array('value'=>urlencode($withdrawInfo["money"]),'color'=>"#0000FF"),'keyword4'=>array('value'=>urlencode(date("Y-m-d H:i:s",$withdrawInfo["createtime"])),'color'=>"#0000FF"),'keyword5'=>array('value'=>urlencode($jieguo),'color'=>"#0000FF"),'remark'=>array('value'=>urlencode($remark),'color'=>"#008000"));
			$access_token =$this->getAccessTokenJiesuan();
			$data='{"touser":"'.$withdrawInfo['wecha_id'].'","template_id":"9rxZEhwNjENNowBmSyw9QNibZLD76z8dWlh2lJ-gy-0","url":"","topcolor":"#FF0000","data":'.urldecode(json_encode($str)).'}';
			$url=$url.$access_token;
			//$url=$url.$access_token['info'];
			$this->api_notice_increment($url,$data);
			//============
	
		}else{
			$this->display();
		}
		
	}
	//提现申请查看
	public function withdrawShow(){
		//if(session('uid')!='105'){$this->error('你没有权限操作该页面！',U('Distribution/index',array('token'=>$this->token)));}
		$map['token']=$this->token;
		$map['id']=$_GET['id'];
		$withdrawInfo=M('withdraw_details')->where($map)->find();
		$this->assign('withdrawInfo',$withdrawInfo);
		//==
		$payWayMap = M('Dictionary')->where("type='pay_way'")->getField("keyvalue,keyname");		
		$this->assign('payWayMap',$payWayMap);
		//===
		$withdrawMap = M('Dictionary')->where("type='withdraw_status'")->getField("keyvalue,keyname");		
		$this->assign('withdrawMap',$withdrawMap);
		//个人财务信息
		$where = array('id' => $withdrawInfo['store_id'], 'token' => $this->token);
		$storeInfo=M('store')->where($where)->find();
		$this->assign('storeInfo',$storeInfo);
		//微币兑换成现金数量
		//读取配置表
		$setInfo=M('distribution_set')->where(array('token'=>$this->token))->find();
		if(empty($setInfo)){$this->error('商家未设置兑换比例，请稍后再试',U('Distribution/set',array('token'=>$this->token)));exit;}
		//===============
		$cash=$this->storeInfo['income']/$setInfo['cash'];
		$this->assign('cash',$cash);
		//============
		$this->display();	
	}
	//签到设置
	public function signIn(){
		$setModel=D('Sign_in');
		$setInfo=$setModel->where(array('token'=>$this->token))->find();
		if(IS_POST){
			if($setModel ->create()){
				if(empty($setInfo)){
					//执行add（）
					$res=$setModel->add();	
				}
				else{
					//执行save
					$data['id']=$setInfo['id'];
					$res=$setModel->save();	
				}
				if($res){
					$this->success("操作成功",U('Distribution/signIn',array('token'=>$this->token)));
					//echo M('distribution_set')->getLastSql();
				}
				else{		
					$this->error("操作失败,请确认输入的值是否有改动",U('Distribution/signIn',array('token'=>$this->token)));
				}
			}else{
                $this->error($setModel->getError());
            }
		}
		else{
			$this->assign('setInfo',$setInfo);
			$this->display();
		}
		
	}
	/*
	*会员等级升级申请
	*2015年3月25日15:16:17
	*/
	public function upvip(){
		$map['token']=$this->token;
		$count      = M('upvip')->where($map)->count();
		$Page       = new Page($count,20);
		$show       = $Page->show();
		$upvipInfo=M('upvip')->where($map)->limit($Page->firstRow.','.$Page->listRows)->order('createtime desc')->select();
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
		$this->assign('vipMap',$vipMap);
		$this->assign('page',$show);
		$this->assign('upvipInfo',$upvipInfo);
		$this->display();
	}
	public function upvipDeal(){
		$map['token']=$this->token;
		$map['id']=$_GET['id'];
		$upvipInfo=M('upvip')->where($map)->find();
		//会员字典映射
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
		$this->assign('vipMap',$vipMap);
		//查询店主的具体信息
		$condition['token']=$this->token;
		$condition['id']=$upvipInfo['store_id'];
		$condition['wecha_id']=$upvipInfo['wecha_id'];
		
		$storeInfo=M('Store')->where($condition)->find();
		$this->assign('upvipInfo',$upvipInfo);
		$this->assign('storeInfo',$storeInfo);
		//查询该订单的具体信息
		$where['orderid']=$upvipInfo['orderid'];
		$where['wecha_id']=$upvipInfo['wecha_id'];
		$orderInfo=M('product_cart')->where($where)->find();
		$this->assign('orderInfo',$orderInfo);
		//
		if (IS_POST){
			$data['id'] = $_GET['id'];
			$data['status'] =$_POST['status'];          //1 通过  2拒绝
			//$data['upvip'] = $upvipInfo['upvip'];       //申请等级
			$data['reason'] = $_POST['reason'];         // 拒绝理由
			$data['handletime'] = time();	
			//p($data);
			//exit;	
			$result=M('upvip')->save($data);
			if($result){
				//申请会员等级提升成功，改变会员等级
				if ($_POST['status']==1){
					$arr['id'] = $upvipInfo['store_id'];
					$arr['token'] = $this->token;			
					$arr['vip'] = $upvipInfo['upvip'];	
					$back = M('store')->save($arr);	
					if($back){
						$this->success('操作成功');
					}
					else{
						$this->error('操作失败');
					}	
				}else{
					$this->success('操作成功');	
				}
			}else{
				$this->error('操作失败');
			}
		}
		$this->display();
	}
	public function upvipShow(){
		
		$map['id']=$_GET['id'];
		$map['token']=$this->token;
		$upvipInfo=M('upvip')->where($map)->find();	
		$this->assign('upvipInfo',$upvipInfo);
		//会员字典映射
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
		$this->assign('vipMap',$vipMap);
		
		$applyMap = M('Dictionary')->where("type='apply_result'")->getField("keyvalue,keyname");
		$this->assign('applyMap',$applyMap);
		//查询店主的具体信息
		$condition['token']=$this->token;
		$condition['id']=$upvipInfo['store_id'];
		$condition['wecha_id']=$upvipInfo['wecha_id'];
		$storeInfo=M('Store')->where($condition)->find();
		$this->assign('storeInfo',$storeInfo);
		$this->display();
	}
	/*2015年4月15日 20:26:30*/
	//用户关系更改
	public function changeMap(){
		//p(session('uid'));
		$id=$_GET['id'];
		if(empty($id)){$this->error('操作失败');}
		else{
			if(IS_POST){
				p($_POST['changeFid']);
				$this->success('操作成功');
			}
			$store=M('store')->field("id,nickname,username,fid,boss")->where(array('id'=>$id,'token'=>$this->token))->find();
			if($store['boss']==1){$this->error('是总代理，不能更改上下级关系！',U('Distribution/index',array('token'=>$this->token)));}
			$storeFid=M('store')->field("id,nickname,username")->where(array('id'=>$store['fid'],'token'=>$this->token))->find();
			$map['token']=$this->token;
			$map['status']=1;
			$map['id']=array('neq',$id);
			$storeAll=M('store')->field("id,nickname,username")->where($map)->select();
			$this->assign('store',$store);
			$this->assign('storeFid',$storeFid);
			$this->assign('storeAll',$storeAll);
		}
		$this->display();
	}
	//查看父级
	public function showFather(){
		//$this->token='mutdij1427425591';
		$storeModel=M('store');
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
		$this->assign('vipMap',$vipMap);
		//$storeModel=M('store');
		$id=$_GET['id'];
		$store=$storeModel->where(array('id'=>$id,'token'=>$this->token))->find();
		/*
		*天使银章资格：直1消费9000元，广告圈10万元 
		*天使金章资格：直1消费9万元，广告圈200万元
		*天使银章资格：直1消费18万元，广告圈500万元
		*/
		$y1='9000';$y2='100000';
		$j1='90000';$j2='2000000';
		$z1='180000';$z2='5000000';
		if($store['newDirectlySale']<=$y1||$store['newAdCountSale']<=$y2){
			$reward='没有';
		}
		elseif($y1<$store['newDirectlySale']||$y2<$store['newAdCountSale']){
			$reward='银章';
		}
		
		elseif($j1<$store['newDirectlySale']||$j2<$store['newAdCountSale']){
			$reward='金章';
		}
		elseif($z1<$store['newDirectlySale']||$z2<$store['newAdCountSale']){
			$reward='钻章';
		}
		$store['reward']=$reward;
		//==================
		$this->assign('store',$store);
		if(empty($id)||empty($store)){
			$this->error('操作失败');
		
		}else{
			//此ID的父是$store['fid'],上上级$store['gid'],上上上级$store['ggid']
			$parent['fid'] = $store['fid'];
			$parents = array();
			while(!empty($parent['fid'])){
				if($parents[$parent['fid']]){
					echo 'id:'.$parent['id'].'  fid:'.$parent['fid'];break;
				}
				$parent = $storeModel->where(array('id'=>$parent['fid'],'token'=>$this->token))->find();
				if($parent['newDirectlySale']<=$y1||$parent['newAdCountSale']<=$y2){
					$reward='没有';
				}
				elseif($y1<$parent['newDirectlySale']||$y2<$parent['newAdCountSale']){
					$reward='银章';
				}
				
				elseif($j1<$parent['newDirectlySale']||$j2<$parent['newAdCountSale']){
					$reward='金章';
				}
				elseif($z1<$parent['newDirectlySale']||$z2<$parent['newAdCountSale']){
					$reward='钻章';
				}
				$parent['reward']=$reward;
				//==================
				$parents[$parent['id']] = $parent;
			}
			$this->assign('parents',$parents);
			
		}
		$this->display();
	}
	//按照ID来查询上级信息
	public function searchFather(){
		//$this->token='mutdij1427425591';
		//$storeModel=M('store2');
		$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");
		$this->assign('vipMap',$vipMap);
		$storeModel=M('store');
		if(IS_POST){
			$id=$_POST['searchkey'];
			$store=$storeModel->where(array('id'=>$id,'token'=>$this->token))->find();
			$this->assign('store',$store);
			if(empty($store)){
				$this->error('该ID的用户不存在！');
			}else{
				//此ID的父是$store['fid'],上上级$store['gid'],上上上级$store['ggid']
				$storeFid=$storeModel->where(array('id'=>$store['fid'],'token'=>$this->token))->find();
				$storeGid=$storeModel->where(array('id'=>$storeFid['fid'],'token'=>$this->token))->find();
				$storeGGid=$storeModel->where(array('id'=>$storeGid['fid'],'token'=>$this->token))->find();
				$this->assign('storeFid',$storeFid);
				$this->assign('storeGid',$storeGid);
				$this->assign('storeGGid',$storeGGid);	
			}
		}
		$this->display();
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