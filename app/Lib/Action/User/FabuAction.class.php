<?php
/**
 *发布消息中心
**/
class FabuAction extends UserAction{
	public function _initialize() {
		parent::_initialize();
	}
	public function index(){
		$FabuModel = D('Fabu');
		$count=$FabuModel->count();
		$page=new Page($count,10);
		$info=$FabuModel->order('addtime DESC')->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('info',$info);
		$this->display();
	}
	public function add(){

            $FabuModel = D('Fabu');
            if(!empty($_POST)){
                if($FabuModel ->create()){
                    $res = $FabuModel ->add();
                    if($res){
                        $this->success('发布成功', U('Fabu/index'));
                    }
                }else{
                    $this->error($FabuModel->getError());
                }
            }else {
                $this->display();
            }
	}
	public function view(){
            $FabuModel = D('Fabu');
            $where['id']=$this->_get('id','intval');
            $res=$FabuModel->where($where)->find();
            $this->assign('info',$res);
            $this->display();
	}
	public function edit(){
            $FabuModel = D('Fabu');
            if(!empty($_POST)){
                $FabuModel->create();
                $res = $FabuModel ->save();
                if($res){
                       $this->success('编辑成功', U('Fabu/index'));
                   }
            }else{
		$where['id']=$this->_get('id','intval');
		$res=$FabuModel->where($where)->find();
		$this->assign('info',$res);
		$this->display();
            }
	}
	public function del(){
		$where['id']=$this->_get('id','intval');
		if(D(MODULE_NAME)->where($where)->delete()){
			$this->success('删除成功',U(MODULE_NAME.'/index'));
		}else{
			$this->error('删除失败',U(MODULE_NAME.'/index'));
		}
	}
	public function show(){

	}

}
?>