<?php
//wap评论模块

class CommentAction extends Oauth2Action{
	public $token;
	public $wecha_id;
	public function _initialize(){
		parent::_initialize();
		//$this->wecha_id='oTRiCtzmS1oLd4CXpXD8L48M9O1Y';   //我的号
		$agent = $_SERVER['HTTP_USER_AGENT']; 
		$this->token = $this->_get('token');
		$this->assign('token',$this->token);	
	}

	//留言列表视图
	public function index(){
		//图文信息
		$imgInfo=M('img')->where(array('token'=>$this->token,'id'=>$_GET['id']))->find();
		if(!$imgInfo){
		$this->redirect('Index/index', array('token' => $this->token));
		exit;
		}
		$this->assign('imgInfo',$imgInfo);
		//个人信息 头像，昵称
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		$peopleInfo = M('wxuser_people')->where($map)->find();
		$this->assign('peopleInfo',$peopleInfo);
		//我的评论
		$map['token']=$this->token;
		$map['wecha_id']=$this->wecha_id;
		$map['imgid']=$_GET['id'];
		$commentInfo = M('comment')->where($map)->order('createtime DESC')->select();
		$this->assign('commentInfo',$commentInfo);
		
		$this->display();

	}

	public function addComment(){
		$data=array();	
		$data['token'] = $this->token;
		$data['wecha_id'] = $this->wecha_id;
		$data['imgid'] = $this->_post('imgid');
		$data['text'] = $this->_post('text');
		$data['createtime'] = time();
		$data['status'] = 0;
		$result=M('comment')->add($data);
		if($result){
			echo '提交评论成功';
		}else{
			echo '提交评论失败';
		}
	}

	//删除留言处理
	public function del(){
		$this->token = $this->_get('token');
		$this->wecha_id	= $this->_get('wecha_id');
		$db = M('liuyan');
		$id = $_GET['id'];
		if(IS_GET){
			$db = M('Liuyan');
			$db->delete($id);
			
			header("location:".U('Liuyan/index',array('token'=> $this->token, 'wecha_id'=> $this->wecha_id)));
		}


}

















}







?>