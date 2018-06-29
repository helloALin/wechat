<?php
//会员控制器
class Member_cardAction extends BackAction{
	public function _initialize() {
        parent::_initialize();  //RBAC 验证接口初始化

    }
    //会员管理
    //会员卡
    public function index(){

    	$card_set_model = M('Member_card_set');
    	$map['token']=TOKEN;
    	$cards = $card_set_model->where($map)->order('id ASC')->select();
        if ($cards) {
            $card_create_data = M('Member_card_create');
            $i                = 0;
            foreach ($cards as $card) {

		        $where['cardid']   = CARDID;
		        $where['token']    = TOKEN;
		        $where['wecha_id'] = array('neq','');

                $cards[$i]['usercount'] = $card_create_data->where($where)->count();
                $i++;
            }
        }
        $this->assign('cards', $cards);
        $this->display();
    }
    //进入会员管理(所有会员)
    public function members(){
		$card_create_db    = M('Member_card_create');
        $where             = array();
        $where['cardid']   = intval($_GET['id']);
        $where['token']    = TOKEN;
        $where['wecha_id'] = array('neq','');
        if (IS_POST) {
            if (isset($_POST['searchkey']) && trim($_POST['searchkey'])) {
                $where['number'] = array(
                    'like',
                    '%' . trim($_POST['searchkey']) . '%'
                );
            }
        }
        $count     = $card_create_db->where($where)->count();
        $Page      = new Page($count, 15);
        $show      = $Page->show();
        $list      = $card_create_db->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $members   = $card_create_db->where($where)->select();

        $wecha_ids = array();
        if ($members) {
            foreach ($members as $member) {
                array_push($wecha_ids, $member['wecha_id']);
            }
            $userinfo_db                = M('Userinfo');
            $userinfo_where['wecha_id'] = array('in',$wecha_ids);
            $users                      = $userinfo_db->where($userinfo_where)->select();
            $usersArr                   = array();
            if ($users) {
                foreach ($users as $u) {
                    $usersArr[$u['wecha_id']] = $u;
                }
            }
            $i = 0;
            foreach ($members as $member) {
                $thisUser                    = $usersArr[$member['wecha_id']];
                $members[$i]['truename']     = $thisUser['truename'];
                $members[$i]['wechaname']    = $thisUser['wechaname'];
                $members[$i]['qq']           = $thisUser['qq'];
                $members[$i]['tel']          = $thisUser['tel'];
                $members[$i]['getcardtime']  = $thisUser['getcardtime'];
                $members[$i]['expensetotal'] = $thisUser['expensetotal'];
                $members[$i]['total_score']  = $thisUser['total_score'];
                $i++;
            }
            $this->assign('members', $members);
            $this->assign('page', $show);
        }
        $this->display();
    }
     //会员卡列表
    public function index222()
    {	//token确定会员卡的所属。
    	$cnt=M()->table("tp_member_card_set as a")->join("INNER JOIN tp_wxuser as b on a.token=b.token")->count();
    	$cards=M()->table("tp_member_card_set as a")->join("INNER JOIN tp_wxuser as b on a.token=b.token")->field("a.*")->select();
        $this->assign('cards', $cards);
		//p($cards);

        //获取token
        //$condition['id']=6;
        //$token=M('Wxuser')->field('token')->where($condition)->select();
        //$TT=$token[0]['token'];
        //$map['token']=$TT;

        //会员的数量

        //$vip_cnt=M()->table("tp_member_card_create as a")->join("INNER JOIN tp_userinfo as b on a.wecha_id=b.wecha_id and a.wecha_id=b.wecha_id")->join(" INNER JOIN tp_wxuser as c on b.token=c.token")->count();
        //$vip_info=M()->table("tp_member_card_create as a")->join("INNER JOIN tp_userinfo as b on a.wecha_id=b.wecha_id")->field("a.*")->select();
		//$this->assign('vip_cnt', $vip_cnt);
		//===========================
		$token=TOKEN;
    	$cardid=CARDID;
    	$where['token'] = array('eq',$token);
		$where['cardid'] = array('eq',$cardid);
    	//会员卡总的数量
    	$data = M('Member_card_create');
    	$count             = $data->where($where)->count();
    	//会员卡领取数量
        $where['wecha_id'] = array('neq','');
        $usecount          = M('member_card_create')->where($where)->count();
        //会员卡未领取数量
        $ucount=$count - $usecount;
        //传递值到模版中使用
		$this->assign("count",$count );
		$this->assign("usecount",$usecount );
        $this->assign("ucount",$ucount );
		//===========================

        $this->display();
    }
    //会员列表
     public function members111()
    {	echo '123';
        $card_create_db    = M('Member_card_create');
        $where             = array();
        $where['cardid']   = intval($_GET['id']);
        $where['token']    = $this->token;
        $where['wecha_id'] = array(
            'neq',
            ''
        );
        if (IS_POST) {
            if (isset($_POST['searchkey']) && trim($_POST['searchkey'])) {
                $where['number'] = array(
                    'like',
                    '%' . trim($_POST['searchkey']) . '%'
                );
            }
        }
        $count     = $card_create_db->where($where)->count();
        $Page      = new Page($count, 15);
        $show      = $Page->show();
        $list      = $card_create_db->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $members   = $card_create_db->where($where)->select();
        $wecha_ids = array();
        if ($members) {
            foreach ($members as $member) {
                array_push($wecha_ids, $member['wecha_id']);
            }
            $userinfo_db                = M('Userinfo');
            $userinfo_where['wecha_id'] = array(
                'in',
                $wecha_ids
            );
            $users                      = $userinfo_db->where($userinfo_where)->select();
            $usersArr                   = array();
            if ($users) {
                foreach ($users as $u) {
                    $usersArr[$u['wecha_id']] = $u;
                }
            }
            $i = 0;
            foreach ($members as $member) {
                $thisUser                    = $usersArr[$member['wecha_id']];
                $members[$i]['truename']     = $thisUser['truename'];
                $members[$i]['wechaname']    = $thisUser['wechaname'];
                $members[$i]['qq']           = $thisUser['qq'];
                $members[$i]['tel']          = $thisUser['tel'];
                $members[$i]['getcardtime']  = $thisUser['getcardtime'];
                $members[$i]['expensetotal'] = $thisUser['expensetotal'];
                $members[$i]['total_score']  = $thisUser['total_score'];
                $i++;
            }
            $this->assign('members', $members);
            $this->assign('page', $show);
        }
        $this->display();
    }
    //删除会员
    public function member_del()
    {
        $card_create_db = M('Member_card_create');
        $thisMember     = $card_create_db->where(array(
            'id' => intval($_GET['itemid'])
        ))->find();
        $thisUser       = M('Userinfo')->where(array(
            'token' => $this->token,
            'wecha_id' => $thisMember['wecha_id']
        ))->find();
        $where          = array(
            'wecha_id' => $thisUser['wecha_id'],
            'token' => $this->token
        );
        M('Member_card_sign')->where($where)->delete();
        M('Member_card_use_record')->where($where)->delete();
        M('Userinfo')->where($where)->delete();
        $card_create_db->where(array(
            'id' => intval($_GET['itemid'])
        ))->save(array(
            'wecha_id' => ''
        ));
        $this->success('操作成功');
    }

