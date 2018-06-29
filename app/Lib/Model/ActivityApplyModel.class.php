<?php
class ActivityApplyModel extends Model{

	protected $_validate =array(
		array('name',	'require',	'姓名不能为空', self::MODEL_BOTH),
		array('activity_id',	'require','提交失败请刷新页面重试', self::MODEL_BOTH),
		array('tel',	'require',	'手机不能为空',3),
		array('tel',	'/^1[3578]\d{9}$/','手机格式不正确，请输入正确的11位手机号码！',3),
		array('content','/^http[s]?:\/\//','备注填写的视频地址URL不正确！',3),
		array('activity_id,tel,name', '', '您已经报过名了，无需要重新报名！', 1, 'unique'),
	);
	
	protected $_auto = array (
		array('create_time','time',self::MODEL_INSERT,'function'),
		array('update_time','time',self::MODEL_BOTH,'function'),
	);
}