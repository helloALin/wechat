<?php
class PublicAction extends BaseAction{
	public function footer(){
		$where['status']=1;
		$links=D('Links')->where($where)->select();
		$this->assign('links',$links);
	}
	public function verify(){
        import('ORG.Util.Image');
        Image::buildImageVerify();
    }
	
}