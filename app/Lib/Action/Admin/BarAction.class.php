<?php
//酒吧控制器	酒吧ID barid=BID
class BarAction extends BackAction{
	public function _initialize() {
        parent::_initialize();  //RBAC 验证接口初始化

        $this->Yuyue_model=M('yuyue');
		$this->yuyue_order=M('yuyue_order');
		$this->assign('token',TOKEN);

    }

	public function index(){

		//确定用户所属分组
		$condition['user_id']=($_SESSION['userid']);
    	$r_info=M()->table("tp_role as a")->join("INNER JOIN tp_role_user as b on a.id=b.role_id")->where($condition)->getField('a.name');
    	$this->assign('r_info',$r_info);

        $this->display();
    }

    //订单列表显示

	public function infos(){

		$pid=BID;
		$check=$this->Yuyue_model->where(array('id'=>$pid))->find();

		if(empty($check)){$this->error('请填写好酒店管理页面再进行设置',U('Bar/index',array('token'=>TOKEN)));}

		$where = array('token'=> TOKEN,'pid'=>$pid);

		$data = $this->yuyue_order->where($where)->order('id desc')->select();

		$count = $this->yuyue_order->where($where)->count();

		$Page = new Page($count,20);

		$show = $Page->show();



		$this->assign('page',$show);

		$this->assign('data', $data);

		$this->assign('pid', $pid);

		$this->display();



	}



	//订单详细信息

	public function infos_detail(){

		$where = array('token'=> TOKEN,'id'=>$this->_get('id'));

		$data = $this->yuyue_order->where($where)->order('id desc')->select();

		$pid = $data[0]['pid'];

		$info=$data[0]['fieldsigle'].$data[0]['fielddownload'];

		if(!empty($info)){

		$info=substr($info,1);

		$info=explode('$',$info);

		$detail=array();

		foreach($info as $v){

			$detail['info'][]=explode('#',$v);

		}}

		$detail['all']=$data[0];

		$this->assign('detail', $detail);

		if(!empty($data[0]['nums'])){$this->assign('sum', $data[0]['nums']*$data[0]['price']);}

		$this->assign('pid', $pid);

		$this->display();

	}



	//删除订单

	public function delinfos(){

		if($this->_get('token')!=TOKEN){$this->error('非法操作');}

        $id = intval($this->_get('id'));

        if(IS_GET){

            $where=array('id'=>$id,'token'=>TOKEN);

            $check=M('yuyue_order')->where($where)->find();

            if($check==false)   $this->error('非法操作');

            $back=M('yuyue_order')->where($where)->delete();

            if($back==true){

                $this->success('操作成功',U('Bar/infos',array('token'=>TOKEN,'id'=>$check['pid'])));

            }else{

                 $this->error('服务器繁忙,请稍后再试',U('Bar/infos',array('token'=>TOKEN,'id'=>$check['xid'])));

            }

        }

	}

	//处理订单

	public function setType(){

		if($this->_get('token')!=TOKEN){$this->error('非法操作');}

        $id = intval($this->_get('id'));

		$type = intval($this->_get('type'));

		$pid = intval($this->_get('pid'));

        if(IS_GET){

			$where = array(

				'id'=> $id,

				'token'=> TOKEN,

			);

			$data = array(

				'type'=> $type

			);

			if($this->yuyue_order->where($where)->setField($data)){

				$this->success('修改成功！',U('Bar/infos',array('pid'=>$pid,'token'=>TOKEN)));

			}else{

				$this->error('修改失败！');

			}

        }

	}

}