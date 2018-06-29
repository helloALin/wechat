<?php
class AddressModel extends Model{

	protected $_validate =array(
		array('name','require','姓名不能为空', self::MODEL_BOTH),
		array('tel','require','手机不能为空',3),
		array('province','require','省份不能为空',3),
		array('city','require','市区不能为空',3),
		array('county','require','县区不能为空',3),
		array('address','require','地址不能为空',3),
		array('postcode','/^[1-9]\d{5}$/','邮编格式不正确，请输入正确的6位邮编！',2),
		array('tel','/^1[3578]\d{9}$/','手机格式不正确，请输入正确的11位手机号码！',2),
	);
	
	protected $_auto = array (
		array('create_time','time',self::MODEL_INSERT,'function'),
		array('update_time','time',self::MODEL_BOTH,'function'),
	);
}