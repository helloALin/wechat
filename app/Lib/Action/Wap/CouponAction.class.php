<?php
class CouponAction extends LotteryBaseAction{
	public $token;
	public $wecha_id;
	public $lottory_record_db;
	public $lottory_db;
	public function __construct(){
		$agent = $_SERVER['HTTP_USER_AGENT'];
		if(!strpos($agent,"icroMessenger")) {
			//echo '此功能只能在微信浏览器中使用';exit;
		}
		$this->token=$this->_get('token');
		$this->wecha_id	= $this->_get('wecha_id');
		$this->lottory_record_db=M('Lottery_record');
		$this->lottory_db=M('Lottery');
		if (!defined('RES')){
			define('RES',THEME_PATH.'common');
		}
		if (!defined('STATICS')){
			define('STATICS',TMPL_PATH.'static');
		}
	}
	public function index(){
		$token		= $this->token;
		$wecha_id	= $this->wecha_id;
		$id 		= $this->_get('id');
		$Lottery 	= $this->lottory_db->where(array('id'=>$id,'token'=>$token,'type'=>3,'status'=>1))->find();
		$Lottery['renametel']=$Lottery['renametel']?$Lottery['renametel']:'手机号';
		$Lottery['renamesn']=$Lottery['renamesn']?$Lottery['renamesn']:'SN码';
		$this->assign('lottery',$Lottery);
		//var_dump($Lottery);
		//0. 判断优惠券是否领完了
		$data=$this->prizeHandle($token,$wecha_id,$Lottery);
	
		$data['token'] 		= $token;
		$data['wecha_id']	= $wecha_id;		
		$data['lid']		= $Lottery['id'];
		$data['phone']		= $data['phone']; 
		$data['usenums']	= $data['usenums'];
		$data['sendtime']	= $data['sendtime'];
		$data['canrqnums']	= $Lottery['canrqnums'];
		$data['fist'] 		= $Lottery['fist'];
		$data['second'] 	= $Lottery['second'];
		$data['third'] 		= $Lottery['third'];
		$data['fistnums'] 	= $Lottery['fistnums'];
		$data['secondnums'] = $Lottery['secondnums'];
		$data['thirdnums'] 	= $Lottery['thirdnums'];	
		$data['info']		= $Lottery['info'];
		$data['aginfo']		= $Lottery['aginfo'];
		$data['txt']		= $Lottery['txt'];
		$data['sttxt']		= $Lottery['sttxt'];
		$data['title']		= $Lottery['title'];
		$data['statdate']	= $Lottery['statdate'];
		$data['enddate']	= $Lottery['enddate'];
		$data['info']=nl2br($data['info']);
		$data['endinfo']=nl2br($data['endinfo']);	
		$this->assign('Coupon',$data);
		$vip=M('userinfo')->where(array('token'=>$token,'wecha_id'=>$wecha_id))->select();//vip查找数据
		$this->assign('tel',$vip[0]['tel']);
		$this->assign('wechaname',$vip[0]['wechaname']);
		//var_dump($data);exit();
		//判断是否已经填写了手机号码（前提是获得了wechatid） by leo
		if($wecha_id){
			$Lottery_record_db = M('Lottery_record');
			$Lottery_record_info = $Lottery_record_db->where(array('lid' => $id, 'wechat_id' => $wecha_id,'token'=>$token, 'islottery' => 1))->getField('phone');
			
			$this->assign('phone',$Lottery_record_info);
		}
		$this->display();
	}
}
	
?>