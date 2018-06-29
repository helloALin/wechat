<?php
/**
 *文本回复
**/
class CommentAction extends UserAction{
	//留言列表
	public function index(){
		//评论内容
		$map['c.imgid']=$_GET['id'];
		$map['c.token']=session('token');
		$count=M()->table("tp_comment c")-> join('tp_wxuser_people p on c.wecha_id=p.wecha_id')->where($map)->count();
		$page=new Page($count,25);
		$commentInfo =M()->table("tp_comment c")-> join('tp_wxuser_people p on c.wecha_id=p.wecha_id')->where($map)->order('c.createtime DESC')->field("c.*,p.nickname,p.headimgurl")->limit($page->firstRow . ',' . $page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('commentInfo',$commentInfo);	
		//p($commentInfo);
		$this->display();
	}
	//添加留言试图
	public function setok()
    {
        $id = intval($this->_get('id'));
        $where = array('id' => $id, 'token' => $this->token);
        $data['uptatetime'] = time();
        $data['status'] = 1;
        $back = M('comment')->where($where)->save($data);
        if ($back == true) {
            $this->success('成功显示评论');
        } else {
            $this->error('操作失败');
        }
    }
    public function setnot()
    {
        $id = $this->_get('id');
        $where = array('id' => $id, 'token' => $this->token);
        $data['uptatetime'] ='';
        $data['status'] = 0;
        $back = M('comment')->where($where)->save($data);
        if ($back == true) {
            $this->success('成功隐藏该评论');
        } else {
            $this->error('操作失败');
        }
    }
	public function commentDel(){
		$id = $_GET['id'];
		if(IS_GET){
			$db = D('comment');
			if($db->delete($id)){
				$this->success('删除成功');
			}else{
				$this->error('删除失败');
			}
			
		}else{
			$this->error('非法操作');
		}
	}
	public function preview(){
		$db=M('Img');
		$where['token']=$this->_get('token','trim');
		//评论内容
		$map['c.token']=$this->token;
		$map['c.imgid']=$_GET['id'];
		$map['c.status']=1;
		$commentInfo =M()->table("tp_comment c")-> join('tp_wxuser_people p on c.wecha_id=p.wecha_id')->where($map)->order('c.createtime DESC')->field("c.*,p.nickname,p.headimgurl")->select();
		$this->assign('commentInfo',$commentInfo);
		$this->display();
	}
}
?>