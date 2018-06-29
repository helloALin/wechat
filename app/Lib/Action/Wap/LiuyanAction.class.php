<?php
//wap留言模块

class LiuyanAction extends Oauth2Action{
	public $token;
	public $wecha_id;
	public function _initialize(){
		//$this->token = $this->_get('token');
		//$this->wecha_id='oTRiCt2TQuT2ZwBuz3IARETH0-fA'; 
		parent::_initialize();
    	define('RES', THEME_PATH . 'common');
        define('STATICS', TMPL_PATH . 'static');
		//$agent = $_SERVER['HTTP_USER_AGENT']; 
	}

	//留言列表视图
	public function index(){
		$where = array(
			'token' => $this->token,
		);
		$this->uid = session('uid');
		$this->createtime = time();
		$db = M('liuyan');
		$rep = M('reply_info');
		$this->repic = $rep->where(array('infotype'=> "Liuyan", 'token'=> $this->token))->field('picurl')->find();
		$this->info = $db->order('createtime')->where(array('token'=> $this->token))->order('createtime DESC')->select();
		if(!empty($this->wecha_id)){
			$where['wecha_id'] = $this->wecha_id;
			$nickname = M('WxuserPeople')->where($where)->getField('nickname');
			$this->assign('nickname', $nickname);
		}
		$this->assign('token', $this->token);
		$this->display();

	}

	//留言添加视图
	// public function add(){
		// $this->token = $this->_get('token');
		// $this->wecha_id	= $this->_get('wecha_id');	
	// }
	
	//留言添加处理
	// public function runAdd(){
		// $this->token = $this->_get('token');
		// $this->wecha_id	= $this->_get('wecha_id');
		// $db = M('liuyan');
		// if(IS_POST){
			// $db->add($_POST);
			// header("location:".U('Liuyan/index'));
		// }
	// }
	
	public function add(){
		if($_POST['action'] == 'liuyan'){
			$data['token'] = $_POST['token'];
			$data['uid'] = $_POST['uid'];
			$data['title'] = $_POST['title'];
			$data['text'] = $_POST['text'];
			$data['createtime'] = time();
			$data['wecha_id'] = $_POST['wecha_id'];
			$db = M('liuyan');

			if($db->add($data)){
				echo '留言成功';
			}else{
				echo '留言失败';
			}
		}
	}
	
	
	//删除留言处理
	public function del(){
		//$this->wecha_id	= $this->_get('wecha_id');
		$id = $this->_get('id', 'trim,intval');
		$where = array('id' => $id, 'token'=>$this->token, 'wecha_id'=>$this->wecha_id);
		if($id && $this->token && M('Liuyan')->where($where)->delete()){
			$this->redirect(U('Liuyan/index', array('token'=> $this->token)));
		}else{
			$this->error('删除失败！',U('Liuyan/index', array('token'=> $this->token)));
		}
	}
}
?>