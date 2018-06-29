<?php
class ActivityAction extends Oauth2Action{
	protected $token;
	
	public function _initialize(){
		parent::_initialize();  
		//$this->wecha_id = 'oj1_HjrufRURyvfc-ScQ4pwMvGr1w';
		define('RES', THEME_PATH . 'common');
		define('STATICS', TMPL_PATH . 'static');
		$this->token	= $this->_get('token');
		if(empty($this->token)){
			exit("请求地址不正确！");
		}
	}
    
	public function index(){
    	$model = M('Activity');
    	$where = array('token' => $this->token);
    	if($this->_get("id", "trim")){
    		$where["id"] = $this->_get("id", "trim,intval");
    	}
    	$activity = $model->where($where)->find();
		if($activity){
			$this->assign('info',$activity);
			$config = getWXJSConfig($this->token);
			$config['jsApiList'] = array('onMenuShareTimeline', 'onMenuShareAppMessage', 'previewImage');
			$this->assign('config', json_encode($config));
			if($this->wecha_id){
				$where['wecha_id'] = $this->wecha_id;
				$this->assign('apply', M('ActivityApply')->where($where)->find());
			}
			$this->assign('subscribe', check_subscribe($this->token, $this->wecha_id));
			$this->display();
		}else{
			exit("活动不存在！");
		}
	}
	
	public function apply(){
		if(!check_subscribe($this->token, $this->wecha_id)){
			$this->error('对不起，需关注公众号后才能进行点赞！');
		}
		$model = D('ActivityApply');
    	C('TOKEN_ON',false);	// 关闭表单令牌验证
    	if($model->create()){
    		if($_FILES["face_img"] && $_FILES["face_img"]['name']){
	    		import("ORG.UploadFile");
	    		//使用home/config下的上传配置
	    		$upload = new UploadFile(array(
					"maxSize"	=>	2048000,
					"allowExts"	=>	array("png","gif","jpg","jpeg"),
					"savePath"	=>	THEME_PATH . 'common/activity/face_img/',
					"thumb"		=>	true,
					"thumbMaxHeight"=>	200,
					"thumbMaxWidth"	=>	200,
					"thumbPrefix"	=>	"",
					"thumbRemoveOrigin"	=>	false,			
				));
	    		//$upload->thumbFile = $token;	//使用token作为上传头像文件名
	    		$info = $upload->uploadOne($_FILES["face_img"]);
	    		if($upload->getErrorMsg()){
	    			$this->error($upload->getErrorMsg());
	    		}
	    		$model->face_img = $info[0]['savepath'].$info[0]['savename'];
    		}else{
    			$model->face_img = THEME_PATH . 'common/activity/face_img/default_face.png';
    		}
    		
    		$model->token = $this->token;
    		$model->wecha_id = $this->wecha_id;
    		if($model->add())
    			$this->success('报名成功！');
    		else{
    			$this->error('报名失败！');
    		}
    	}else{
    		$this->error($model->getError());
    	}
	}
	
	public function rank(){
		$model = M('ActivityApply');
		if(IS_AJAX){
			if($this->_get("id", "trim")){
				$last = $this->_post('last', 'intval');
				$amount = $this->_post('amount', 'intval');
				$amount = $amount ? $amount : 10;
				$where = array('token'=>$this->token, 'activity_id'=>$this->_get("id", "trim"));
				if($_GET['key']){
					$where['name'] = array('like','%'.$_GET['key'].'%');
				}
				$field = 'id,name,face_img,content,good_num';
				$user_list = $model->field($field)->where($where)->limit($last,$amount)->order('good_num desc')->select();
				
			}
			echo json_encode($user_list);exit;
		}
		$where = array('token' => $this->token);
		if($this->_get("id", "trim")){
			$where["id"] = $this->_get("id", "trim,intval");
		}else{
			$this->error('请求内容不存在！');
		}
		$this->assign('list', $model->select());
		$this->display();
	}
	
	public function endorse(){
		if(!check_subscribe($this->token, $this->wecha_id)){
			$this->error('');
		}
		if($this->_get("id", "trim")){
			$where["id"] = $this->_get("id", "trim,intval");
			$where['token'] = $this->token;
			$activity = M('Activity')->field('start_date,end_date')->where($where)->find();
			if($activity && $this->_get("uid", "trim")){
				$apply_id = $this->_get("uid", "trim");
				$where['activity_id'] = $where['id'];
				$where['id'] = $apply_id;
				$endorse_data = array('wecha_id'=>$this->wecha_id, 'apply_id'=>$apply_id, 'activity_id'=>$where['activity_id']);
				$is_endorse = M('ActivityEndorse')->where($endorse_data)->getField('id');
				if($is_endorse){
					$this->error('您已经点过赞了！');
				}
				if(M('ActivityApply')->where($where)->setInc('good_num')){
					$endorse_data['romate_ip'] = get_client_ip();
					$endorse_data['create_time'] = time();
					M('ActivityEndorse')->add($endorse_data);
					$this->success(M('ActivityApply')->where($where)->getField('good_num'));
				}else{
					Log::write('点赞失败'.M('ActivityApply')->getDbError());
					$this->error('点赞失败！');
				}
			}else{
				$this->error('没有找到对应报名信息！');
			}
		}else{
			$this->error('请求内容不存在！');
		}
	}
	
	public function child(){
		$model = M('Activity');
		$where = array('token' => $this->token);
		if($this->_get("id", "trim")){
			$where["id"] = $this->_get("id", "trim,intval");
		}
		$type = $this->_get('type','trim');
		if(empty($type) || $type=='intro'){
			$this->assign('title', '简介');
		}else{
			$this->assign('title', '流程');
		}
		$activity = $model->where($where)->find();
		if($activity){
			$this->assign('info',$activity);
			$config = getWXJSConfig($this->token);
			$config['jsApiList'] = array('onMenuShareTimeline', 'onMenuShareAppMessage', 'previewImage');
			$this->assign('config', json_encode($config));
			//if($this->wecha_id){
				//$where['wecha_id'] = $this->wecha_id;
				//$this->assign('apply', M('ActivityApply')->where($where)->find());
			//}
			$this->display();
		}else{
			exit("活动不存在！");
		}
	}
}
    
?>