    // 添加用户
    public function add(){
        $UserDB = D("User");
        if(isset($_POST['dosubmit'])) {
            $password = $_POST['password'];
            $repassword = $_POST['repassword'];
            if(empty($password) || empty($repassword)){
                $this->error('密码必须填写！');
            }
            if($password != $repassword){
                $this->error('两次输入密码不一致！');
            }
            //根据表单提交的POST数据创建数据对象
            if($UserDB->create()){
                $user_id = $UserDB->add();
                if($user_id){
                    $data['user_id'] = $user_id;
                    $data['role_id'] = $_POST['role'];
                    if (M("RoleUser")->data($data)->add()){
                        $this->assign("jumpUrl",U('/Admin/User/index'));
                        $this->success('添加成功！');
                    }else{
                        $this->error('用户添加成功,但角色对应关系添加失败!');
                    }
                }else{
                     $this->error('添加失败!');
                }
            }else{
                $this->error($UserDB->getError());
            }
        }else{
            $role = D('Role')->getAllRole(array('status'=>1),'sort DESC');
            $this->assign('role',$role);
            $this->assign('tpltitle','添加');
            $this->display();
        }
    }

    // 编辑用户
    public function edit(){
         $UserDB = D("User");
        if(isset($_POST['dosubmit'])) {
            $password = $_POST['password'];
            $repassword = $_POST['repassword'];
            if(!empty($password) || !empty($repassword)){
                if($password != $repassword){
                    $this->error('两次输入密码不一致！');
                }
                $_POST['password'] = md5($password);
            }
            if(empty($password) && empty($repassword)) unset($_POST['password']);   //不填写密码不修改
            //根据表单提交的POST数据创建数据对象
            if($UserDB->create()){
                if($UserDB->save()){
                    $where['user_id'] = $_POST['id'];
                    $data['role_id'] = $_POST['role'];
                    M("RoleUser")->where($where)->save($data);
                    $this->assign("jumpUrl",U('/Admin/User/index'));
                    $this->success('编辑成功！');
                }else{
                     $this->error('编辑失败!');
                }
            }else{
                $this->error($UserDB->getError());
            }
        }else{
            $id = $this->_get('id','intval',0);
            if(!$id)$this->error('参数错误!');
            $role = D('Role')->getAllRole(array('status'=>1),'sort DESC');
            $info = $UserDB->getUser(array('id'=>$id));
            $this->assign('tpltitle','编辑');
            $this->assign('role',$role);
            $this->assign('info',$info);
            $this->display('add');
        }
    }

