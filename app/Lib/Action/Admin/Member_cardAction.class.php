<?php
//会员控制器
class Member_cardAction extends BackAction{
	public function _initialize() {
        parent::_initialize();  //RBAC 验证接口初始化

    }
    //会员管理
    //会员卡
    public function index(){

    	$card_set_model = M('Member_card_set');
    	$map['token']=TOKEN;
    	$cards = $card_set_model->where($map)->order('id ASC')->select();
        if ($cards) {
            $card_create_data = M('Member_card_create');
            $i                = 0;
            foreach ($cards as $card) {

		        $where['cardid']   = CARDID;
		        $where['token']    = TOKEN;
		        $where['wecha_id'] = array('neq','');

                $cards[$i]['usercount'] = $card_create_data->where($where)->count();
                $i++;
            }
        }
        $this->assign('cards', $cards);
        //=============================================
		//优惠券
		$member_card_coupon_db = M('Member_card_coupon');
		$map['token']=TOKEN;
		$map['cardid']=CARDID;

		//$data  = $member_card_coupon_db->where($map)->order('id desc')->select();
		//$this->assign('data_vip', $data);

		$count     = $member_card_coupon_db->where($map)->count();
        $Page      = new Page($count, 15);
        $show      = $Page->show();
        $data      = $member_card_coupon_db->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->order('id desc')->select();

        $this->assign('data_vip', $data);
        $this->assign('page', $show);

		//=============================================
        $this->display();
    }
    //进入会员管理(所有会员)
    public function members(){
		$card_create_db    = M('Member_card_create');
		$cardid=intval($_GET['id']);

        $where             = array();
        $where['cardid']   = intval($_GET['id']);
        $where['token']    = TOKEN;
        $where['wecha_id'] = array('neq','');
        if (IS_POST) {
            if (isset($_POST['searchkey']) && trim($_POST['searchkey'])) {
                $where['number'] = array(
                    'like',
                    '%' . trim($_POST['searchkey']) . '%'
                );
            }
        }

		if(!empty($_POST)){
			$p=$_POST['searchkey'];

			if(''==$p){
				$c=0;
			}
			else{
				$c=1;
			}
		}
        $count     = $card_create_db->where($where)->count();
        $Page      = new Page($count, 15);
        $show      = $Page->show();
        $list      = $card_create_db->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $members   = $card_create_db->where($where)->order('id ASC')->select();

        $wecha_ids = array();
        if ($members) {
            foreach ($members as $member) {
                array_push($wecha_ids, $member['wecha_id']);
            }
            $userinfo_db                = M('Userinfo');
            $userinfo_where['wecha_id'] = array('in',$wecha_ids);
            $users                      = $userinfo_db->where($userinfo_where)->select();
            $usersArr                   = array();
            if ($users) {
                foreach ($users as $u) {
                    $usersArr[$u['wecha_id']] = $u;
                }
            }
            $i = 0;
            foreach ($members as $member) {
                $thisUser                    = $usersArr[$member['wecha_id']];
                $members[$i]['truename']     = $thisUser['truename'];
                $members[$i]['wechaname']    = $thisUser['wechaname'];
                $members[$i]['qq']           = $thisUser['qq'];
                $members[$i]['tel']          = $thisUser['tel'];
                $members[$i]['getcardtime']  = $thisUser['getcardtime'];
                $members[$i]['expensetotal'] = $thisUser['expensetotal'];
                $members[$i]['total_score']  = $thisUser['total_score'];
                $i++;
            }
            $this->assign('members', $members);
            $this->assign('page', $show);

        }
        $this->assign('p', $p);
        $this->assign('c', $c);
        $this->assign('cnt', $count);
        $this->assign('cardid', $cardid);

        $this->display();
    }

