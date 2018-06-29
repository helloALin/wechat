<?php
class Tel_serviceModel extends Model{

	protected $_validate =array(
		array('nickname','require','客服昵称不能为空',1),
		array('tel','require','客服号码不能为空',1),
	);
	
	protected $_auto = array (

		array('createtime','time',self::MODEL_INSERT,'function'),
		array('updatetime','time',self::MODEL_BOTH,'function'),
		array('token','gettoken',self::MODEL_INSERT,'callback'),
		//array('click','0'),
	);
	
	function gettoken(){
		return session('token');
	}
	
}