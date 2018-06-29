<?php
class BackAction extends BaseAction{
	protected $pid;
	protected function _initialize(){
		//if(!isset($_SESSION['username'])){$this->error('非法操作',U('Admin/Admin/index'));}
		parent::_initialize();
		if (C('USER_AUTH_ON') && !in_array(MODULE_NAME, explode(',', C('NOT_AUTH_MODULE')))) {
			//获取Node表 Level=1的Name值
			$rid=($_SESSION['roleid']);
			$map['role_id']=$rid;
			$map['level']=1;
			$nid=M('Access')->where($map)->getField('node_id');

			$condition['id']=$nid;
			$name=M('Node')->where($condition)->getField('name');

            if (!RBAC::AccessDecision($name)) {
                //检查认证识别号
                if (!$_SESSION [C('USER_AUTH_KEY')]) {
                    //跳转到认证网关
                    redirect(PHP_FILE . C('USER_AUTH_GATEWAY'));
                }
                // 没有权限 抛出错误
                if (C('RBAC_ERROR_PAGE')) {
                    // 定义权限错误页面
                    redirect(C('RBAC_ERROR_PAGE'));
                } else {
                    if (C('GUEST_AUTH_ON')) {
                        $this->assign('jumpUrl', PHP_FILE . C('USER_AUTH_GATEWAY'));
                    }
                    // 提示错误信息
                    $this->error(L('_VALID_ACCESS_'));
                }
            }
        }
		$this->show_menu();
	}
	protected function _initialize111111(){

		//if(!isset($_SESSION['username'])){$this->error('非法操作',U('Admin/Admin/index'));}
//		if(!isset($_SESSION[C('USER_AUTH_KEY')])){
//			//$this->redirect('Admin/Admin/index');
//			$this->error('信息不正确，请认真填写',U('Admin/Admin/index'));
//		}
		$notAuth=in_array(MODULE_NAME, explode(',', C('NOT_AUTH_MODULE'))) || in_array(ACTION_NAME, explode(',', C('NOT_AUTH_ACTION')));

		parent::_initialize();
		if (C('USER_AUTH_ON')) {
			 import('ORG.RBAC');
			//var_dump(RBAC::AccessDecision("Hotel"));
			//获取Node表 Level=1的Name值
			$rid=($_SESSION['roleid']);
			$map['role_id']=$rid;
			$map['level']=1;
			$nid=M('Access')->where($map)->getField('node_id');

			$condition['id']=$nid;
			$name=M('Node')->where($condition)->getField('name');
			RBAC::AccessDecision($name)||$this->error(L('_VALID_ACCESS_'));
        }
		$this->show_menu();
	}

	private function show_menu(){
		$this->pid=$this->_get('pid','intval')?$this->_get('pid','intval'):2;
		$where['level']=$this->_get('level','intval');
		$where['pid']=$this->pid;
		$title=rawurldecode($this->_get('title'));
		$where['status']=1;
		$where['display']=array('gt',0);
		$order['sort']='asc';
		$nav=M('Node')->where($where)->order($order)->select();
		$this->assign('title',$title);
		$this->assign('nav',$nav);
	}


}