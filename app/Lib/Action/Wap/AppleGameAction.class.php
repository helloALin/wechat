<?php
class AppleGameAction extends LotteryBaseAction{
    public function __construct(){
        parent :: _initialize();
        $id = $this -> _get('id', "intval");
        if(!$id) $this -> error('不存在的活动');
        $wecha_id = $this -> _get('wecha_id');
        $token = $this -> _get('token');
        $Lottery = M('Lottery') -> field('statdate,enddate,canrqnums,aginfo,title') -> where(array('id' => $id, 'token' => $token, 'type' => 7)) -> find();
        if(!$Lottery) $this -> error('不存在的活动');
        $record = M('Lottery_record') -> field('usenums') -> where(array('token' => $token, 'wecha_id' => $wecha_id, 'lid' => $id)) -> find();
        if ($Lottery['statdate'] > time()){
            $this -> error('活动未开始，请在' . date('Y-m-d H:i:s', $Lottery['statdate']) . '后再来参加活动!');
        }
        $this -> assign('canrqnums', $Lottery['canrqnums']);
        $this -> assign('aginfo', $Lottery['aginfo']);
        $this -> assign('title', $Lottery['title']);
    }
    public function index(){
        $id = (int)$this -> _get('id');
        $wecha_id = $this -> _get('wecha_id');
        $token = $this -> _get('token');
        $Lottery_record = M('Lottery_record');
        $Lottery = M('Lottery') -> where(array('id' => $id, 'token' => $token, 'type' => 7)) -> find();
        $record = $Lottery_record -> where(array('token' => $token, 'lid' => $id)) -> order('time DESC') -> limit(10) -> select();
        foreach($record as $k => $v){
            $p = M('wxuserPeople') -> where(array('token' => $token, 'wecha_id' => $v['wecha_id'])) -> getField('headimgurl');
            if($p){
                $record[$k]['portrait'] = $p;
            }else{
                $record[$k]['portrait'] = '/tpl/User/default/common/images/portrait.jpg';
            }
            if($v['wecha_name'] == ''){
                $record[$k]['wecha_name'] = '游客';
            }
        }
        $myinfo = $Lottery_record -> where(array('token' => $token, 'lid' => $id, 'wecha_id' => $wecha_id)) -> find();
        if($myinfo){
            $myinfo['portrait'] = M('wxuserPeople') -> where(array('token' => $token, 'wecha_id' => $wecha_id)) -> getField('headimgurl');
            if(!$myinfo['portrait']) $myinfo['portrait'] = '/tpl/User/default/common/images/portrait.jpg';
            $rank = 1;
            $rank += $Lottery_record -> where("token = '$token' AND lid = $id AND time > " . $myinfo['time']) -> count();
            $myinfo['rank'] = $rank;
        }
        $prizeStr = '<p>一等奖: ' . $Lottery['fist'];
        if ($Lottery['displayjpnums']){
            $prizeStr .= '奖品数量:' . $Lottery['fistnums'];
        }
        $prizeStr .= '</p>';
        if ($Lottery['second']){
            $prizeStr .= '<p>二等奖: ' . $Lottery['second'];
            if ($Lottery['displayjpnums']){
                $prizeStr .= '奖品数量:' . $Lottery['secondnums'];
            }
            $prizeStr .= '</p>';
        }
        if ($Lottery['third']){
            $prizeStr .= '<p>三等奖: ' . $Lottery['third'];
            if ($Lottery['displayjpnums']){
                $prizeStr .= '奖品数量:' . $Lottery['thirdnums'];
            }
            $prizeStr .= '</p>';
        }
        $this -> assign('prizeStr', $prizeStr);
        $this -> assign('myinfo', $myinfo);
        $this -> assign('linfo', $Lottery);
        $this -> assign('record', $record);
		$config = getWXJSConfig($token);
		//$config['debug']=true;  //调试模式
		$config['jsApiList']=array('onMenuShareTimeline','onMenuShareAppMessage');
		$this->assign("config", json_encode($config));
        $this -> display();
    }
    public function game(){
        $token = $this -> _get('token');
        $wecha_id = $this -> _get('wecha_id');
        $id = $this -> _get('id');
        if(empty($wecha_id)) 
        	$this -> error('请从该公众号的聊天窗口进入活动');
        $redata = M('Lottery_record');
        $where = array('token' => $token, 'wecha_id' => $wecha_id, 'lid' => $id);
        $record = $redata -> where(array('token' => $token, 'wecha_id' => $wecha_id, 'lid' => $id)) -> find();
        $Lottery = M('Lottery') -> field('statdate,enddate,canrqnums,aginfo,title') -> where(array('id' => $id, 'token' => $token, 'type' => 7)) -> find();
        if ($record){
            if($record['usenums'] >= $Lottery['canrqnums']) 
            	$this -> error($Lottery['aginfo']);
        }
        if ($Lottery['enddate'] < time()){
            $this -> error('活动已结束');
        }
        $Lottery = M('Lottery') -> where(array('id' => $id, 'token' => $token, 'type' => 7)) -> find();
        if ((!$this -> fans) && $Lottery['needreg']){
            $this -> error('请先完善个人资料再参加活动', U('Userinfo/index', array('token' => $this -> token, 'wecha_id' => $this -> wecha_id, 'redirect' => MODULE_NAME . '/index|id:' . intval($id))));
        }
        $this -> assign('record', $record);
        $this -> assign('linfo', $Lottery);
		$config = getWXJSConfig($token);
		$config['jsApiList']=array('onMenuShareTimeline','onMenuShareAppMessage');
		$this->assign("config", json_encode($config));
        $this -> display();
    }
    
    public function gameOver(){
        $data['time'] = $this->_post('num','intval');
        $data['lid'] = (int)$this -> _get('id');
        $data['token'] = $this -> _get('token');
        $data['wecha_id'] = $this -> _get('wecha_id');
        $Lottery_record = M('Lottery_record');
        $where = array('token' => $data['token'], 'wecha_id' => $data['wecha_id'], 'lid' => $data['lid']);
        $record = $Lottery_record -> field('time,usenums') -> where($where) -> find();
        if($record){
            if($record['time'] < $data['time']){
                $update['time'] = $data['time'];
            }
            $update['usenums'] = $record['usenums'] + 1;
            $Lottery_record -> where($where) -> setField($update);
            if($record['time'] < $data['time']){
                echo "{'usenums':" . ($record['usenums'] + 1) . ",'best':" . $data['time'] . "}";
            }else{
                echo "{'usenums':" . ($record['usenums'] + 1) . ",'best':" . $record['time'] . "}";
            }
        }else{
            $userinfo = M('wxuserPeople') -> where(array('token' => $data['token'], 'wecha_id' => $data['wecha_id'])) -> getField('nickname');
            if($userinfo){
                $data['phone'] = '';
                $data['wecha_name'] = $userinfo;
            }else{
                $data['phone'] = '';
                $data['wecha_name'] = '游客';
            }
            $data['usenums'] = 1;
            $data['sendstutas'] = 0;
            $data['islottery'] = 0;
            $data['sn'] = '';
            $data['prize'] = '';
            $data['sendtime'] = 0;
            $Lottery_record -> add($data);
            echo '{"usenums":1,"best":' . $data['time'] . '}';
        }
    }
}
?>