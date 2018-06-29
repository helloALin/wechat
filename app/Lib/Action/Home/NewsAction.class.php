<?php
class NewsAction extends BaseAction{
	//报名表
	public function index(){

		$news=M('Fabu');

		if (isset ( $_GET ['type'] )) {
			$conditon['type']=array('eq',$_GET['type']);
		}

		$newsCnt=$news->where($conditon)->count();

		$Page=new Page($newsCnt,5);
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show=$Page->show();

		$newsInfo=$news->where($conditon)->limit($Page->firstRow.','.$Page->listRows)->select();

		$this->assign('newsInfo',$newsInfo);
		$this->assign('page',$show);
		$this->display();
	}
	public function newslist(){

		//import('Extend.Function.extend');
		import('Common.extend',APP_PATH,'.php');//截取中文字符串
		$news=M('Fabu');

		if (isset ( $_GET ['type'] )) {
			$conditon['type']=array('eq',$_GET['type']);
		}

		$newsCnt=$news->where($conditon)->count();

		$Page=new Page($newsCnt,9);
		$nowPage = isset($_GET['p'])?$_GET['p']:1;
		$show=$Page->show();

		$newsInfo=$news->where($conditon)->limit($Page->firstRow.','.$Page->listRows)->order('id desc')->select();

		foreach($newsInfo as $k=>&$v){

			//$v['info']= substr_cut(htmlspecialchars_decode($v['info']),700);
			$v['info']= trim(htmlspecialchars_decode($v['info']));
			$str = $v['info'];
//			$str = trim($str);
//		    $str = ereg_replace("\t","",$str);
//		    $str = ereg_replace("\r\n","",$str);
//		    $str = ereg_replace("\r","",$str);
//		    $str = ereg_replace("\n","",$str);
//		    $str = ereg_replace(" ","",$str);
			$v['info'] = strip_tags($str);

		//$v['info']= trim($v['info']);
		}
//		p($newsInfo);
//		die;
		$this->assign('newsInfo',$newsInfo);
		$this->assign('page',$show);
		$this->display();
	}

	public function newscontent(){

		$news=M('Fabu');

		if (isset ( $_GET ['type'] )) {
			$conditon['type']=array('eq',$_GET['type']);
		}
		if (isset ( $_GET ['nid'] )) {
			$conditon['id']=array('eq',$_GET['nid']);
		}

		$newsCnt=$news->where($conditon)->count();

		//$newsContent=$news->where($conditon)->getField('info');
		$newsInfo=$news->where($conditon)->find();


		$newsInfo['info']=htmlspecialchars_decode($newsInfo['info']);


	    //$newsInfo['info']=strip_tags($newsInfo['info']);

		//$newsInfo['info']= htmlspecialchars_decode($newsInfo['info']);

		//$newsInfo=strip_tags($newsInfo);


		$this->assign('newsInfo',$newsInfo);

		$this->display();

	}

	public function test(){

		//$this->newslist();
		$News = A("News"); // 实例化NewsAction控制器对象
		$News->newslist(); // 调用News模块的newslist操作方法
	}

}