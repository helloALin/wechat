<?php
class LotteryAction extends LotteryBaseAction{
	public function index(){
		$agent = $_SERVER['HTTP_USER_AGENT'];
		if(!strpos($agent,"icroMessenger")) {
			//echo '此功能只能在微信浏览器中使用';exit;
		}
		$token		= $this->_get('token');
		$wecha_id	= $this->_get('wecha_id');
		$id 		= $this->_get('id');
		
		$redata		= M('Lottery_record');
		$where 		= array('token'=>$token,'wecha_id'=>$wecha_id,'lid'=>$id);
		$record 	= $redata->where(array('token'=>$token,'wecha_id'=>$wecha_id,'lid'=>$id,'islottery'=>1))->find();
		if (!$record){
			$record 	= $redata->where($where)->order('id DESC')->find();
		}
		
		$Lottery 	= M('Lottery')->where(array('id'=>$id,'token'=>$token,'type'=>1,'status'=>1))->find();
		$Lottery['renametel']=$Lottery['renametel']?$Lottery['renametel']:'手机号';
		$Lottery['renamesn']=$Lottery['renamesn']?$Lottery['renamesn']:'SN码';
		$data=$Lottery;
		//1.活动过期,显示结束
		//4.显示奖项,说明,时间
		if ($Lottery['enddate'] < time()) {
			 $data['end'] = 1;
			 $endinfo = M('Lottery')->where(array('id'=>$id))->getField('endinfo');
			 $data['endinfo'] = $endinfo;
			 $this->assign('Dazpan',$data);
			 $this->display();
			 exit();
		}
		// 1. 中过奖金	
		if ($record['islottery'] == 1) {				
			$data['end'] = 5;
			$data['sn']	 	 = $record['sn'];
			$data['uname']	 = $record['wecha_name'];
			$data['prize']	 = $record['prize'];
			$data['tel'] 	 = $record['phone'];	
		}
		//抽取次数
		$data['On'] 		= 1;
		$data['token'] 		= $token;
		$data['wecha_id']	= $wecha_id;		
		$data['lid']		= $Lottery['id'];
		$data['rid']		= intval($record['id']);
		$data['usenums'] 	= $record['usenums'];
		$data['info']=str_replace('&lt;br&gt;','<br>',$data['info']);
		$data['endinfo']=str_replace('&lt;br&gt;','<br>',$data['endinfo']);
		$this->assign('Dazpan',$data);
		$record['id']=intval($record['id']);
		$this->assign('record',$record);
		$vip=M('userinfo')->where(array('token'=>$token,'wecha_id'=>$wecha_id))->select();//vip查找数据
		$this->assign('tel',$vip[0]['tel']);
		$this->assign('wechaname',$vip[0]['wechaname']);
		//var_dump($data);exit();
		$now = time();
		$year        = date('Y', $now);
		$month       = date('m', $now);
		$day         = date('d', $now);
		$firstSecond = mktime(0, 0, 0, $month, $day, $year);
		$lastSecond  = mktime(23, 59, 59, $month, $day, $year);
		$dayWhere    = 'wecha_id=\'' . $wecha_id . '\' AND lid=' . $id . ' AND time>' . $firstSecond . ' AND time<' . $lastSecond;
		$thisDayNums = $redata->where($dayWhere)->count();
		$thisDayNums = intval($thisDayNums);
		$this->assign('nums',$thisDayNums);
		$this->display();
	}
	
	
	
	public function getajax(){	
		
		$token 		=	$this->_get('token');
		$wecha_id	=	$this->_get('oneid');
		$id 		=	$this->_get('id');
		$rid 		= 	$this->_get('rid');
		$Lottery=M('Lottery')->where(array('id'=>$id))->find();
		$data=$this->prizeHandle($token,$wecha_id,$Lottery);
		if ($data['end']==3){
			$sn	 	 = $data['sn'];
			$uname	 = $data['wecha_name'];
			$prize	 = $data['prize'];
			$tel 	 = $data['phone'];
			$msg = "您已经中过了";
			echo '{"error":1,"msg":"'.$msg.'"}';
			exit;
		}
		if ($data['end']==-1){
			$msg = $data['winprize'];
			echo '{"error":1,"msg":"'.$msg.'"}';
			exit;
		}
		if ($data['end']==-2){
			$msg = $data['winprize'];
			echo '{"error":1,"msg":"'.$msg.'"}';
			exit;
		}
		//
		if ($data['prizetype'] >= 1 && $data['prizetype'] <= 6) {
			echo '{"success":1,"sn":"'.$data['sncode'].'","prizetype":"'.$data['prizetype'].'","usenums":"'.$data['usenums'].'"}';
		}else{
			echo '{"success":0,"prizetype":"","usenums":"'.$data['usenums'].'"}';
		}
		exit();
	}
}
	
?>