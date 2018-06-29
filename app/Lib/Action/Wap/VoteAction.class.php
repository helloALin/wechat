<?php

class VoteAction extends BaseAction
{
    /*public function index()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (!strpos($agent, "icroMessenger")) {
            //echo '此功能只能在微信浏览器中使用';
            //exit;
        }
        $token    = $this->_get('token');
        $wecha_id = $this->_get('wecha_id');
        $id       = $this->_get('id');
        $t_vote   = M('Vote');
        $t_record = M('Vote_record');
        $where    = array(
            'token' => $token,
            'id' => $id
        );
        $vote     = $t_vote->where($where)->find();
        if (empty($vote)) {
            exit('非法操作');
        }
        $vote_item = M('Vote_item')->where('vid=' . $vote['id'])->select();
        $this->assign('count', $t_record->where(array(
            'vid' => $id
        ))->count());
        $where       = array(
            'wecha_id' => $wecha_id,
            'vid' => $id
        );
        $vote_record = $t_record->where($where)->find();
        $total       = $t_record->where('vid=' . $id)->count('touched');
        $item_count  = M('Vote_item')->where('vid=' . $id)->select();
        foreach ($item_count as $k => $value) {
            $vote_item[$k]['per'] = (number_format(($value['vcount'] / $total), 4)) * 100;
        }
        $this->assign('vote_item', $vote_item);
        $this->assign('vote', $vote);
        $this->display();
    }*/
	public function index(){
		$where['token']=$this->token;
		//$kefu=M('Kefu')->where($where)->find();
		//$this->assign('kefu',$kefu);
		$agent = $_SERVER['HTTP_USER_AGENT']; 
		if(!strpos($agent,"icroMessenger")) {
			echo '此页面只能在微信浏览器中使用';exit;
		}
		$wecha_id	= $this->_get('wecha_id');
        $id         = $this->_get('id');
		//echo $_SERVER['HTTP_REFERER'];
		if(empty($wecha_id)){
			echo "请求地址不正确，请重新点击链接进入";exit;
		}
		//针对邮政教师节投票使用的验证
		$randCode = session("randCode".$wecha_id);
		if($id == '1' && strpos($_SERVER['HTTP_REFERER'], "http://121.8.157.221/chinapost/JumpPageAction!init.do") !== 0
			&& strpos($_SERVER['HTTP_REFERER'], "http://121.8.157.202") !== 0
			&& strpos($_SERVER['HTTP_REFERER'], "http://202.105.44.4") !== 0
			&& (empty($randCode) || $randCode != $_GET["randCode"])
			){
			//echo "非法访问，请重新点击链接进入";exit;
		}
		/*
        $imageLocation = 'http://c.cnzz.com/wapstat.php';
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $query = array();
        array_push($query, 'siteid=' . 1252947963 * 1);
        array_push($query, 'r=' . urlencode($referer));
        array_push($query, 'rnd=' . mt_rand(1, 2147483648));
        $imageUrl = $imageLocation . '?' . implode('&', $query);
        $this->assign("cznnImgurl",$imageUrl);*/
		//针对邮政教师节投票使用的验证 结束
		
		$token		= $this->_get('token');
        $this->assign('token',$token);
        $this->assign('wecha_id',$wecha_id);
        $this->assign('id',$id);

		$t_vote		= M('Vote');
        $t_record	= M('Vote_record');
		$where 		= array('token'=>$token,'id'=>$id);
		$vote 	= $t_vote->where($where)->find();
        if(empty($vote)){
            exit('非法操作');
        }
        $vote_item = M('Vote_item')->where(array('vid'=>$vote['id']))->order('rank')->select(); 
        $vcount =  M('Vote_item')->where(array('vid'=>$vote['id']))->sum("vcount");
        $this->assign('count',$vcount);
        //检查是否投票过
        $t_item = M('Vote_item');
        $where = array('wecha_id'=>$wecha_id,'vid'=>$id);
        $vote_record  = $t_record->where($where)->find();
        if($vote_record && $vote_record != NULL){
            $arritem = trim($vote_record['item_id'],',');
            $map['id'] = array('in',$arritem);
            $hasitems = $t_item->where($map)->field('item')->select();
            $this->assign('hasitems',$hasitems);
            $this->assign('vote_record',1);
        }else{
            $this->assign('vote_record',0);
        }

        $item_count = M('Vote_item')->where('vid='.$id)->order('rank')->select();
        foreach ($item_count as $k=>$value) {
           $vote_item[$k]['per']=(number_format(($value['vcount'] / $vcount),3))*100;
           $vote_item[$k]['pro']=$value['vcount'];
        } 
		//$this->assign('status',$t_vote.status);
        $this->assign('total',$total);
        $this->assign('vote_item', $vote_item);
        $this->assign('vote',$vote);
		$this->display();
	}
    public function add_vote()
    {
		if(!strpos($_SERVER['HTTP_USER_AGENT'],"icroMessenger")) {
            echo json_encode(array("success"=>0)); exit;
		}
        $token    = $this->_post('token');
        $wecha_id = $this->_post('wecha_id');
        $tid      = $this->_post('tid');
        $chid     = rtrim($this->_post('chid'), ',');
        $recdata  = M('Vote_record');
        $where    = array(
            'vid' => $tid,
            'wecha_id' => $wecha_id
        );
        $recode   = $recdata->where($where)->find();
        if ($recode != '' || $wecha_id == '') {
            $arr = array(
                'success' => 0
            );
            echo json_encode($arr);
            exit;
        }
        $data      = array(
            'item_id' => $chid,
            'vid' => $tid,
            'wecha_id' => $wecha_id,
            'touch_time' => time(),
            'touched' => 1
        );
        $ok        = $recdata->add($data);
        $map['id'] = array(
            'in',
            $chid
        );
        $t_item    = M('Vote_item');
        $t_item->where($map)->setInc('vcount');
        $total      = M('Vote_record')->where('vid=' . $tid)->count('touched');
        $item_count = M('Vote_item')->where('vid=' . $tid)->select();
        foreach ($item_count as $value) {
            $per[$value['id']] = (number_format(($value['vcount'] / $total), 4)) * 100;
        }
		$rand_code = mt_rand();
        $arr = array(
            'success' => 1,
            'token' => $token,
            'wecha_id' => $wecha_id,
            'tid' => $tid,
            'chid' => $chid,
            'arrpre' => $per,
			'randCode' => $rand_code
        );
		//保存投票完成随机码，用于邮政教师节投票，授权用户刷新页面
		session("randCode".$wecha_id, $rand_code);
        echo json_encode($arr);
        exit;
    }
	
	function result(){
		$this->display();
	}
}
?>