    //ajax 验证用户名
    public function check_username(){
        $userid = $this->_get('userid');
        $username = $this->_get('username');
        if(D("User")->check_name($username,$userid)){
            echo 1;
        }else{
            echo 0;
        }
    }

    //删除用户
    public function del(){
        $id = $this->_get('id','intval',0);
        if(!$id)$this->error('参数错误!');
        $UserDB = D('User');
        $info = $UserDB->getUser(array('id'=>$id));
        if($info['username']==C('SPECIAL_USER')){     //无视系统权限的那个用户不能删除
           $this->error('禁止删除此用户!');
        }
        if($UserDB->delUser('id='.$id)){
            if(M("RoleUser")->where('user_id='.$id)->delete()){
                $this->assign("jumpUrl",U('/Admin/User/index'));
                $this->success('删除成功！');
            }else{
                $this->error('用户成功,但角色对应关系删除失败!');
            }
        }else{
            $this->error('删除失败!');
        }
    }
    //leo add
    //获取当前的token
    public function token(){
//    	$wxuser_info=M('Wxuser')->field('token')->select();
//    	var_dump($wxuser_info);
//    	$userinfo_info=M('Userinfo')->field('token')->select();
//    	var_dump($userinfo_info);
    	$vip_info=M()->table("tp_userinfo as a")->join("INNER JOIN tp_wxuser as b on a.token=b.token")->order('id asc')->field("a.*")->select();
	    //p($vip_info);
	    //die;
	    $this->assign('vip_info',$vip_info);
		$this->display();
    }
    public function test(){
    	$token='fatqrj1401869433';
    	$cardid=2;
    	$where['token'] = array('eq',$token);
		$where['cardid'] = array('eq',$cardid);
    	//会员卡总的数量
    	$data = M('Member_card_create');
    	$count             = $data->where($where)->count();
    	//会员卡领取数量
        $where['wecha_id'] = array('neq','');
        $usecount          = M('member_card_create')->where($where)->count();
        //会员卡未领取数量
        $ucount=$count - $usecount;
        echo '会员卡总的数量'.$count.'<br />';
        echo '会员卡领取数量'.$usecount.'<br />';
        echo '会员卡剩余数量'.$ucount.'<br />';
		//$this->assign("count",$count );
		//$this->assign("usecount",$usecount );
        //$this->assign("ucount",$ucount );
    }
    function gdc(){
		p(get_defined_constants(true));
	}
}