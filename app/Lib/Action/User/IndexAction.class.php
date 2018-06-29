<?php
class IndexAction extends UserAction{
	public function _initialize() {
		//parent::_initialize();
		define('RES', THEME_PATH . 'common');
        define('STATICS', TMPL_PATH . 'static');
		$this->assign('wecha', $wecha);
		$this->token = session('token');
        $this->assign('token', $this->token);
		//==========
		//vip权限控制部分
		$this->userinfo        = M('User_group')->where(array(
            'id' => session('gid')
        ))->find();
        $this->userGroup = $this->userinfo;
        $this->users           = M('Users')->where(array(
            'id' => $_SESSION['uid']
        ))->find();
        $this->user      = $this->users;
        $this->assign('thisUser', $this->users);
        $this->assign('viptime', $this->users['viptime']);
		$this->wecha = M('Wxuser')->field('wxname,weixin,wxid,headerpic')->where(array(
            'token' => session('token'),
            'uid' => session('uid')
        ))->find();
        $this->assign('wecha', $this->wecha);
		
		//==========
		
	}
	
	//登录界面
	function login(){
		if(session("LOGIN_NAME"))
			$this->redirect("User/Index/index");
		//p($_SESSION);	
		
		$this->display();
	}
	
	/* $code=$this->_post('code','intval,md5',0);
		if($code != $_SESSION['verify']){
			$this->error('验证码错误',U('Admin/index'));
		} */
	
	/**
	 * ajax登录
	 */
	function ajaxLogin(){
		$result = array('status'=>0, 'msg'=>'非法访问！');
		if(IS_POST){
			$username = $_POST['username']; 
			$password = $_POST['password'];
			//$verify = $_POST['code'];
			//$verify = $this->_post('code','md5');
			$verify = md5(strtolower($_POST['code']));
			if(empty($username) || empty($password)){
				$result['msg'] = "用户名、密码不能用空 ！";
			//} else if(empty($verify) || $_SESSION['verify'] != md5($_POST['code'])) {
			} else if(empty($verify) || strcasecmp($verify, $_SESSION["verify"])) {
				$result['msg'] = "验证码错误!";
				//$result['msg'] =$verify;
			} else {
				//$admin = M("admin")->where(array('admin_name'=>$username))->find();
				$admin = M("users")->where(array('username'=>$username))->find();
				if($admin && $admin['password'] === $password){
					//$admin['admin_password'] = '';
					//$admin['login_count'] = intval($admin['login_count'], 0) + 1;
					//$admin['last_login_time'] = $admin['login_time'];
					//$admin['login_time'] = time();
					/* $obj = M("users")->field('id,lasttime');
					if($obj->create($admin) && $obj->save()){
						//验证通过，且更新数据到数据库，返回跳转地址
						session("LOGIN_NAME", $admin['username']);
						//session("LOGIN_ACCESS", $admin['admin_access']); //by leo
						//session("LOGIN_COUNT", $admin['login_count']);
						session("LAST_LOGIN_TIME", date("Y-m-d H:i:s", $admin['lasttime']));
						$result['status'] = 1; 
						$result['url'] = U("User/Index/index");
					} else {
						Log::write('MODEL_ERROR: '.$obj->getError().', DB_ERROR: '.$obj->getDbError());
						$result['msg'] = "登录失败111，请重试！";
					} */
					session("LOGIN_NAME", $admin['username']);
					session("LAST_LOGIN_TIME", date("Y-m-d H:i:s", $admin['lasttime']));
					session('uid',$admin['id']);
					session('gid',$admin['gid']);
					//session('uname',$admin['username']);
					$info=M('user_group')->find($admin['gid']);
					session('diynum',$admin['diynum']);
					session('connectnum',$admin['connectnum']);
					session('activitynum',$admin['activitynum']);
					session('viptime',$admin['viptime']);
					session('gname',$info['name']);
					M("users")->where(array('id'=>$admin['id']))->save(array('lasttime'=>time(),'lastip'=>$_SERVER['REMOTE_ADDR']));//最后登录时间
					$result['status'] = 1; 
					$result['url'] = U("User/Index/index");
					
				} else {
					$result['msg'] = "用户名密码错误！";
				}
			}
		}
		session("verify", null);	//删除验证码
		$this->ajaxReturn($result, 'json');
	}
	/**
	 * 获取验证码图片
	 */
	function verify(){
		Image::buildImageVerify('4','5','png', '50', '20', 'verify'); //长度，字符串类型，图片类型，宽度，高度，session记录名称
	}
	/**
	 * 退出登录
	 */
	function logout(){
		session("LOGIN_NAME", null);
		session("LOGIN_COUNT", null);
		session("LAST_LOGIN_TIME", null);
		$this->redirect("Index/login");
	}
	//公众帐号列表
	public function index(){
		//p($this->wecha);
		parent::loginAuth();
		//$where['uid']=session('uid');
		$group=D('User_group')->select();
		foreach($group as $key=>$val){
			$groups[$val['id']]['did']=$val['diynum'];
			$groups[$val['id']]['cid']=$val['connectnum'];
		}
		unset($group);
		$db=M('Wxuser');
		$count=$db->where($where)->count();
		$page=new Page($count,25);
		$info=$db->where($where)->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('info',$info);
		$this->assign('group',$groups);
		$this->assign('page',$page->show());
		$this->display();
	}
	//添加公众帐号
	public function add(){
		parent::loginAuth();
		$randLength=6;
		$chars='abcdefghijklmnopqrstuvwxyz';
		$len=strlen($chars);
		$randStr='';
		for ($i=0;$i<$randLength;$i++){
			$randStr.=$chars[rand(0,$len-1)];
		}
		$tokenvalue=$randStr.time();
		$this->assign('tokenvalue',$tokenvalue);
		$this->assign('email',time().'@yourdomain.com');
		//地理信息
		if (C('baidu_map_api')){
			//$locationInfo=json_decode(file_get_contents('http://api.map.baidu.com/location/ip?ip='.$_SERVER['REMOTE_ADDR'].'&coor=bd09ll&ak='.C('baidu_map_api')),1);
			///$this->assign('province',$locationInfo['content']['address_detail']['province']);
			//$this->assign('city',$locationInfo['content']['address_detail']['city']);
			//var_export($locationInfo);
		}
	
		
		$this->display();
	}
	public function edit(){
		parent::loginAuth();
		$id=$this->_get('id','intval');
		$where['uid']=session('uid');
		$res=M('Wxuser')->where($where)->find($id);
		$this->assign('info',$res);
		$this->display();
	}
	
