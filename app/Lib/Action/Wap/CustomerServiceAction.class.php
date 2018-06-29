<?php
class CustomerServiceAction extends BaseAction{
	public $isTel;
	public function _initialize() {
		parent::_initialize();		
		if (isset($_GET['tel'])&&intval($_GET['tel'])){
			$this->isTel=1;
		}else {
			$this->isTel=0;
		}
		$this->assign('isTel',$this->isTel);
	}
	public function telIndex(){
		$where['token']=$this->_get('token','trim');
		$where['cat']=$this->isTel;
		$where['status']=1;
		$telService=M('Tel_service')->where($where)->select();
		$count=count($telService);
		$this->assign('telService',$telService);
		$this->assign('num',$count);
		$this->display();
	}
}

