<?php
class Distribution_setModel extends Model{
	protected $_validate =array(
		array('level1','require','分成比例设置不能为空',1),
		array('level2','require','分成比例设置不能为空',1),
		array('level3','require','分成比例设置不能为空',1),
		array('own','require','消费返还设置不能为空',1),  
		array('cash','require','提取兑换设置不能为空',1),  
	);
	protected $_auto = array (
		array('token','gettoken',self::MODEL_INSERT,'callback'),
	);
	function gettoken(){
		return session('token');
	}
}	
	
