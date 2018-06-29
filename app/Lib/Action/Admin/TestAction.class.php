<?php
class TestAction extends Action{

	public function dd(){
		$s='注册<a href="http://www.baidu.com">会员</a>享受更多优惠';

		$s2='&lt;a href=&quot;http://www.baidu.com&quot;&gt;百度&lt;/a&gt;';

		$content=htmlspecialchars_decode($s);
		echo $content;
		echo '<br />';
		$ct2=htmlspecialchars($s);
		echo $ct2;
	}
	public function htm(){
		$s='&lt;a href=&quot;http://www.baidu.com&quot;&gt;百度&lt;/a&gt;';

		$ct1=htmlspecialchars_decode($s);
		echo $ct1;
		echo '<br />';
		$ct2=htmlspecialchars($s);
		echo $ct2;
	}

	public function hh(){
		$s='注册<a href="http://www.baidu.com">会员</a>享受更多优惠';
		$ct1=htmlspecialchars($s);
		echo $ct1;
		echo '<br />';
	}

	public function ss(){
		$this->success('修改成功',U('Test/zhuce'));
	}

	public function zhuce(){
		$s='注册<a href="http://www.baidu.com">会员</a>享受更多优惠';
		echo $s;
	}

	public function uu(){
		$arr = array("你好","我很好","我非常不好");//数组
		$sarr = serialize($arr);//产生一个可存储的值(用于存储)
		p($sarr);

		//然后把$sarr的值string(60) "a:3:{i:0;s:7:"测试1";i:1;s:7:"测试2";i:2;s:7:"测试3";}"保存在$newarr中
		$newarr='a:3:{i:0;s:6:"你好";i:1;s:9:"我很好";i:2;s:15:"我非常不好";}';
		$unsarr=unserialize($newarr);
		p($unsarr);
	}

}