<?php
/**
 *文本回复
**/
class TestAction extends UserAction {
	public function index() {
		echo '<img src="'.U('Test/qrcode', array('str'=>'http://jingyan.baidu.com/article/48a42057bff0d2a925250464.html')).'" width="180"/><br/>';
		echo '<img src="'.U('Test/qrcode', array('str'=>'微信公众平台：思维与逻辑;公众号:siweiyuluoji')).'" width="180"/><br/>';
	}
	
	public function qrcode(){
		if(empty($_GET['str'])){
			echo '参数错误';exit;
		}
		//include "tpl/phpqrcode/phpqrcode.php";
		Vendor("phpqrcode.phpqrcode");
		//Vendor("PHPExcel.PHPExcel"); // 引入phpexcel类(注意你自己的路径)
		//定义纠错级别
		$errorLevel = "L";
		//定义生成图片宽度和高度;默认为3
		$size = "4";
		//定义生成内容
		QRcode::png($_GET['str'], false, $errorLevel, $size);
	}
	public function ercode(){
		
		Vendor("phpqrcode.phpqrcode");
		//定义纠错级别
		$errorLevel = "L";
		//定义生成图片宽度和高度;默认为3
		$size = "4";
		//定义生成内容
		QRcode::png($_GET['str'], false, $errorLevel, $size);
		
		echo '<img src="'.U('Test/qrcode', array('str'=>'http://jingyan.baidu.com/article/48a42057bff0d2a925250464.html')).'" width="180"/><br/>';
		
		
	} 
}
?>