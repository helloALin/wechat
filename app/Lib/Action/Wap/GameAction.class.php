<?php
class GameAction extends BaseAction{
	private $token;
	
	public function _initialize(){
		parent::_initialize();
		$this->token	= $this->_get('token');
		if(empty($this->token)){
			exit("请求地址不正确！");
		}
	}
    
	public function game2048(){
		$where = array('token'=>$this->token, 'keyword'=>'2048');
		$thisInfo = M('gamereply_info')->where($where)->find();
		if($thisInfo){
			$this->assign('info',$thisInfo);
			$this->display();
		}else{
			exit("该公众号没有开放该游戏！");
		}
	}
	
	public function game2048Plus(){
		$where = array('token'=>$this->token, 'keyword'=>'2048Plus');
		$thisInfo = M('gamereply_info')->where($where)->find();
		if($thisInfo){
			$this->assign('info',$thisInfo);
			$this->display();
		}else{
			exit("该公众号没有开放该游戏！");
		}
	}
	
	public function game2048Fly(){
		$where = array('token'=>$this->token, 'keyword'=>'2048Fly');
		$thisInfo = M('gamereply_info')->where($where)->find();
		if($thisInfo){
			$this->assign('info',$thisInfo);
			$this->display();
		}else{
			exit("该公众号没有开放该游戏！");
		}
	}
	
	public function gameCzz(){
		$info		= M('czzreply_info')->where(array('token' => $this->token))->find();
		$info['url']=str_replace('{siteUrl}','',$info['url']);
		$this->assign('info', $info);
		$this->display();
	}
    
}
    
?>