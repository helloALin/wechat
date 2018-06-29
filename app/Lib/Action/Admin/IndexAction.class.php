<?php
//Leo测试使用类
class IndexAction extends Action{
	public function index(){
		echo '123';
		$map['pid']=5;
		$yuyue=M('Yuyue_setcin')->where($map)->select();

		$youhui=M('Yuyue_setcin')->where()->getField('youhui',true);

		p($youhui);

		$youhui[0]=$youhui[0]-'3';

		p($youhui);

		$arr=array();
		foreach ($youhui as $k=>$v){
			echo $v=$v-1;
			echo '<br />';
		}
		$arr=$youhui;
		$this->display();
	}
	public function test(){

		$map=array('pid'=>5);
		$yuyue=M('Yuyue_setcin')->where($map)->select();
		$this->assign('yuyue',$yuyue);
		$this->display();
	}
	public function unum(){
		$map=array('kind'=> '双人间','pid'=>5,'token'=> 'ewerun1402039733');

		$nn=M('Yuyue_order')->where($map)->sum(nums);

		echo '双人间已经有'.$nn.'人预定';
	}
	public function tnum(){
		$map=array('kind'=> '双人间','pid'=>5,'token'=> 'ewerun1402039733');
		$yuyue=M('Yuyue_setcin')->where($map)->getField('number');
	}
	public function tt(){
		$map['pid']=5;
		$yuyue=M('Yuyue_setcin')->where($map)->select();
		//p($yuyue);
		foreach ($yuyue as $k=>$v)
		{
			foreach($v as $kk=>$vv){

				//echo $kk.'---'.$vv.'<br />';
				echo $v['numbers'];
			}

		}
		p($yuyue);
	}

	public function ff(){
		$map['pid']=5;
		$yuyue=M('Yuyue_setcin')->where($map)->select();
		//p($yuyue);
		$list=array();
		foreach ($yuyue as $k=>$v){$list['$k']['numbers'];}

//		foreach ($yuyue as $k=>$v)
//		{
//
//				 $v['numbers']=$v['numbers']-10;
//				 echo '<br />';
//				 echo $v['numbers'];
//
//		}
		p($list);
	}
	public function aa(){
		$arr = array(  'one'=>array('name'=>'张三','age'=>'23','sex'=>'男'),
					    'two'=>array('name'=>'李四','age'=>'43','sex'=>'女'),
					    'three'=>array('name'=>'王五','age'=>'32','sex'=>'男'),
					    'four'=>array('name'=>'赵六','age'=>'12','sex'=>'女'));

		//$arr['one']['name']='名称';
		//p($arr);
		//exit;

		//方法一：使用&
//		foreach($arr as $k=>&$val){
//		   $val['age']=$val['age']-3;
//		   echo $val['age']."<br>";
//		}
		//方法二：  原来一样的~~
//		$tmp_data =$arr;
//		foreach($tmp_data as $k=>$val){
//
//		   $val['age']=$val['age']-3;
//		   echo $val['age']."<br>";
//		}
//		$arr=$tmp_data;

		p($arr);
	}
	public function form(){
		$sorts=$_POST['sort'];
		//$sorts=$_GET;
		p($sorts);
		$this->display();
	}
	public function dd(){
		$this->display();
	}
}