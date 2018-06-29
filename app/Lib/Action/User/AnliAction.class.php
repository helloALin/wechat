<?php
/**
 *发布消息中心
**/
class AnliAction extends UserAction{
	public function _initialize() {
		parent::_initialize();
	}
	public function index(){
		$AnliModel = D('Anli');
		$count=$AnliModel->count();
		$page=new Page($count,10);
		$info=$AnliModel->order('addtime DESC')->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('info',$info);
		$this->display();
	}
	public function add(){

            $AnliModel = D('Anli');
            if(!empty($_POST)){
                if($AnliModel ->create()){
                    $res = $AnliModel ->add();
                    if($res){
                        $this->success('发布成功', U('Anli/index'));
                    }
                }else{
                    $this->error($AnliModel->getError());
                }
            }else {
                $this->display();
            }
	}
	public function view(){
            $AnliModel = D('Anli');
            $where['id']=$this->_get('id','intval');
            $res=$AnliModel->where($where)->find();
            $this->assign('info',$res);
            $this->display();
	}
	public function edit(){
            $AnliModel = D('Anli');
            if(!empty($_POST)){
                $AnliModel->create();
                $res = $AnliModel ->save();
                if($res){
                       $this->success('编辑成功', U('Anli/index'));
                   }
            }else{
				$where['id']=$this->_get('id','intval');
				$res=$AnliModel->where($where)->find();
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