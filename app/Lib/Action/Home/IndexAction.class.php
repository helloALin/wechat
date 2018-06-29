<?php
class IndexAction extends BaseAction{
	//关注回复
	public function index(){

		$news=M('Fabu');

		$conditon['type']=array('eq',0);//新闻中心
		$map['type']=array('eq',1);//课程中心
		$newsCnt=$news->where($conditon)->count();

		$Page=new Page($newsCnt,4);
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show=$Page->show();

		$newsInfo=$news->where($conditon)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$courseInfo=$news->where($map)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$this->assign('newsInfo',$newsInfo);
		$this->assign('courseInfo',$courseInfo);
		$this->assign('page',$show);
		//=============
			$news=M('Anli');
			$where['type']=array('eq',2);//案例展示
			$anliCnt=$news->where($where)->count();
			$anliPage=new Page($anliCnt,5);
			$nowPage = isset($_GET['p'])?$_GET['p']:1;
			$anliShow=$anliPage->show();
			
			$anliInfo=$news->where($where)->limit($anliPage->firstRow.','.$anliPage->listRows)->order('id desc')->select();
			$this->assign('anliInfo',$anliInfo);
		//=============

		$this->display();
	}
	public function resetpwd(){
		$uid=$this->_get('uid','intval');
		$code=$this->_get('code','trim');
		$rtime=$this->_get('resettime','intval');
		$info=M('Users')->find($uid);
		if( (md5($info['uid'].$info['password'].$info['email'])!==$code) || ($rtime<time()) ){
			$this->error('非法操作',U('Index/index'));
		}
		$this->assign('uid',$uid);
		$this->display();
	}
	
	//课程中心
	public function train(){
		//==========
		//课程新闻换回首页的那个
		$news=M('Fabu');
		$conditon['type']=array('eq',0);//新闻中心
		$map['type']=array('eq',1);//课程中心
		$newsCnt=$news->where($conditon)->count();

		$Page=new Page($newsCnt,4);
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show=$Page->show();

		$newsInfo=$news->where($conditon)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$courseInfo=$news->where($map)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		$this->assign('newsInfo',$newsInfo);
		$this->assign('courseInfo',$courseInfo);
		$this->assign('page',$show);
		//==========
		define('UP', C('site_url') . '/uploads/video/');
		$train=M('Train');
		//==========
		
		//视频广告				
		$conditon1['type']=array('eq','video');//视频广告
		$video=$train->where($conditon1)->field('title,video,video2')->order('id desc')->find();
		$this->assign('video',$video);	
		//==================
		//学员分享
		$conditon2['type']=array('eq','share');//学员分享
		$share=$train->where($conditon2)->field('id,title,picurl')->order('id desc')->limit(0,4)->select();
		$this->assign('share',$share);	
		//p($share);
		//===========
		//就业意向
		$conditon3['type']=array('eq','job');//就业意向
		$job=$train->where($conditon3)->field('id,title')->order('id desc')->limit(0,5)->select();
		$this->assign('job',$job);	
		//==========
		//学员感言
		$conditon4['type']=array('eq','thank');//学员感言
		$thank=$train->where($conditon4)->field('id,title,picurl')->order('id desc')->limit(0,3)->select();
		$this->assign('thank',$thank);	
		//==========
		//课程新闻
		$conditon5['type']=array('eq','news');//课程新闻
		$news=$train->where($conditon5)->field('id,title')->order('id desc')->limit(0,5)->select();
		$this->assign('news',$news);	
		//==========
		//课程优势
		$conditon6['type']=array('eq','advantage');//课程优势		
		//$advantage=$train->where($conditon6)->field('id,title,info')->order('id desc')->find();
		//$advantage['info']=htmlspecialchars_decode($advantage['info']);
		$advantage=$train->where($conditon6)->field('id,title,info')->order('id asc')->limit(0,7)->select();
		foreach($advantage as $k => &$v){
        $v["info"] = htmlspecialchars_decode($v['info']);	
		}	
		$this->assign('advantage',$advantage);	
		//===========
		//课程描述
		$conditon7['type']=array('eq','describe');//课程描述
		$describe=$train->where($conditon7)->field('id,title')->order('id desc')->limit(0,7)->select();
		$this->assign('describe',$describe);	
		//==========
		//师资介绍
		$conditon8['type']=array('eq','teacher');//师资介绍		
		$teacher=$train->where($conditon8)->field('id,title,info,picurl')->order('id asc')->limit(0,3)->select();
		foreach($teacher as $k => &$v){
        $v["info"] = htmlspecialchars_decode($v['info']);	
		}	
		$this->assign('teacher',$teacher);	
		//===========
		//课程列表
		$conditon9['type']=array('eq','syllabus');//课程列表	
		$syllabus=$train->where($conditon9)->order('id asc')->limit(0,9)->select();
		foreach($syllabus as $k => &$v){
        $v["info"] = htmlspecialchars_decode($v['info']);	
		}
		//p($syllabus);	
		$this->assign('syllabus',$syllabus);	
		//===========
		//教学环境
		$conditon9['type']=array('eq','environment');//教学环境		
		$environment=$train->where($conditon9)->field('id,title,info,picurl')->order('id asc')->limit(0,5)->select();
		foreach($environment as $k => &$v){
        $v["info"] = htmlspecialchars_decode($v['info']);	
		}	
		$this->assign('environment',$environment);	
		//===========
		$this->display();
	}
	//更多内容、新闻
	public function newslist(){
		import('Common.extend',APP_PATH,'.php');//截取中文字符串
		//============================
		$type=$_GET["type"];
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
		$this->assign('type',$type);
		$this->assign('name',$name);
		//=============	
		$train=M('Train');
		if (isset ($_GET ['type'])) {
			$conditon['type']=$_GET['type'];
		}
		
		$newsCnt=$train->where($conditon)->count();
		$Page=new Page($newsCnt,5);
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show=$Page->show();
		$newsInfo=$train->where($conditon)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();
		foreach($newsInfo as $k=>&$v){
			$v['info']= trim(htmlspecialchars_decode($v['info']));
			$str = $v['info'];
			$v['info'] = strip_tags($str);
		}
		$this->assign('newsInfo',$newsInfo);
		$this->assign('page',$show);
		$this->display();
	}
	public function newscontent(){

		$train=M('Train');

		if (isset ( $_GET ['type'] )) {
			$conditon['type']=array('eq',$_GET['type']);
		}
		if (isset ( $_GET ['nid'] )) {
			$conditon['id']=array('eq',$_GET['nid']);
		}

		$newsCnt=$train->where($conditon)->count();
		$newsInfo=$train->where($conditon)->find();
		$newsInfo['info']=htmlspecialchars_decode($newsInfo['info']);
		$this->assign('newsInfo',$newsInfo);

		$this->display();

	}
}