	public function editsms(){
		parent::loginAuth();
		$id=$this->_get('id','intval');
		$where['uid']=session('uid');
		$res=M('Wxuser')->where($where)->find($id);
		$this->assign('info',$res);
		$this->display();
	}

	public function editemail(){
		parent::loginAuth();
		$id=$this->_get('id','intval');
		$where['uid']=session('uid');
		$res=M('Wxuser')->where($where)->find($id);
		$this->assign('info',$res);
		$this->display();
	}
	
	public function del(){
		parent::loginAuth();
		$where['id']=$this->_get('id','intval');
		$where['uid']=session('uid');
		if(D('Wxuser')->where($where)->delete()){
			$this->success('操作成功',U(MODULE_NAME.'/index'));
		}else{
			$this->error('操作失败',U(MODULE_NAME.'/index'));
		}
	}
	
	public function upsave(){
		parent::loginAuth();
		//$this->all_save('Wxuser', '/editemail?id='.$this->_post('id','intval')."&token=".$this->_post('token'));
		$this->all_save('Wxuser','/index?token='."&token=".$this->_post('token'));
	}
	
	public function insert(){
		parent::loginAuth();
		$data=M('User_group')->field('wechat_card_num')->where(array('id'=>session('gid')))->find();
		$users=M('Users')->field('wechat_card_num')->where(array('id'=>session('uid')))->find();
		if($users['wechat_card_num']<$data['wechat_card_num']){
			
		}else{
			$this->error('您的VIP等级所能创建的公众号数量已经到达上线，请购买后再创建',U('User/Index/index'));exit();
		}
		//$this->all_insert('Wxuser');
		//
		$db=D('Wxuser');
		if($db->create()===false){
			$this->error($db->getError());
		}else{
			$id=$db->add();
			if($id){
				M('Users')->field('wechat_card_num')->where(array('id'=>session('uid')))->setInc('wechat_card_num');
				$this->addfc();
				//
				$this->success('操作成功',U('Index/index'));
			}else{
				$this->error('操作失败',U('Index/index'));
			}
		}
		
	}
	
	//功能
	public function autos(){
		parent::loginAuth();
		$this->display();
	}
	
	public function addfc(){
		parent::loginAuth();
		$token_open=M('Token_open');
		$open['uid']=session('uid');
		$open['token']=$_POST['token'];
		$gid=session('gid');
		$fun=M('Function')->field('funname,gid,isserve')->where('`gid` <= '.$gid)->select();
		foreach($fun as $key=>$vo){
			$queryname.=$vo['funname'].',';
		}
		$open['queryname']=rtrim($queryname,',');
		$token_open->data($open)->add();
	}
	
	public function usersave(){
		parent::loginAuth();
		$pwd=$this->_post('password');
		if($pwd!=false){
			$data['password']=md5($pwd);
			$data['id']=$_SESSION['uid'];
			if(M('Users')->save($data)){
				$this->success('密码修改成功！',U('Index/index'));
			}else{
				$this->error('密码修改失败！',U('Index/index'));
			}
		}else{
			$this->error('密码不能为空!',U('Index/useredit'));
		}
	}
	//处理关键词
	public function handleKeywords(){
		parent::loginAuth();
		$Model = new Model();
		//检查system表是否存在
		$keyword_db=M('Keyword');
		$count = $keyword_db->where('pid>0')->count();
		//
		$i=intval($_GET['i']);
		//
		if ($i<$count){
			$img_db=M($data['module']);
			$back=$img_db->field('id,text,pic,url,title')->limit(9)->order('id desc')->where($like)->select();
			//
			$rt=$Model->query("CREATE TABLE IF NOT EXISTS `tp_system_info` (`lastsqlupdate` INT( 10 ) NOT NULL ,`version` VARCHAR( 10 ) NOT NULL) ENGINE = MYISAM CHARACTER SET utf8");
			$this->success('关键词处理中:'.$row['des'],'?g=User&m=Create&a=index');
		}else {
			exit('更新完成，请测试关键词回复');
		}
	}

	// 用户登出
    /* public function logout() {
		session(null);
		session_destroy();
		unset($_SESSION);
        if(session('?'.C('USER_AUTH_KEY'))) {
            session(C('USER_AUTH_KEY'),null);
           
            //redirect(U('Home/Index/index'));
            //$this->success('退出登录！',U('Home/Index/index'));
            header('Location: http://www.vvhang.com/');
            
        }else {
            //$this->error('退出成功！',U('Home/Index/index'));
            //$this->error('退出成功！','Home/Index/index');
            header('Location: http://www.vvhang.com/');
        }
    } */
}
?>