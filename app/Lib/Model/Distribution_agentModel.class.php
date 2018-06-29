<?php
class Distribution_agentModel extends Model{
	protected $_validate =array(
		array('level1','require','银眼资格设置不能为空',1),
		array('level2','require','金眼资格设置不能为空',1),
		array('level3','require','钻眼资格设置不能为空',1),
		array('storeFee','require','店铺管理费设置不能为空',1),
	);
	protected $_auto = array (
		array('token','gettoken',self::MODEL_INSERT,'callback'),
	);
	function gettoken(){
		return session('token');
	}
}	
	
