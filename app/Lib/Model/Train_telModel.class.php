<?php
class Train_telModel extends Model {
	protected $_validate =array(
		array('title','require','标题不能为空',1),
		array('info','require','简介不能为空',1),
		array('schedule','require','日常安排不能为空',1),	
	);
	protected $_auto = array (
		
		array('createtime','time',self::MODEL_INSERT,'function'),
		array('updatetime','time',self::MODEL_BOTH,'function'),		
		array('token','gettoken',self::MODEL_INSERT,'callback'),
	);
	function gettoken(){
		return session('token');
	}
}