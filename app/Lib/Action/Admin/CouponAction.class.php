<?php
class CouponAction extends BackAction{
	//没有BackAction html没有样式
	public function _initialize() {
        parent::_initialize();  //RBAC 验证接口初始化

    }
	public function index()
    {
		$where['token']=TOKEN;
		$where['type']=3;
		$count = M('Lottery')->where($where)->count();

        $Page       = new Page($count);// 实例化分页类 传入总记录数
        // 进行分页数据查询 注意page方法的参数的前面部分是当前的页数使用 $_GET[p]获取
        $nowPage = isset($_GET['p'])?$_GET['p']:1;
        $show       = $Page->show();// 分页显示输出

        $list = M('Lottery')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('count', $count);
        $this->assign('list', $list);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }
    //index使用了$where['token']=TOKEN; 其实后面不需要再去确定token了。
	//SN码管理
	public function sn()
    {
		//活动类型ID
    	$type=3;
        $Lottery_record_db = M('Lottery_record');
        //活动ID
        $id = intval($this->_get('id'));
        $data = M('Lottery')->where(array('token' => TOKEN, 'id' => $id, 'type' => $type))->find();
		//中奖总人数
        $recordcount = (((($data['fistlucknums'] + $data['secondlucknums']) + $data['thirdlucknums']) + $data['fourlucknums']) + $data['fivelucknums']) + $data['sixlucknums'];
        //奖品总数
        $datacount = (((($data['fistnums'] + $data['secondnums']) + $data['thirdnums']) + $data['fournums']) + $data['fivenums']) + $data['sixnums'];
        //已发奖品数量
        $sendCount = $Lottery_record_db->where(array('lid' => $id, 'sendstutas' => 1, 'islottery' => 1))->count();
        $this->assign('thisLottery', $data);
        $this->assign('sendCount', $sendCount);
        $this->assign('datacount', $datacount);
        $this->assign('recordcount', $recordcount);
		//======================================================
        $recordWhere['lid']   = $id;
        $recordWhere['token']    = TOKEN;
        $recordWhere['sn'] = array('neq','');

        $count     = $Lottery_record_db->where($recordWhere)->count();
        $Page      = new Page($count, 15);
        $show      = $Page->show();
        $record      = $Lottery_record_db->where($recordWhere)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        //$wecha_id = $Lottery_record_db->where($recordWhere)->getField('wecha_id',true);

        if ($record) {
            $i = 0;
            foreach ($record as $r) {
                switch (intval($r['prizetype'])) {
                default:
                    $record[$i]['prizeName'] = $r['prize'];		//奖项
                    break;
                case 1:
                    $record[$i]['prizeName'] = $data['fist']; 	//一等奖
                    break;
                case 2:
                    $record[$i]['prizeName'] = $data['second'];	//二等奖
                    break;
                case 3:
                    $record[$i]['prizeName'] = $data['third'];	//三等奖
                    break;
                case 4:
                    $record[$i]['prizeName'] = $data['four'];		//四等奖
                    break;
                case 5:
                    $record[$i]['prizeName'] = $data['five'];		//五等奖
                    break;
                case 6:
                    $record[$i]['prizeName'] = $data['six'];		//六等奖
                    break;
                }
                $i++;
            }
        }
        //===================================================================
		//判断是不是会员
		foreach($record as $k=>&$v){

		$vip = M('Member_card_create');
		$map['token']=TOKEN;
		$map['wecha_id']  = $v['wecha_id'];
		$isvip=$vip->where($map)->count(); //如果是会员，使用count（）得到$isvip的值是1.
		//lid字段数据库存的值在html页面没有使用，所以这里使用来存放$isvip的值。
		if(0==$isvip){
			$v['lid']=0;
		}else{$v['lid']=1;}

		}
		//=============================
        $this->assign('record', $record);
        $this->assign('page', $show);
        $this->display();
    }
    //查看会员领取者的信息(这是领卡时填写的信息)
    public function vipinfo(){
		$userinfo=M('Userinfo');
    	$map['token']=TOKEN;
		$map['wecha_id']  =$_GET['wid'];

		$vipinfo=$userinfo->where($map)->select();
			foreach($vipinfo as $k=>&$v)
			{

				$vip = M('Member_card_create');
				$where['token']=TOKEN;
				$where['wecha_id']  = $v['wecha_id'];
				$cardinfo=$vip->where($map)->getField('number');

				$v['wecha_id']=$cardinfo;
			}

		$this->assign('vipinfo',$vipinfo);
		$this->display();
    }
    //SN码管理-发奖状态
    	//未发奖
    public function sendnull()
    {
        $id = intval($this->_get('id'));
        $where = array('id' => $id, 'token' => TOKEN);
        //发奖时间空，发奖状态0
        $data['sendtime'] = '';
        $data['sendstutas'] = 0;

        $back = M('Lottery_record')->where($where)->save($data);
        if ($back == true) {
            $this->success('优惠券状态确定为未使用');
        } else {
            $this->error('操作失败');
        }
    }
    	//已发奖
    public function sendprize()
    {
        $id = $this->_get('id');
        $where = array('id' => $id, 'token' => $this->token);
        //发奖时间当前，发奖状态1
        $data['sendtime'] = time();
        $data['sendstutas'] = 1;
        $back = M('Lottery_record')->where($where)->save($data);
        if ($back == true) {
            $this->success('优惠券状态确定为使用');
        } else {
            $this->error('操作失败');
        }
    }
    //SN码管理-中奖记录删除
    public function snDelete()
    {
        $db = M('Lottery_record');
        $rt = $db->where(array('id' => intval($_GET['id'])))->find();
        if (TOKEN != $rt['token']) {
            die('no permission');
        }
        $db->where(array('id' => intval($_GET['id'])))->delete();
        $this->success('操作成功');
    }

}