    //删除会员
    public function member_del()
    {
        $card_create_db = M('Member_card_create');
        $thisMember     = $card_create_db->where(array(
            'id' => intval($_GET['itemid'])
        ))->find();
        $thisUser       = M('Userinfo')->where(array(
            'token' => TOKEN,
            'wecha_id' => $thisMember['wecha_id']
        ))->find();
        $where          = array(
            'wecha_id' => $thisUser['wecha_id'],
            'token' => $this->token
        );
        M('Member_card_sign')->where($where)->delete();
        M('Member_card_use_record')->where($where)->delete();
        M('Userinfo')->where($where)->delete();
        $card_create_db->where(array(
            'id' => intval($_GET['itemid'])
        ))->save(array(
            'wecha_id' => ''
        ));
        $this->success('操作成功');
    }
    //查看单个会员信息
	public function member()
    {
        $card_create_db = M('Member_card_create');
        $thisMember     = $card_create_db->where(array(
            'id' => intval($_GET['itemid'])
        ))->find();

       // p($thisMember);

        $thisUser       = M('Userinfo')->where(array(
            'token' => $thisMember['token'],
            'wecha_id' => $thisMember['wecha_id']
        ))->find();

        $this->assign('thisUser', $thisUser);
        $members[0]                 = $thisMember;
        $members[0]['truename']     = $thisUser['truename'];
        $members[0]['wechaname']    = $thisUser['wechaname'];
        $members[0]['qq']           = $thisUser['qq'];
        $members[0]['tel']          = $thisUser['tel'];
        $members[0]['getcardtime']  = $thisUser['getcardtime'];
        $members[0]['expensetotal'] = $thisUser['expensetotal'];
        $members[0]['total_score']  = $thisUser['total_score'];
        $this->assign('members', $members);


        $record_db = M('Member_card_use_record');
        $where     = array(
            'wecha_id' => $thisUser['wecha_id'],
            'token' => TOKEN,
        );
        $count     = $record_db->where($where)->count();
        $Page      = new Page($count, 15);
        $show      = $Page->show();
        $list      = $record_db->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order('id desc')->select();

        $this->assign('records', $list);
        $this->assign('page', $show);
		//=============================================
		//优惠券
		$member_card_coupon_db = M('Member_card_coupon');
		$map['token']=TOKEN;
		$map['cardid']=CARDID;
		$now                   = time();
		$map['statdate']=array('lt',$now);
		$map['enddate']=array('gt',$now);
		$vipcoupon  = $member_card_coupon_db->where($map)->order('create_time desc')->select();
		if ($vipcoupon) {
            $i = 0;
            foreach ($vipcoupon as $n) {
                $vipcoupon[$i]['info']       = html_entity_decode($n['info']);	//html_entity_decode() 函数把 HTML 实体转换为字符。
                $vipcoupon[$i]['useCount']   = M('Member_card_use_record')->where(array(
                    'itemid' => $n['id'],		//具体到某一张优惠券
                    'cat' => 3,
                    'wecha_id' =>$_GET['wid']	//某一个会员
                ))->sum('usecount');
                $vipcoupon[$i]['nouseCount'] = intval($n['people']) - $vipcoupon[$i]['useCount'];
                $i++;
            }
        }
        $this->assign('firstItemID', $vipcoupon[0]['id']);
        $this->assign('vipcoupon', $vipcoupon);
        $this->assign('wid', $_GET['wid']);
        $this->assign('cid', CARDID);
		//p($vipcoupon);
		//=============================================
        $this->display();
    }

    //会员优惠券使用的处理（已经整合到member方法里面↑）
	//会员优惠券
	public function _thisCard()
    {
        $member_card_set_db = M('Member_card_set');
        $thisCard           = $member_card_set_db->where(array(
            'token' => TOKEN,
            'id' => intval($_GET['cid'])
        ))->find($thisCard);
        return $thisCard;
    }
    //会员优惠券
    public function coupon()
    {
        $this->assign('infoType', 'coupon');
        $thisCard = $this->_thisCard();
        $this->assign('thisCard', $thisCard);
        $map['token']=TOKEN;
		$map['cardid']=CARDID;
		$now                   = time();
		$map['statdate']=array('lt',$now);
		$map['enddate']=array('gt',$now);
        $member_card_coupon_db = M('Member_card_coupon');
        $list  = $member_card_coupon_db->where($map)->order('create_time desc')->select();
        if ($list) {
            $i = 0;
            foreach ($list as $n) {
                $list[$i]['info']       = html_entity_decode($n['info']);
                $list[$i]['useCount']   = M('Member_card_use_record')->where(array(
                    'itemid' => $n['id'],
                    'cat' => 3,
                    'wecha_id' => $_GET['wid']	//某一个会员
                ))->sum('usecount');
                $list[$i]['nouseCount'] = intval($n['people']) - $list[$i]['useCount'];//剩余的数量
                $i++;
            }
        }
        $this->assign('firstItemID', $list[0]['id']);
        $this->assign('list', $list);
        $this->display();
    }

