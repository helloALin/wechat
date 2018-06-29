<?php
class GroupAction extends BackAction{
	public function _initialize() {
        parent::_initialize();  //RBAC 验证接口初始化
    }
	public function index(){
		$RoleDB = D('Role');
        $list = $RoleDB->getAllRole();
        $this->assign('list',$list);

        $this->display();
	}
	//添加用户组
	public function add(){
		$RoleDB = D("Role");
        if(isset($_POST['dosubmit'])) {
            //根据表单提交的POST数据创建数据对象
            if($RoleDB->create()){
                if($RoleDB->add()){
                    //$this->assign("jumpUrl",U('Group/index'));
                   // $this->success('添加成功！','Group/index');//在add的页面使用了js弹出文字框，所以没有跳转。
                   $this->success('添加成功！',U('Admin/Group/index'));
                }else{
                     $this->error('添加失败!');
                }
            }else{
                $this->error($RoleDB->getError());
            }
        }else{
            $this->assign('tpltitle','添加');
            $this->display();
        }
	}
	//编辑用户组
	public function edit(){
		$RoleDB = D("Role");
        if(isset($_POST['dosubmit'])) {
            //根据表单提交的POST数据创建数据对象
            if($RoleDB->create()){
                if($RoleDB->upRole($_POST)){
                    $this->assign("jumpUrl",U('Group/index'));
                    $this->success('编辑成功!');
                }else{
                     $this->error('编辑失败!');
                }
            }else{
                $this->error($RoleDB->getError());
            }
        }else{
            $id = $this->_get('id','intval',0);
            if(!$id)$this->error('参数错误!');
            $info = $RoleDB->getRole(array('id'=>$id));
            $this->assign('tpltitle','编辑');
            $this->assign('info',$info);
			$this->assign('id',$id);
            $this->display();
        }
	}
	//删除用户组
	public function del(){
		$id = $this->_get('id','intval',0);

		//p($id);
		//exit ;
        if(!$id)$this->error('参数错误!');
        $RoleDB = D('Role');
        if($RoleDB->delRole('id='.$id)){
            $this->assign("jumpUrl",U('Group/index'));
            $this->success('删除成功！');
        }else{
            $this->error('删除失败!');
        }
	}

	// 排序权重更新
    public function role_sort(){
        $sorts = $this->_post('sort');
        if(!is_array($sorts))$this->error('排序失败，请认真填写参数!');
        foreach ($sorts as $id => $sort) {
            D('Role')->upRole( array('id' =>$id , 'sort' =>intval($sort) ) );
        }
        $this->assign("jumpUrl",U('Group/index'));
        $this->success('更新完成！');
    }

/* ========权限设置部分======== */
	//递归重组节点信息为多维数组
//	 public function node_merge($node, $pid = 0  ){
//        $arr = array();
//
//		foreach($node as $v){
//
//
//			if($v['pid'] == $pid){
//				$v['child'] = $this->node_merge($node,$v['id']);
//				$arr[] = $v;
//			}
//		}
//
//		return $arr;
//    }
/* ========权限设置部分======== */
	//递归重组节点信息为多维数组
	 public function node_merge($node,$access=null, $pid = 0  ){
        $arr = array();

		foreach($node as $v){
			//如果$access是null,直接走第二个if,不走第一个if.
			if(is_array($access)){
				$v['access']=in_array($v['id'],$access)?1:0;

			}
			if($v['pid'] == $pid){
				$v['child'] = $this->node_merge($node,$access,$v['id']);
				$arr[] = $v;
			}
		}

		return $arr;
    }

    //权限浏览（配置权限）
    public function access(){
    	//获取需要修改的id
        $roleid = $this->_get('roleid','intval',0);
        if(!$roleid) $this->error('参数错误!');


		$fields = array('id','name','title','pid');
		//把所有的节点都输出来
		$node = M('node')->field($fields)->order('sort')->select();
		//$node=$this->node_merge($node);
		//p($node);
		//die;
		//======LEO ADD===================
        //$this->assign('node',$this->node_merge($node));

		//原有权限
		//$access=M('access')->where(array('role_id'=>$roleid))->select();
		//如果有权限即1，没有0  页面1打上勾，0不打。
		//只需要node_id
		$access=M('access')->where(array('role_id'=>$roleid))->getField('node_id',true);
		//p($node);
		//p($access);
		//die;
		$node=$this->node_merge($node,$access); //$node是节点，$access是权限。如果权限数组在节点有，则把$v['access']压为1
		//$this->assign('node',$node);


		$nodec=$node[0]['child'];
		$this->assign('node',$nodec);
		//p($node[0]['child']);
		//die;
		//===========END ========================
		$this->assign('roleid',$roleid);
        $this->display();
    }

    //权限编辑（修改权限setAccess）
    public function access_edit(){
		//p($_POST);die;
        $roleid = $this->_post('roleid','intval',0);

		$db = M('access');

		//清空原权限
		$db->where(array('role_id'=>$roleid))->delete();

		//组成新权限
	    $data = array();
		foreach ($_POST['access'] as $v){
			$tmp = explode('_',$v);
			$data[] = array(
				'role_id' => $roleid,
				'node_id' => $tmp[0],
				'level' => $tmp[1]
			);
		}

		//插入新权限
		if($db->addAll($data)){
			$this->assign("jumpUrl",U('Group/access',array('roleid'=>$roleid)));
			$this->success('设置成功！');
		}else{
			$this->error('设置失败！');
		}

    }
}