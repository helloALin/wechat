<?php

class LogisticsModel extends Model{
    
    protected $_validate =array(
				array('status','require','信息所属类别必须选择',1),
                array('logname','require','物流公司不能为空',1),
                array('fee','require','费用不能为空',1),
            );
    
    protected $_auto = array (
		array('token','gettoken',self::MODEL_INSERT,'callback'),
	);
	function gettoken(){
		return session('token');
	}
}