	function action_useCoupon()
    {
//    	$db       = M('Member_card_coupon');
//    	$map['statdate'] =$_GET['wid'];	//某一张会员优惠券

        $now = time();
        if (IS_POST) {
            $itemid   = intval($_POST['itemid']);
            $db       = M('Member_card_coupon');
            $map['token']=TOKEN;
			$map['cardid']=CARDID;
			$now                   = time();
			$map['statdate']=array('lt',$now);
			$map['enddate']=array('gt',$now);
            $thisItem = $db->where('id=' . $itemid . ' AND statdate<' . $now . ' AND enddate>' . $now)->find();
            if (!$thisItem) {
                echo '{"success":-2,"msg":"不存在指定信息"}';
                exit();
            }
            $member_card_set_db = M('Member_card_set');
            $thisCard           = $member_card_set_db->where(array(
                'id' => intval($thisItem['cardid'])
            ))->find();
            if (!$thisCard) {
                echo '{"success":-3,"msg":"会员卡不存在"}';
                exit();
            }
            $userinfo_db = M('Userinfo');
            $thisUser    = $userinfo_db->where(array(
                'token' => $thisCard['token'],
                'wecha_id' => $this->_post('wecha_id')
            ))->find();
            $useTime     = intval($_POST['usetime']);
            $useCount    = M('Member_card_use_record')->where(array(
                'itemid' => $itemid,
                'cat' => 3,
                'wecha_id' => $this->_post('wecha_id')
            ))->sum('usecount');
            $useCount    = $useCount + $useTime;
            if (intval($useCount) > intval($thisItem['people'])) {
                echo '{"success":-5,"msg":"最多能用' . $thisItem['people'] . '次"}';
                exit();
            }
            $staff_db  = M('Company_staff');
            $thisStaff = $staff_db->where(array(
                'username' => $this->_post('username'),
                'token' => $thisCard['token']
            ))->find();
            if (!$thisStaff) {
                echo '{"success":-4,"msg":"用户名和密码不匹配"}';
                exit();
            } else {
                if (md5($this->_post('password')) != $thisStaff['password']) {
                    echo '{"success":-4,"msg":"用户名和密码不匹配"}';
                    exit();
                } else {
                    $arr             = array();
                    $arr['itemid']   = $this->_post('itemid');
                    $arr['wecha_id'] = $this->_post('wecha_id');
                    $arr['expense']  = $this->_post('money');
                    $arr['time']     = $now;
                    $arr['token']    = $thisItem['token'];
                    $arr['cat']      = $this->_post('cat');
                    $arr['staffid']  = $thisStaff['id'];
                    $arr['usecount'] = $useTime;
                    $set_exchange    = M('Member_card_exchange')->where(array(
                        'cardid' => intval($thisCard['id'])
                    ))->find();
                    $arr['score']    = intval($set_exchange['reward']) * $arr['expense'];
                    M('Member_card_use_record')->add($arr);
                    $userArr                 = array();
                    $userArr['total_score']  = $thisUser['total_score'] + $arr['score'];
                    $userArr['expensetotal'] = $thisUser['expensetotal'] + $arr['expense'];
                    $userinfo_db->where(array(
                        'token' => $thisCard['token'],
                        'wecha_id' => $arr['wecha_id']
                    ))->save($userArr);
                    $thisuseCount = intval($thisItem['usetime']) + $useTime;
                    $db->where(array(
                        'id' => $itemid
                    ))->save(array(
                        'usetime' => $thisuseCount
                    ));
                    echo '{"success":1,"msg":"成功提交记录"}';
                }
            }
        } else {
            echo '{"success":-1,"msg":"不是post数据"}';
        }
    }
    //===============================================================================
    //会员优惠券功能
    //会员优惠券的使用统计
    public function useRecords(){
    	$types = array(
            'vip' => 1,
            'coupon' => 3,
            'integral' => 2
        );
        $type  = $_GET['type'];
        if (!$types[$type]) {
            exit('no type');
        }
        switch ($type) {
            //特权
			case 'vip':
                $a = 'privilege';
                break;
			//积分
            case 'integral':
			//优惠券
            case 'coupon':
                $a = $type;
                break;
        }
        $this->assign('a', $a);
        $db       = M('Member_card_' . $type);
        $thisItem = $db->where(array(
            'id' => intval($_GET['itemid'])
        ))->find();
        $this->assign('thisItem', $thisItem);
        $record_db = M('Member_card_use_record');
        $where     = array(
            'itemid' => $thisItem['id'],
            'cat' => $types[$type]
        );
        $count     = $record_db->where($where)->count();
        $Page      = new Page($count, 15);
        $show      = $Page->show();
        $list      = $record_db->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order('id desc')->select();
        $wecha_ids = array();
        $staffids  = array();
        if ($list) {
            foreach ($list as $l) {
                if (!in_array($l['wecha_id'], $wecha_ids)) {
                    array_push($wecha_ids, $l['wecha_id']);
                }
                if (!in_array($l['staffid'], $staffids)) {
                    array_push($staffids, $l['staffid']);
                }
            }
            $userinfo_where['wecha_id'] = array(
                'in',
                $wecha_ids
            );
            $users                      = M('Userinfo')->where($userinfo_where)->select();
            $usersArr                   = array();
            if ($users) {
                foreach ($users as $u) {
                    $usersArr[$u['wecha_id']] = $u;
                }
            }
            $cards    = M('Member_card_create')->where($userinfo_where)->select();
            $cardsArr = array();
            if ($cards) {
                foreach ($cards as $u) {
                    $cardsArr[$u['wecha_id']] = $u;
                }
            }
            $staffWhere = array(
                'in',
                $staffids
            );
            $staffs     = M('Company_staff')->where($staffWhere)->select();
            $staffsArr  = array();
            if ($staffs) {
                foreach ($staffs as $s) {
                    $staffsArr[$s['id']] = $s;
                }
            }
        }
        if ($list) {
            $i = 0;
            foreach ($list as $l) {
                $list[$i]['userName'] = $usersArr[$l['wecha_id']]['truename'];
                $list[$i]['userTel']  = $usersArr[$l['wecha_id']]['tel'];
                $list[$i]['cardNum']  = $cardsArr[$l['wecha_id']]['number'];
                $list[$i]['operName'] = $staffsArr[$l['staffid']]['name'];
                $i++;
            }
        }
        $this->assign('page', $show);
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->display();
    }

