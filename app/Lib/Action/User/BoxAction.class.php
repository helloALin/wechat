<?php
/**
 *拆盒子活动
**/
class BoxAction extends UserAction{
	
	public function _initialize() {
		parent::_initialize();
		// $token_open=M('token_open')->field('queryname')->where(array('token'=>session('token')))->find();
		// if(!strpos($token_open['queryname'],'Shake')){
  //           $this->error('您还开启该模块的使用权,请到功能模块中添加',U('Function/index',array('token'=>session('token'),'id'=>session('wxid'))));
		// }
	}
	
	public function index(){
		 $db=D('Box');
		 $where['token']=session('token');
		 $count=$db->where($where)->count();
		 $page=new Page($count,10);
		 $info=$db->where($where)->order('id desc')->limit($page->firstRow.','.$page->listRows)->select();
		 $this->assign('page',$page->show());
		 $this->assign('info',$info);
		 $this->display();
	}
        
	
	public function add(){
                $db=M('Box');
		if($_POST){
                    if($_POST['id']){
                        if($db->where(array('id'=>$_POST['id'],'token'=>$_POST['token']))->save($_POST)){
                        $this->success('修改成功',U('Box/index',array('token'=>session('token'))));
                        }
                    }else{
                        if($db->add($_POST)){
                        $this->success('添加成功',U('Box/index',array('token'=>session('token'))));
                        }
                    }
                    
                }else if($_GET['id']){
                    $info = $db->where(array('id'=>$this->_get('id','intval'),'token'=>$this->_get('token')))->find();
                    $this->assign('es_data', $info);
                    $this->assign('token', $this->_get('token'));
                    $this->display();
                }else{
		$this->display();
		}
	}
	
	public function change()
	{
		$db=M('Box');
		$where['token']=$this->_get('token');
		$where['id']=$this->_get('id','intval');
                if($this->_get('status')=='1'){
                    $num = $db->where(array('token'=>$this->_get('token'),'status'=>1))->count('id');
                    if($num>0){
                        $this->error('只能有一个活动是开启的，请先关闭其他开启的活动',U('Box/index',array('token'=>session('token'))));
                        exit;
                    }else{
                        $result = $db->where($where)->setField('status', $this->_get('status'));
                    }
                }else{
                    $result = $db->where($where)->setField('status', $this->_get('status'));
                }
		if($result!==false){$this->success('设置成功',U('Box/index',array('token'=>session('token'))));}
	}
        
        public function deleBox()
	{
		$db=M('Box');
		$where['token']=$this->_get('token');
		$where['id']=$this->_get('id','intval');
		$result = $db->where($where)->delete();
		if($result){$this->success('删除成功',U('Box/index',array('token'=>session('token'))));}
	}
        
        public function getPrizers(){
            $where = array('token'=>$_GET['token'],'pid'=>$_GET['id'],'isshare'=>1);
            $count=M('box_get')->where($where)->count();
            $page=new Page($count,10);
            $info=M('box_get')->where($where)->order('time desc')->limit($page->firstRow.','.$page->listRows)->select();
            $box = M('box')->where(array('token'=>$_GET['token'],'id'=>$_GET['id']))->find();
            
            foreach ($info as $k => $v) {
                switch (intval($v['info-prize']))
                {case 2:
                        $info[$k]['extnum'] = $box['awardname2'];
                        $info[$k]['isshare'] = $box['starttime2'];
                        $info[$k]['isget'] = $box['endtime2'];
                        break;
                 case 3:
                        $info[$k]['extnum'] = $box['awardname3'];
                        $info[$k]['isshare'] = $box['starttime3'];
                        $info[$k]['isget'] = $box['endtime3'];
                        break;
                 default:
                        $info[$k]['extnum'] = $box['awardname'];
                        $info[$k]['isshare'] = $box['starttime'];
                        $info[$k]['isget'] = $box['endtime'];
                }
            }
            $this->assign('page',$page->show());
            $this->assign('info', $info);
            $this->display();
        }
        
        public function getAward(){
            $where = array('token'=>$_GET['token'],'id'=>$_GET['id']);
            if(time()<strtotime($_GET['begintime']) || time()>strtotime($_GET['endtime'])){
                $this->error('抱歉！兑换时间超出奖品有效时间',U('Box/getPrizers',array('token'=>$_GET['token'],'id'=>$_GET['pid'])));
            }else{
                if(M('box_get')->where($where)->save(array('getnum'=>0,'sntime'=>time()))){
                   $this->success('兑换成功',U('Box/getPrizers',array('token'=>$_GET['token'],'id'=>$_GET['pid'])));
                   
                    //兑完奖之后微信推送消息
                   $content = $_GET['prizer']."（先生/女士），您于\r\n".date('Y-m-d H:i:s',time())."\r\n成功兑换奖品，感谢您的参与。";

                   $access_token = getAccessToken($_GET['token']);
                   if($access_token["status"]){	
	                   $data='{"touser":"'.$_GET['wecha_id'].'","msgtype":"text","text":{"content":"'.$content.'"}}';
	                   $url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token["info"];
	                   curlPost($url,$data);
                   }else{
					   Log::write("BoxAction.getAward - getAccessToken:".$access_token["info"]);
                   }
                   
                }else{
                    $this->error('兑换失败',U('Box/getPrizers',array('token'=>$_GET['token'],'id'=>$_GET['pid'])));
                }
            }
        }
}
?>