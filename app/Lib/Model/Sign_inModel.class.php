<?php
class Sign_inModel extends Model{
	protected $_validate =array(
	
		array('everyday','require','每天签到奖励设置不能为空',1),
		array('continuation','require','连续7天签到奖励设置不能为空',1),
		
	);
	protected $_auto = array (
		array('token','gettoken',self::MODEL_INSERT,'callback'),
	);
	function gettoken(){
		return session('token');
	}
}	
	