    //会员优惠券使用统计的删除
    public function useRecord_del()
    {
        $record_db  = M('Member_card_use_record');
        $thisRecord = $record_db->where(array(
            'id' => intval($_GET['itemid'])
        ))->find();
        if ($thisRecord['token'] != TOKEN) {
            exit('error');
        }
        if ($thisRecord['cat']) {
            switch ($thisRecord['cat']) {
                case 1:
                    $type = 'vip';
                    break;
                case 2:
                    $type = 'integral';
                    break;
                case 3:
                    $type = 'coupon';
                    break;
            }
            $db       = M('Member_card_' . $type);
            $thisItem = $db->where(array(
                'id' => $thisRecord['itemid']
            ))->find();
        }
        $record_db->where(array(
            'id' => intval($_GET['itemid'])
        ))->delete();
        $userinfo_db             = M('Userinfo');
        $thisUser                = $userinfo_db->where(array(
            'token' => $this->token,
            'wecha_id' => $thisRecord['wecha_id']
        ))->find();
        $userArr                 = array();
        $userArr['total_score']  = $thisUser['total_score'] - $thisRecord['score'];
        $userArr['expensetotal'] = $thisUser['expensetotal'] - $thisRecord['expense'];
        $userinfo_db->where(array(
            'token' => $this->token,
            'wecha_id' => $thisRecord['wecha_id']
        ))->save($userArr);
        if ($thisRecord['itemid']) {
            $useCount = $thisItem['usetime'];
            $useCount = intval($useCount) - $thisRecord['usecount'];
            $db->where(array(
                'id' => $thisRecord['itemid']
            ))->save(array(
                'usetime' => $useCount
            ));
        }
        $this->success('操作成功');
    }
	//会员优惠券的删除
     public function coupon_del()
    {
        $this->_isUseRecordExist(3, $_GET['itemid']);
        $data = M('Member_card_coupon')->where(array(
            'token' => TOKEN,
            'id' => $this->_get('itemid')
        ))->delete();
        if ($data == false) {
            $this->error('没删除成功');
        } else {
            $this->success('操作成功', U('Member_card/index', array(
                'id' => $this->thisCard['id']
            )));
        }
    }
	function _isUseRecordExist($cat, $itemid)
    {
        $record_db  = M('Member_card_use_record');
        $thisRecord = $record_db->where(array(
            'itemid' => intval($itemid),
            'cat' => intval($cat)
        ))->find();
        if ($thisRecord) {
            $this->error('请先删除该信息下的所有使用记录，然后再删除本信息');
        }
    }

}