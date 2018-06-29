<?php
class CustomerServiceAction extends UserAction{
	public $isTel;
	public function _initialize() {
		parent::_initialize();
		$this->token= session('token');
		$this->assign('token',$this->token);
		
		if (isset($_GET['tel'])&&intval($_GET['tel'])){
			$this->isTel=1;
		}else {
			$this->isTel=0;
		}
		$this->assign('isTel',$this->isTel);
	}
	public function index(){
		
		$this->display();	
	}
	public function telService(){
		$serviceDb=D('tel_service');
		$where['token']=session('token');
		$where['cat']=$this->isTel;
		$telService=M('Tel_service')->where($where)->select();
		$count=count($telService);
		$this->assign('telService',$telService);
		$this->assign('num',$count);
		$this->display();	
	}
	//添加客服处理
	
	public function runAdd(){

		$serviceDb = D('Tel_service');
		if(!$serviceDb->create()){
			$this->error($serviceDb->getError());
		}else{
			$id=$serviceDb->add();
			if($id){
				$this->success('操作成功',U('CustomerService/telService',array('token'=>session('token'))));
			}else{
				$this->error('操作失败',U('CustomerService/telService',array('token'=>session('token'))));
			}
		}
	}
	public function edit(){
		$id=$this->_get('id','intval');
		$where['id']=$id;
		$where['token']=session('token');
		//$where['token']=$this->_get('token','trim');
		$telService=M('Tel_service')->where($where)->find();
		$this->assign('telService',$telService);
		$this->assign('id',$id);
		$this->display();
	}
	public function runEdit(){
		$serviceDb = D('Tel_service');
		if(IS_POST){
			if($serviceDb->create()){
				if($serviceDb->where(array('token'=>session('token'),'id'=>$_GET['id']))->save()!=false){
					$this->success('操作成功',U('CustomerService/telService',array('token'=>session('token'))));	
				}else{
					$this->error('服务器繁忙，请稍候再试');
				}			
			}else{			
				$this->error($serviceDb->getError());
			}		
		}else{
			$this->error('非法操作');		
		
		}
	}
	//删除客服
	public function del(){
		$id = $_GET['id'];
		if(IS_GET){
			$serviceDb = D('Tel_service');
			if($serviceDb->delete($id)){
				$this->success('操作成功',U('CustomerService/telService',array('token'=>session('token'))));	
			}else{
				$this->error('删除失败');
			}
			
		}else{
			$this->error('非法操作');
		}
	}



}


?>