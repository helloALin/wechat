<?php
/**
 *发布消息中心
**/
class TrainAction extends UserAction{
	public function _initialize() {
		parent::_initialize();
		$type=$_GET["type"];
		if($type==""){$type='advantage';};
		switch($type){
			case 'advantage':
				$tpltypename[0][name] = '课程优势';
				$tpltypename[1][name] = 'advantage_add';
				break;
			case 'describe':
				$tpltypename[0][name] = '课程描述';
				$tpltypename[1][name] = 'describe_add';
				break;
			case 'teacher':
				$tpltypename[0][name] = '师资介绍';
				$tpltypename[1][name] = 'teacher_add';
				break;
			case 'syllabus':
				$tpltypename[0][name] = '课程表';
				$tpltypename[1][name] = 'syllabus_add';
				break;
			case 'environment':
				$tpltypename[0][name] = '教学环境';
				$tpltypename[1][name] = 'environment_add';
				break;
			case 'video':
				$tpltypename[0][name] = '视频广告';
				$tpltypename[1][name] = 'video_add';
				break;
			case 'share':
				$tpltypename[0][name] = '学员分享';
				$tpltypename[1][name] = 'share_add';
				break;	
			case 'job':
				$tpltypename[0][name] = '就业意向';
				$tpltypename[1][name] = 'job_add';
				break;
			case 'thank':
				$tpltypename[0][name] = '学员感言';
				$tpltypename[1][name] = 'thank_add';
				break;
			case 'news':
				$tpltypename[0][name] = '课程新闻';
				$tpltypename[1][name] = 'news_add';
				break;		
		}
		$this->assign('type',$type);
		$this->assign('tpltypename',$tpltypename);
		
	}
	public function index(){
		//=============
		$type=$_GET["type"];
		//if($type=''){$type='advantage';};
		if($type==""){$type='advantage';};
		switch($type){
			case 'advantage':
				$tpltypename[0][name] = '课程优势';
				$tpltypename[1][name] = 'advantage_add';
				break;
			case 'describe':
				$tpltypename[0][name] = '课程描述';
				$tpltypename[1][name] = 'describe_add';
				break;
			case 'teacher':
				$tpltypename[0][name] = '师资介绍';
				$tpltypename[1][name] = 'teacher_add';
				break;
			case 'syllabus':
				$tpltypename[0][name] = '课程表';
				$tpltypename[1][name] = 'syllabus_add';
				break;
			case 'environment':
				$tpltypename[0][name] = '教学环境';
				$tpltypename[1][name] = 'environment_add';
				break;
			case 'video':
				$tpltypename[0][name] = '视频广告';
				$tpltypename[1][name] = 'video_add';
				break;
			case 'share':
				$tpltypename[0][name] = '学员分享';
				$tpltypename[1][name] = 'share_add';
				break;	
			case 'job':
				$tpltypename[0][name] = '就业意向';
				$tpltypename[1][name] = 'job_add';
				break;
			case 'thank':
				$tpltypename[0][name] = '学员感言';
				$tpltypename[1][name] = 'thank_add';
				break;
			case 'news':
				$tpltypename[0][name] = '课程新闻';
				$tpltypename[1][name] = 'news_add';
				break;		
		}
		$name=$tpltypename[0][name];
		$tpl=$tpltypename[1][name];
		$this->assign('type',$type);
		$this->assign('name',$name);
		$this->assign('tpl',$tpl);
		//=============	
		$TrainModel = D('Train');
		$map['type']=$type;
		$count=$TrainModel->where($map)->count();
		$page=new Page($count,10);

		$info=$TrainModel->where($map)->order('addtime desc')->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('info',$info);	
		$this->display();
	}
	public function add(){
		//=========
		$type=$_GET["type"];
		
		if($type==""){$type='advantage';};
			switch($type){
			case 'advantage':
				$tpltypename[0][name] = '课程优势';
				$tpltypename[1][name] = 'advantage_add';
				break;
			case 'describe':
				$tpltypename[0][name] = '课程描述';
				$tpltypename[1][name] = 'describe_add';
				break;
			case 'teacher':
				$tpltypename[0][name] = '师资介绍';
				$tpltypename[1][name] = 'teacher_add';
				break;
			case 'syllabus':
				$tpltypename[0][name] = '课程表';
				$tpltypename[1][name] = 'syllabus_add';
				break;
			case 'environment':
				$tpltypename[0][name] = '教学环境';
				$tpltypename[1][name] = 'environment_add';
				break;
			case 'video':
				$tpltypename[0][name] = '视频广告';
				$tpltypename[1][name] = 'video_add';
				break;
			case 'share':
				$tpltypename[0][name] = '学员分享';
				$tpltypename[1][name] = 'share_add';
				break;	
			case 'job':
				$tpltypename[0][name] = '就业意向';
				$tpltypename[1][name] = 'job_add';
				break;
			case 'thank':
				$tpltypename[0][name] = '学员感言';
				$tpltypename[1][name] = 'thank_add';
				break;
			case 'news':
				$tpltypename[0][name] = '课程新闻';
				$tpltypename[1][name] = 'news_add';
				break;		
		}
		$tpl=$tpltypename[1][name];
		$name=$tpltypename[0][name];	
		$this->assign('type',$type);
		$this->assign('name',$name);

		//$this->display('Train:'.$tpl);

		//=========
		$TrainModel = D('Train');		
		if(!empty($_POST)){
			$_POST['statdate'] = strtotime($this->_post('statdate'));
			//p($_POST['statdate']);
			//exit();
			if($TrainModel ->create()){
				$res = $TrainModel ->add();
				if($res){
					//p($_POST);
					$this->success('发布成功', U('Train/index',array('type'=>$_POST['type'])));
				}
			}else{
				$this->error($TrainModel->getError());
			}
		
		}else {
			
			$this->display('Train:'.$tpl);
			
		}		
		//=========
	}
	public function addPost(){
			//set_time_limit(0);
			import('ORG.UploadFile');
	   		$upload = new UploadFile();// 实例化上传类
			$upload->maxSize  = 10485760 ;// 设置附件上传大小10M
	    	//$upload->allowExts  = array('mp4', 'ogg','webm');// 设置附件上传类型
			$upload->savePath ='./uploads/video/' ;// 设置附件上传目录
	    	$upload->saveRule = date('ymdHis').'_'.mt_rand(10,99);
	    	$TrainModel = D('Train');	
	        //===========================================
            if(!empty($_POST)){
                if($TrainModel ->create()){

                	if(!$upload->upload()) {// 上传错误提示错误信息
			        $this->error($upload->getErrorMsg());
				    }
				    else{
				    	// 上传成功
				    	$info = $upload->getUploadFileInfo();
						//保存当前数据对象
						$res =$TrainModel->add();

						if($res){

						$TrainModel->where(array('id'=>$res))->setField('video', $info[0]['savename']);

                        $this->success('发布成功', U('Train/index',array('type'=>$_POST['type'])));

		                }else{
		                    $this->error($TrainModel->getError());
		                }
				    }

	            }else {
	                $this->display('Train:'.$tpl);
	            }
            }

	}	
	public function view(){
            $TrainModel = D('Train');
            $where['id']=$this->_get('id','intval');
            $res=$TrainModel->where($where)->find();
            $this->assign('info',$res);
            $this->display();
	}
	public function edit(){
			$type=$_GET["type"];
			if($type==""){$type='advantage';};
			switch($type){
			case 'advantage':
				$tpltypename[0][name] = '课程优势';
				$tpltypename[1][name] = 'advantage_add';
				break;
			case 'describe':
				$tpltypename[0][name] = '课程描述';
				$tpltypename[1][name] = 'describe_add';
				break;
			case 'teacher':
				$tpltypename[0][name] = '师资介绍';
				$tpltypename[1][name] = 'teacher_add';
				break;
			case 'syllabus':
				$tpltypename[0][name] = '课程表';
				$tpltypename[1][name] = 'syllabus_add';
				break;
			case 'environment':
				$tpltypename[0][name] = '教学环境';
				$tpltypename[1][name] = 'environment_add';
				break;
			case 'video':
				$tpltypename[0][name] = '视频广告';
				$tpltypename[1][name] = 'video_add';
				break;
			case 'share':
				$tpltypename[0][name] = '学员分享';
				$tpltypename[1][name] = 'share_add';
				break;	
			case 'job':
				$tpltypename[0][name] = '就业意向';
				$tpltypename[1][name] = 'job_add';
				break;
			case 'thank':
				$tpltypename[0][name] = '学员感言';
				$tpltypename[1][name] = 'thank_add';
				break;
			case 'news':
				$tpltypename[0][name] = '课程新闻';
				$tpltypename[1][name] = 'news_add';
				break;		
		}
		$tpl=$tpltypename[1][name];
		$name=$tpltypename[0][name];	
		$this->assign('type',$type);
		$this->assign('name',$name);
			//===============
            $TrainModel = D('Train');
            if(!empty($_POST)){
            	$_POST['statdate'] = strtotime($_POST['statdate']);
                $TrainModel->create();
                //$res = $TrainModel ->save($_POST);
                $res = $TrainModel ->save();
                if($res){
                       $this->success('编辑成功', U('Train/index',array('type'=>$_POST['type'])));
                   }
            }else{
				$where['id']=$this->_get('id','intval');
				$res=$TrainModel->where($where)->find();
				$this->assign('info',$res);
				$this->display();
            }
	}
	public function del(){
		$where['id']=$this->_get('id','intval');
		if(D(MODULE_NAME)->where($where)->delete()){
			$this->success('删除成功',U(MODULE_NAME.'/index',array('type'=>$_GET['type'])));
		}else{
			$this->error('删除失败',U(MODULE_NAME.'/index',array('type'=>$_GET['type'])));
		}
	}
	public function show(){

	}

}
?>