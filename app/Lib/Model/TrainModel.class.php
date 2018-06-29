<?php

class TrainModel extends Model{
    
    // protected $_validate =array(
                
    //             array('picurl','require','图片不能为空',1),
                
    //         );
    
    protected $_auto = array (
        
            array('addtime','time',self::MODEL_INSERT,'function'),
            array('updatetime','time',self::MODEL_BOTH,'function'),
        
	);
}
