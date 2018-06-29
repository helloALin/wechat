<?php
/**
 *关注回复
**/
class AreplyAction extends UserAction{
	public function index(){
		//========
		$id=$this->_get('id','intval');
		$token=$this->_get('token','trim');	
		$info=M('Wxuser')->find($id);
		/*	
		if($info==false||$info['token']!==$token){
			$this->error('非法操作',U('Home/Index/index'));
		}
		*/
		session('token',$token);
		session('wxid',$info['id']);
		//第一次登陆　创建　功能所有权
		$token_open=M('Token_open');
		$toback=$token_open->field('id,queryname')->where(array('token'=>session('token'),'uid'=>session('uid')))->find();
		$open['uid']=session('uid');
		$open['token']=session('token');
		//遍历功能列表
		$group=M('User_group')->field('id,name')->where('status=1')->select();
		$check=explode(',',$toback['queryname']);
		$this->assign('check',$check);
		foreach($group as $key=>$vo){
			$fun=M('Function')->where(array('status'=>1,'gid'=>$vo['id']))->select();
			foreach($fun as $vkey=>$vo){
				$function[$key][$vkey]=$vo;
			}
		}
		$this->assign('fun',$function);
		//========
		$db=D('Areply');
		$where['uid']=$_SESSION['uid'];
		$where['token']=$_SESSION['token'];
		$res=$db->where($where)->find();
		$this->assign('areply',$res);
		$this->display();
	}
	public function insert(){
		C('TOKEN_ON',false);
		$db=D('Areply');
		$where['uid']=$_SESSION['uid'];
		$where['token']=$_SESSION['token'];
		$res=$db->where($where)->find();
		if($res==false){
			$where['content']=$this->_post('content');
			if(isset($_POST['keyword'])){
				$where['keyword']=$this->_post('keyword');
			}			
			if($where['content']==false){$this->error('内容必须填写');}
			$where['createtime']=time();
			$id=$db->data($where)->add();
			if($id){
				$this->success('发布成功',U('Areply/index',array('token'=>$_SESSION['token'])));
			}else{
				$this->error('发布失败',U('Areply/index',array('token'=>$_SESSION['token'])));
			}
		}else{
			$where['id']=$res['id'];
			$where['content']=$this->_post('content');
			$where['home']=intval($this->_post('home'));
			$where['updatetime']=time();
			if(isset($_POST['keyword'])){
				$where['keyword']=$this->_post('keyword');
			}		
			if($db->save($where)){
				$this->success('更新成功',U('Areply/index',array('token'=>$_SESSION['token'])));
			}else{
				$this->error('更新失败',U('Areply/index',array('token'=>$_SESSION['token'])));
			}
		}
	}
	//支付成功之后调用此方法。自动审核成为对应等级的微店主
	public function autoStore(){
	
	$this->token='mutdij1427425591';
	$this->wecha_id='on9FcswsgewCVkNbjmX5QcswxMqc';
	//查询在关注表中是否有此用户的信息	
	$peopleInfo=M('wxuser_people')->where($where)->field('nickname,headimgurl')->find();
	//查出该openid的微店主的信息
	$this->store_model=M('Store');  //微店铺表
	$where['token']=$this->token;
	$where['wecha_id']=$this->wecha_id;
	//个人地址信息
	$pcInfo=M('address2')->where(array('token'=>$this->token,'openid'=>$this->wecha_id))->field('name,tel')->find();
	p($pcInfo);
	echo M('address2')->getLastSql();
	
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
	//$pcInfo=$product_cart_model->where($map)->field('truename,tel')->select();
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