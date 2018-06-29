<?php
class GameAction extends UserAction{
	private $reply_info_model;
	
	public function _initialize() {
		parent::_initialize();
		if (!$this->token){
			exit();
		}
		$this->reply_info_model=M('Gamereply_info');
	}
	public function game2048(){
		$where = array('token'=>$this->token, 'keyword'=>'2048');
		$thisInfo = $this->reply_info_model->where($where)->find();
		if(IS_POST){
			$this->saveGame('2048', empty($thisInfo));
		}else{
			$this->assign('set',$thisInfo);
			$this->display('index');
		}
	}
	
	public function game2048Plus(){
		$where = array('token'=>$this->token, 'keyword'=>'2048Plus');
		$thisInfo = $this->reply_info_model->where($where)->find();
		if(IS_POST){
			$this->saveGame('2048Plus', empty($thisInfo));
		}else{
			$this->assign('set',$thisInfo);
			$this->display('index');
		}
	}
	
	public function game2048Fly(){
		$where = array('token'=>$this->token, 'keyword'=>'2048Fly');
		$thisInfo = $this->reply_info_model->where($where)->find();
		if(IS_POST){
			$this->saveGame('2048Fly', empty($thisInfo));
		}else{
			$this->assign('set',$thisInfo);
			$this->display('index');
		}
	}
	
	private function saveGame($type, $isAdd = false){
		$row['title']=$this->_post('title');
		$row['info']=$this->_post('info');
		$row['picurl']=$this->_post('picurl');
		$row['picurls1']=$this->_post('picurls1');
		$row['linkurl']=$this->_post('linkurl');
		$row['ad']=$this->_post('ad');
		if ($isAdd){
			$row['keyword'] = 'game2048';
			$row['token']=$this->token;
			if($this->reply_info_model->add($row))
				$this->success('设置成功',U('Game/game2048'));
			else{
				$this->error("设置失败，请检查填写内容是否超过长度限制！");
				Log::write('设置游戏信息失败Gamereply_info'.$this->reply_info_model->getDbError());
			}
		}else {
			$where = array('token'=>$this->token, 'keyword'=>$type);
			//$keyword_model=M('Keyword');
			//$keyword_model->where(array('token'=>$this->token,'pid'=>$thisInfo['id'],'module'=>'Reply_info'))->save(array('keyword'=>$_POST['keyword']));
			if($this->reply_info_model->where($where)->save($row) === false){
				$this->success('修改成功',U('Game/game2048'));
			}else{
				$this->error("修改失败，请检查填写内容是否超过长度限制！");
				Log::write('修改游戏信息失败Gamereply_info'.$this->reply_info_model->getDbError());
			}
		}
	}
	
	/**
	 * 吃粽子游戏
	 */
	public function gameCzz(){
		$model=M('czzreply_info');
		$thisInfo = $model->where(array('token'=>$this->token))->find();
	
		if(IS_POST){
			$row['url']=strip_tags(htmlspecialchars_decode($_POST['url']));
			$row['title']=$this->_post('title');
			$row['info']=$this->_post('info');
			$row['picurl']=$this->_post('picurl');
			$row['picurls1']=$this->_post('picurls1');
			$row['bg']=$this->_post('bg');
			$row['wx']=$this->_post('wx');
			$row['zz']=$this->_post('zz');
	
			if ($thisInfo){
				$where=array('token'=>$this->token);
				$model->where($where)->save($row);
				//$keyword_model=M('Keyword');
				//$keyword_model->where(array('token'=>$this->token,'pid'=>$thisInfo['id'],'module'=>'Reply_info'))->save(array('keyword'=>$_POST['keyword']));
				$this->success('修改成功',U('Game/gameCzz'));
			}else {
				$row['token'] = $this->token;
				$model->add($row);
				$this->success('设置成功',U('Game/gameCzz'));
			}
		}else{
			$this->assign('set',$thisInfo);
			$this->display();
		}
	}
}
?>