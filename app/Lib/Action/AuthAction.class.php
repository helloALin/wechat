<?php
class AuthAction extends BaseAction{
	
	/**
	 * 登录验证
	 */
	protected function loginAuth(){
		//登录验证
		if(session("LOGIN_NAME") == null)
			$this->redirect("User/Index/login");
	}
	
	/**
	 * 进入公众号管理页面验证
	 */
	/* protected function publicAuth(){
		parent::_initialize();
		$this->loginAuth();
		if(session("token") == null)
			$this->redirect("Index/publicMng");
	} */
	
}