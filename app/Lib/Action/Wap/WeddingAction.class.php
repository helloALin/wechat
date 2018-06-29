<?php
class WeddingAction extends BaseAction{
	
	public function index(){
		$agent = $_SERVER['HTTP_USER_AGENT']; 
		$domain = 'http://'.$_SERVER['SERVER_NAME'];
				
		// if(!strpos($agent,"MicroMessenger")) {
		// 	echo '此功能只能在微信浏览器中使用';
		// 	exit;
		// }
		
		$token	  =  $this->_get('token');
		$wecha_id = $this->_get('wecha_id');
		$id 	  = $this->_get('id');
		$tCount=0;
		
		if($token==false){
			echo '数据不存在';exit;
		}
				
		$Wedding = M('Wedding')->where(array('id'=>$id,'token'=>$token))->find(); 
		$Wedding['who_first']=intval($Wedding['who_first']);
        $totalCount=$Wedding['tcount'];//报名总人数
		$photo_list=M('Wedding_photo')->where(array('token'=>$token,'pid'=>$Wedding['id'],'status'=>1))->select();
		$info_list=M('Wedding_info')->where(array('token'=>$token,'pid'=>$id))->select();
		if ($info_list){
			foreach ($info_list as $c){
				if ($c){
					$tCount+=intval($c['count']);
				}
			}
			$totalCount=$totalCount-$tCount;
		}
		$this->assign('Wedding',$Wedding);
		$this->assign('photo',$photo_list);
		$this->assign('Token',$token);
		$this->assign('domain',$domain);
		$this->assign('totalCount',$totalCount);
		$this->display();
		
	}
	//宴会地址
	public function address(){
		$token	  =  $this->_get('token');
		$id 	  = $this->_get('id');
		$Wedding = M('Wedding')->where(array('id'=>$id,'token'=>$token))->find(); 
        $this->assign('companies', $Wedding);
        $this->display();	
	}
	//宴会地图
	public function map()
    {
        $this->apikey = C('baidu_map_api');
        $this->assign('apikey', $this->apikey);
        $token	  =  $this->_get('token');
		$id 	  = $this->_get('id');
		$Wedding = M('Wedding')->where(array('id'=>$id,'token'=>$token))->find(); 
		//p($Wedding );
		//exit;
        $this->assign('thisCompany', $Wedding);
        $this->display();
    }
	public function info(){
		$agent = $_SERVER['HTTP_USER_AGENT']; 
		if(!strpos($agent,"MicroMessenger")) {
			echo '此功能只能在微信浏览器中使用';
			exit;
		}
		$token	  =  $this->_get('token');
		$wecha_id = $this->_get('wecha_id');
		$id 	  = $this->_get('id');
		$password = $this->_post('pwd');
		$isShow = 0;
		$totalCount = 0;
		if($token==false){
			echo '数据不存在';exit;
		}	
		if(isset($password)){	
			$Wedding = M('Wedding')->where(array('id'=>$id,'token'=>$token,'password'=>$password))->find();
			$Wedding['who_first']=intval($Wedding['who_first']);
			$this->assign('Wedding',$Wedding);
			if(!empty($Wedding)){
				$isShow=1;
			}
		}
        $info_list=M('Wedding_info')->where(array('token'=>$token,'pid'=>$id,'type'=>1))->select();
		if ($info_list){
			foreach ($info_list as $c){
				if ($c){
					$totalCount+=intval($c['count']);
				}
			}
		}
		$this->assign('info',$info_list);
		$this->assign('isShow',$isShow);
		$this->assign('Token',$token);
		$this->assign('id',$id);
		$this->assign('totalCount',$totalCount);
		$this->display();
		
	}

	public function comment(){
		$agent = $_SERVER['HTTP_USER_AGENT']; 
		if(!strpos($agent,"MicroMessenger")) {
			echo '此功能只能在微信浏览器中使用';
			exit;
		}
	 
		$token	  =  $this->_get('token');
		$wecha_id = $this->_get('wecha_id');
		$id 	  = $this->_get('id');
        $password = $this->_post('pwd');
		$isShow=0;
		if($token==false){
			echo '数据不存在';exit;
		}	
		if(isset($password)){	
			$Wedding = M('Wedding')->where(array('id'=>$id,'token'=>$token,'password'=>$password))->find();
			$Wedding['who_first']=intval($Wedding['who_first']);
			$this->assign('Wedding',$Wedding);
			if(!empty($Wedding)){
				$isShow=1;
			}
		}
        $info_list=M('Wedding_info')->where(array('token'=>$token,'pid'=>$id,'type'=>2))->select();
		$totalCount=M('Wedding_info')->where(array('token'=>$token,'pid'=>$id,'type'=>2))->count();
		$this->assign('info',$info_list);
		$this->assign('isShow',$isShow);
		$this->assign('Token',$token);
		$this->assign('id',$id);
		$this->assign('totalCount',$totalCount);
		$this->display();
		
	}
		
	public function add(){
		if($_POST['type'] =='ly'){
			$data=array();
			$data['pid'] 		= $this->_post('id');
			$data['token'] 		= $this->_post('token');
			$data['username'] = $this->_post('userName');
			$data['telphone'] = $this->_post('telphone');
			$data['count'] = $this->_post('count');
			$data['content'] = '';
			$data['type'] = 1;
			$data['create_time'] = time(); 
			$result=M('Wedding_info')->add($data);
			echo'提交成功';
			exit;
		}else{

			echo'提交失败';
		}

	}


	public function add2(){
		if($_POST['type'] =='zf'){
			$data=array();
			$data['pid'] 		= $this->_post('id');
			$data['token'] 		= $this->_post('token');
			$data['username'] = $this->_post('userName');
			$data['telphone'] = $this->_post('telphone');
			$data['count'] = 0;
			$data['content'] = $this->_post('content');
			$data['type']=2;
			$data['create_time'] = time(); 
			$result=M('Wedding_info')->add($data);
			echo'提交成功';
			exit;
		}else{

			echo'提交失败';
		}

	}
	
	
}
?>