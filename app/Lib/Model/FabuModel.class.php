<?php

class FabuModel extends Model{
    
    protected $_validate =array(
                array('title','require','信息标题不能为空',1),
                array('type','require','信息所属类别必须选择',1),
            );
    
    protected $_auto = array (
        
            array('addtime','time',self::MODEL_INSERT,'function'),
            array('updatetime','time',self::MODEL_BOTH,'function'),
        
	);
}
