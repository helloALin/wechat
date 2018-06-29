<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class WxpayAction{
    
    public function pay(){
        /**
		* JS_API支付dem
		* ====================================================
		* 在微信浏览器里面打开H5网页中执行JS调起支付。接口输入输出数据格式为JSON。
		* 成功调起支付需要三个步骤：
		* 步骤1：网页授权获取用户openid
		* 步骤2：使用统一支付接口，获取prepay_id
		* 步骤3：使用jsapi调起支付
	   */
	import("@.ORG.WxPayPubHelper.JsApi_pub");
	import("@.ORG.WxPayPubHelper.UnifiedOrder_pub");
	//使用jsapi接口
	$jsApi = new JsApi_pub();

	//=========步骤1：网页授权获取用户openid============
	//通过code获得openid
	if (!isset($_GET['code']))
	{
		//触发微信返回code码
		$url = $jsApi->createOauthUrlForCode(urlencode(C('site_url'). $_SERVER['REQUEST_URI']));
		Header("Location: $url"); 
	}else
	{
		//获取code码，以获取openid
	    $code = $_GET['code'];
		$jsApi->setCode($code);
		$openid = $jsApi->getOpenId();
		if(empty($openid)){
			$jumpUrl = C('site_url').'/index.php?g=Wap&m=Product&a=index&token='.$_GET['token'].'&store_id='.$_GET['store_id'];
			Header("Location: $jumpUrl");
		}
	}
	$product_cart_model=M('product_cart');
	$thisOrder=$product_cart_model->where(array('id'=>intval($_GET['id'])))->find();
	$thisOrder['time'] = date('Y-m-d H:i:s',$thisOrder['time']);
	//检查权限
//	if ($thisOrder['wecha_id']!=$this->wecha_id){
//		exit();
//	}
	$carts=unserialize($thisOrder['info']);
	$orderName='';
	$totalFee=0;
	$totalCount=0;
	$products='';
	$ids=array();
	foreach ($carts as $k=>$c){
		if (is_array($c)){
			$productid=$k;
			$price=$c['price'];
			$count=$c['count'];
			//
			if (!in_array($productid,$ids)){
				array_push($ids,$productid);
			}
			$totalFee+=$price*$count;
			$totalCount+=$count;
		}
	}
	if (count($ids)){
		$list=M('product')->where(array('id'=>array('in',$ids)))->select();
	}
	if ($list){
		$i=0;
		foreach ($list as $p){
			if($i==count($list,0)-1){
				$orderName = $orderName.$p['name'];
			}else{
				$orderName = $orderName.$p['name'].'/';
			}
			$list[$i]['count']=$carts[$p['id']]['count'];
			$products .= '<div class="yesOrders"><p class="orderName">'.$p['name'].'</p> 
							<div class="ordersCon"><img src="'.$p['logourl'].'" />
							<div class="xd">
								<p>售价：<span class="black">￥'.$p['price'].'</span></p>
								<p>数量：<span class="black">'.$carts[$p['id']]['count'].'</span></p>
							</div>
							<div class="clear"></div>
							</div>
							</div>';		
			$i++;
		}
	}
	//=========步骤2：使用统一支付接口，获取prepay_id============
	//使用统一支付接口
	$unifiedOrder = new UnifiedOrder_pub();
	
	//设置统一支付接口参数
	//设置必填参数
	//appid已填,商户无需重复填写
	//mch_id已填,商户无需重复填写
	//noncestr已填,商户无需重复填写
	//spbill_create_ip已填,商户无需重复填写
	//sign已填,商户无需重复填写
	$unifiedOrder->setParameter("openid",$openid);//商品描述
	$unifiedOrder->setParameter("body",  trim($orderName));//商品描述
	//自定义订单号，此处仅作举例
	//$timeStamp = time();
	//$out_trade_no = WxPayConf_pub::APPID.$timeStamp;
	$unifiedOrder->setParameter("out_trade_no",$thisOrder['orderid']);//商户订单号 
	//$unifiedOrder->setParameter("total_fee",$totalFee*100);//总金额
	$unifiedOrder->setParameter("total_fee",$thisOrder['paymoney']*100);//应付金额
	$unifiedOrder->setParameter("notify_url",WxPayConf_pub::NOTIFY_URL);//通知地址 
	$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
	//非必填参数，商户可根据实际情况选填
	//$unifiedOrder->setParameter("sub_mch_id","XXXX");//子商户号  
	//$unifiedOrder->setParameter("device_info","XXXX");//设备号 
	$unifiedOrder->setParameter("attach",$this->trimall($orderName));//附加数据 
	//$unifiedOrder->setParameter("time_start","XXXX");//交易起始时间
	//$unifiedOrder->setParameter("time_expire","XXXX");//交易结束时间 
	//$unifiedOrder->setParameter("goods_tag","XXXX");//商品标记 
	//$unifiedOrder->setParameter("openid","XXXX");//用户标识
	//$unifiedOrder->setParameter("product_id","XXXX");//商品ID
	$prepay_id = $unifiedOrder->getPrepayId();
	//=========步骤3：使用jsapi调起支付============
	$jsApi->setPrepayId($prepay_id);
	$jsApiParameters = $jsApi->getParameters();
echo '<!DOCTYPE HTML>
<html lang="zh-CN">
<head>
	<meta charset="utf-8" />
	<title>微信安全支付</title>
	<meta content="yes" name="apple-mobile-web-app-capable">
	<meta content="yes" name="apple-touch-fullscreen">
	<meta name="viewport" content="initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no" />
	<meta name="format-detection" content="telephone=no" />
	<link type="text/css" rel="stylesheet" href="/tpl/Wap/default/common/css/product/css/style.css" />
	<script src="/tpl/Wap/default/common/css/product/js/jquery.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="/tpl/Wap/default/common/css/product/js/main.js"></script>
    <style type="text/css">
	.lay_toptab a i{height: 41px;line-height: 60px;color: #fff;font-style: normal;}
    </style>
	<link type="text/css" rel="stylesheet" href="/tpl/Wap/default/common/css/product/css/stylep.css?1" /> 
	
	<script>
	function drop_confirm(msg, url){
		if(confirm(msg)){
			window.location = url;
		}
	}
	//调用微信JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			\'getBrandWCPayRequest\',
			'.$jsApiParameters.',
			function(res){
				WeixinJSBridge.log(res.err_msg);
				if(res.err_msg==\'get_brand_wcpay_request:ok\')
				{
					setTimeout("window.location.href =\'/index.php?g=Wap&m=Product&a=my&token='.$_GET['token'].'\'",100);

				}else{
				}
			}
		);
	}

	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
			if( document.addEventListener ){
				document.addEventListener(\'WeixinJSBridgeReady\', jsApiCall, false);
			}else if (document.attachEvent){
				document.attachEvent(\'WeixinJSBridgeReady\', jsApiCall); 
				document.attachEvent(\'onWeixinJSBridgeReady\', jsApiCall);
			}
		}else{
			jsApiCall();
		}
	}
	</script>
</head>

<body>
<div class="ordersLists">
	<!-----------收货地址---------------->
	<div class="yesOrders customerInfo">
    	<p class="grey">联系姓名：<span class="black">'.$thisOrder['truename'].'</span></p>
        <p class="grey">联系电话：<span class="black">'.$thisOrder['tel'].'</span></p>
        <p class="grey third">联系地址：<span class="black">'.$thisOrder['address'].'</span></p>
    </div>
    <!-----------订单列表---------------->
	'.$products.'
    <!-----------订单状态---------------->
    <div class="yesOrders customerInfo">
    	<p class="grey">订单状态：
		<span class="redBG">待付款</span>
		</p>
    	<p class="grey">订单编号：<span class="black">'.$thisOrder['orderid'].'</span></p>
        <p class="grey">下单时间：<span class="black">'.$thisOrder['time'].'</span></p>
        <p class="grey">支付方式：<span class="black">微信支付</span></p>
        <p class="grey third">订单金额：<span class="red">￥'.$totalFee.'</span></p>
		<!--by leo-->
		<p class="grey">抵扣：<span class="red">￥'.floatval($thisOrder['deduct']).'</span></p>
		<p class="grey">应付：<span class="red">￥'.floatval($thisOrder['paymoney']).'</span></p>
    </div>
    <!-----------支付---------------->
	<div class="payBtn">
    	<a href="javascript:drop_confirm(\'确认取消?\', \'/index.php?g=Wap&m=Product&a=cancelOrder&token='.$_GET['token'].'&store_id='.$_GET['store_id'].'&id='.$thisOrder['id'].'\');" class="btn_blue btn">取消订单</a>
		<a href="javascript:callpay()" class="btn_red btn">立即支付</a>
    </div>
</div>
</body></html>';exit();
    }
    public function notice(){
	/**
	* 通用通知接口demo
	* ====================================================
	* 支付完成后，微信会把相关支付和用户信息发送到商户设定的通知URL，
	* 商户接收回调信息后，根据需要设定相应的处理流程。
	* 
	* 这里举例使用log文件形式记录回调信息。
   */
	import("@.ORG.WxPayPubHelper.Notify_pub");

    //使用通用通知接口
	$notify = new Notify_pub();
        
	//存储微信的回调
	$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
	$notify->saveData($xml);
	Log::write("【接收到的notify通知】:\n".$xml."\n");
	
	//插入notice_detail记录通知内容
	$recode = M('notice_detail')->where(array('out_trade_no'=>$notify->data['out_trade_no']))->find();
	if(empty($recode)){
		$detail['openid'] = $notify->data['openid'];
		$detail['is_subscribe'] = $notify->data['is_subscribe'];
		$detail['return_code'] = $notify->data['return_code'];
		$detail['return_msg'] = $notify->data['return_msg'];
		$detail['result_code'] = $notify->data['result_code'];
		$detail['err_code_des'] = $notify->data['err_code_des'];
		$detail['appid'] = $notify->data['appid'];
		$detail['bank_type'] = $notify->data['bank_type'];
		$detail['total_fee'] = $notify->data['total_fee']/(100.0);
		$detail['transaction_id'] = $notify->data['transaction_id'];
		$detail['out_trade_no'] = $notify->data['out_trade_no'];
		$detail['attach'] = $notify->data['attach'];
		$detail['time_end'] = $notify->data['time_end'];
		M('notice_detail')->add($detail);
	}else{
		$detail['id'] = $recode['id'];
		$detail['openid'] = $notify->data['openid'];
		$detail['is_subscribe'] = $notify->data['is_subscribe'];
		$detail['return_code'] = $notify->data['return_code'];
		$detail['return_msg'] = $notify->data['return_msg'];
		$detail['result_code'] = $notify->data['result_code'];
		$detail['err_code_des'] = $notify->data['err_code_des'];
		$detail['appid'] = $notify->data['appid'];
		$detail['bank_type'] = $notify->data['bank_type'];
		$detail['total_fee'] = $notify->data['total_fee']/(100.0);
		$detail['transaction_id'] = $notify->data['transaction_id'];
		$detail['out_trade_no'] = $notify->data['out_trade_no'];
		$detail['attach'] = $notify->data['attach'];
		$detail['time_end'] = $notify->data['time_end'];
		M('notice_detail')->save($detail);
	}
	//验证签名，并回应微信。
	//对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
	//微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
	//尽可能提高通知的成功率，但微信不保证通知最终能成功。
	if($notify->checkSign() == FALSE){
		Log::write("【验证签名失败】:\n".$xml."\n");
		$notify->setReturnParameter("return_code","FAIL");//返回状态码
		$notify->setReturnParameter("return_msg","签名失败");//返回信息
	}else{
        if ($notify->data["return_code"] == "FAIL") {
			//此处应该更新一下订单状态，商户自行增删操作
			Log::write("【通信出错】:\n".$xml."\n");
		}
		elseif($notify->data["result_code"] == "FAIL"){
			//此处应该更新一下订单状态，商户自行增删操作
			Log::write("【业务出错】:\n".$xml."\n");
		}
		else{
			//此处应该更新一下订单状态，商户自行增删操作
			Log::write("【支付成功】:\n".$xml."\n");
			//修改订单状态ostatus=2待发货
			$Product_cart = M("product_cart"); 
			$product = $Product_cart->where(array('orderid'=>$notify->data["out_trade_no"]))->find();
			//防止二次通知操作 只能是操作待付款状态的订单 即ostatus=1
			if(!empty($product) && $product['ostatus']==1){
				$data['ostatus'] = '2';
				$data['paytime'] = strtotime($notify->data["time_end"]);//订单支付时间
				$Product_cart->where(array('id'=>$product['id']))->save($data);
				//购物成功之后，返利(需获取当前的会员等级，所以在代理自动等级前进行返利操作)
				$this->rebate($product['id'],$product['token'], strtotime($notify->data["time_end"]));
				//购物成功之后，自动成为对应等级的代理
				$this->autoStore($product['wecha_id'], $product['token'], $product['id'], $product['price'], $product['store_id']);
				
				//购买成功之后刷新所有上家的广告圈收入
				$this->refreshAdIncome($product);
				//该订单混搭,扣除微币，记录写入rebate_record表
				if($product['otype']==2){
					$this->mashup($product);	
				}
				//
				$a = $notify->data["time_end"];
				$payTime = substr($a, 0,4).'-'.substr($a, 4, 2).'-'.substr($a, 6, 2).' '.substr($a, 8, 2).':'.substr($a, 10, 2).':'.substr($a, 12, 2);

				//增加微信回复
				$content = "支付成功通知\r\n你好，你的商品已支付成功。\r\n付款金额： ".$notify->data["total_fee"]/(100.0)."元\r\n商品详情： ".$notify->data["attach"]."\r\n商家订单号：".$notify->data["out_trade_no"]."\r\n交易单号：".$notify->data["transaction_id"]."\r\n支付时间：".$payTime;

				$access_token = getAccessToken($product['token']);
				if($access_token['status']){
					$data='{"touser":"'.$notify->data["openid"].'","msgtype":"text","text":{"content":"'.$content.'"}}';
					$url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token['info'];
					$this->api_notice_increment($url,$data);
				}else{
					Log::write("Wap/ProductAction.orderCart - getAccessToken:".$access_token["info"]);
				}
			}
			
            $notify->setReturnParameter("return_code","SUCCESS");//设置返回码
		}
		
	}
	$returnXml = $notify->returnXml();
	echo $returnXml;
	exit();
    }
	
	//购物成功之后，返利
	/**
	 * 返利规则：返利分为返积分和返微币
	 * 返积分：未成为代理(VIP=0)用户的订单, 以distribution_set表的own列为比例
	 * 返微币：1.给用户的上级(fid),上上级(gid)，上上上级(ggid)进行返利
	 * 		   2.根据distribution_set表的level1, level2, level3为比例 
	 * 		   3.用户为vip=3时distribution_agent表的level3的倍数部分的消费再乘以80%
	 * @param int $id
	 * @param int $token
	 * @param unknown $payTime
	 */
	private function rebate($id, $token, $payTime){
		//读取配置表
		$token_where = array('token'=>$token);
		$agentInfo 	= M('distribution_agent')->where($token_where)->find();
		$setInfo	= M('distribution_set')->where($token_where)->find();
		$cart_model = M('product_cart');
		if(empty($setInfo)){
			$this->writeUpLevelLog('返利参数未配置', Log::ERR);
			return;
		}
		//获取订单信息
		$where = array('id' => $id, 'token' =>$token, 'moneystatus'=>0);//moneystatus=0 订单未操作返利
		$financeInfo=$cart_model->where($where)->find();
		if(empty($financeInfo)){
			$this->writeUpLevelLog('未找到匹配订单信息，cart_id:'.$id, Log::ERR);
			return;
		}
		
		$store = M('store');
		//1、获得该订单的店铺store_id，如果店铺id是0，则无需返利。如果不是0，则要返利给其fid
		//返利方式已修改：获得该订单的店铺store_id（此store_id是购买者的fid,）
		//如果store_id=0 表示没有父类，无需返利
		//如果store_id不等于0 需给父类id=store_id 返利，再给gid,ggid返利
		//$id = $this->_get('id'); //订单的id
		//自己消费返利（返还积分）
		//必须有店铺，订单ownstatus=1 代表是有店铺的时候购买商品===
		//$where = array('id' => $id, 'token' =>$this->token,'moneystatus'=>0,'ownstatus'=>1);
		//只要消费就返还微币，不管是不是店主
		//此订单是在有店铺的情况下购买的
		//echo '有自己的店铺';
		$store_where = array('token'=>$token,'wecha_id'=>$financeInfo['wecha_id']);
		$ownStoreInfo = $store->where($store_where)->find();
		if(empty($ownStoreInfo)){
			$this->writeUpLevelLog('无效的用户openid: '.$financeInfo['wecha_id'].' cart_id: '.$id, Log::ERR);
			return;
		}
		$this->writeUpLevelLog("购买者订单ID".$id."订单号".$financeInfo['orderid']."购买者的店铺状态".$ownStoreInfo['status'], Log::INFO);
		if(!empty($setInfo['own'])){
			//代理用户和达到成为代理条件的用户将不进行返积分操作
			if($ownStoreInfo['status'] != 1 && $ownStoreInfo['vip'] == '0'	//非代理用户
				&& (empty($financeInfo['store_id']) || $financeInfo['price'] < $agentInfo['level1'])){ //不符合成为代理条件
				$arr['id'] 	= $ownStoreInfo['id'];
				$arr['coin']= $ownStoreInfo['coin'] + $financeInfo['price']/$setInfo['own']; //10积分返还1微币
				if($store->save($arr)){
					//返还积分插入积分记录表tp_coin_record
					$coinRecord['storeid'] = $ownStoreInfo['id'];
					$coinRecord['orderid'] = $financeInfo['orderid'];
					$coinRecord['coin'] = $financeInfo['price']/$setInfo['own'];
					$coinRecord['recordtime'] = $payTime;
					M('coin_record')->add($coinRecord);
				}else{
					$this->writeUpLevelLog('返还积分失败！sql: '.$store->getLastSql(), Log::ERR);
				}
			}else{
				$this->writeUpLevelLog('未返积分 status:'.$ownStoreInfo['status'].' vip:'.$ownStoreInfo['vip'].' cart store_id:'.$financeInfo['store_id']. ' price:'.$financeInfo['price'], Log::INFO);
			}
		}else{
			$this->writeUpLevelLog('未设置返积分基数,token:'.$token, Log::ERR);
		}
		//给推荐该用户的代理进行返利操作
		if($financeInfo['store_id'] && $agentInfo){
			//确定用户的返利基数：判断用户是否达到成未钻代标准
			$parent_store = $store->field('id,fid,gid')->where(array('id'=>$financeInfo['store_id']))->find();
			if(empty($parent_store)){
				$this->writeUpLevelLog('store_id:'.$financeInfo['store_id'].' 没找到对应的记录！', Log::INFO);
				return ;
			}
			// 代理用户存在，计算返利
			$rebate_level1 = $rebate_level2 = $rebate_level3 = 0;
			$offset_price = 0;
			//判断用户是否达到成为钻代标准
			if(empty($agentInfo['level3']) || empty($agentInfo['level2']) || empty($agentInfo['level1'])){
				$this->writeUpLevelLog('返利计算失败 agentInfo:'.json_encode($agentInfo));
				return ;
			}elseif($ownStoreInfo['vip'] == '2'){
				$offset_price = $agentInfo['level2'];
			}elseif($ownStoreInfo['vip'] == '1'){
				$offset_price = $agentInfo['level1'];
			}
			$multiple = floor(($offset_price + $financeInfo['price']) / $agentInfo['level3']);
			$normal_residue = $financeInfo['price'];
			if($multiple > 0){
				$normal_residue -= $multiple * $agentInfo['level3'] + $offset_price;
				$rebate_level1 = (($financeInfo['price'] + $offset_price - $normal_residue) * 0.8 - $offset_price) 
									* $setInfo['level1'] / 100;
				$rebate_level2 = (($financeInfo['price'] + $offset_price - $normal_residue) * 0.8 - $offset_price)
									* $setInfo['level2'] / 100;
				$rebate_level3 = (($financeInfo['price'] + $offset_price - $normal_residue) * 0.8 - $offset_price)
									* $setInfo['level3'] / 100;
			}
			$rebate_level1 += $normal_residue * $setInfo['level1'] / 100.0;
			$rebate_level2 += $normal_residue * $setInfo['level2'] / 100.0;
			$rebate_level3 += $normal_residue * $setInfo['level3'] / 100.0;
			//登记tp_rebate_record返利记录表中记录订单id与记录时间及类型消费返利(1)
			$record = array('recordtime' => $payTime, 'level' => 1, 'type' => '1');
			$record['orderid'] 	= $financeInfo['orderid'];
			$record['price'] 	= $financeInfo['price'];
			
			//考虑到父id可能在后面取消了店主资格，所以再做一次判断，如果不是店主，不给他返利。
			$data = array('status' => '1', 'id' => $financeInfo['store_id']);
			$rebate_flag = true;	//返利执行成功标志
			$save_result = true;	//数据提交结果标志
			$save_result = $store->where($data)->save(array('noincome' => array('exp', 'noincome + '.strval($rebate_level1))));
			if($save_result){
				//添加返利至tp_rebate_record返利记录表记录
				$record['storeid'] 	= $financeInfo['store_id'];
				$record['noincome'] = $rebate_level1;
				$record['level'] = 1;
				M('rebate_record')->add($record);
			}else{
				$rebate_flag = $save_result !== false; //是否更新失败
			}
			//给storeId的fid分销商返利结算	
			if($rebate_flag && $parent_store['fid']){
				$data['id'] = $parent_store['fid'];
				$save_result = $store->where($data)->save(array('noincome' => array('exp', 'noincome + '.strval($rebate_level2))));
				if($save_result){
					//添加返利至tp_rebate_record返利记录表记录
					$record['storeid']	= $parent_store['fid'];
					$record['noincome'] = $rebate_level2;
					$record['level'] = 2;
					M('rebate_record')->add($record);
				}else{
					$rebate_flag = $save_result !== false; //是否更新失败
				}
			}
			//给storeId的gid分销商返利结算	
			if($rebate_flag && $parent_store['gid']){
				$data['id'] = $parent_store['gid'];
				$save_result = $store->where($data)->save(array('noincome' => array('exp', 'noincome + '.strval($rebate_level3))));
				if($save_result){
					//添加返利至tp_rebate_record返利记录表记录
					$record['storeid'] 	=  $parent_store['gid'];
					$record['noincome'] = $rebate_level3;
					$record['level'] = 3;
					M('rebate_record')->add($record);
				}else{
					$rebate_flag = $save_result !== false; //是否更新失败
				}
			}
				
			if($rebate_flag && $cart_model->save(array('id'=>$id, 'moneystatus'=>1, 'moneytime'=>time()))){
				$this->writeUpLevelLog('执行返利成功！product_cart id:'.$id);
			}else{
				$this->writeUpLevelLog('返利失败store表, SQL：'.$store->getLastSql()."\n返利失败product_cart表, SQL:".$cart_model->getLastSql(), Log::ERR);
			}
		}
	}
	/**
	 * 支付成功之后调用此方法。自动审核成为对应等级的微店主
	 * @param array $price 			消费订单价格
	 * @param int 	$proxy_store_id	分享链接用户store_id
	 */
	private function autoStore($openid, $token, $cart_id, $price, $proxy_store_id = 0){
		$this->wecha_id=$openid;
		$this->token=$token;
		//查询在关注表中是否有此用户的信息	
		//$peopleInfo=M('wxuser_people')->where($where)->field('nickname,headimgurl')->find();
		//查出该openid的微店主的信息
		$model = M('Store');  //微店铺表
		$where['token']=$this->token;
		$where['wecha_id']=$this->wecha_id;
		$storeInfo=$model->where($where)->find();
		/** 调整代码：by cqz add 20150417
		 * 1. 代理等级自动升级（未成为代理用户和vip < 3的用户）；
		 * 2. 首次购买根据分享的storeId设置上级ID(fid)；
		 * 首次购买时自动导入数据到userinfo表(提交订单时已经添加，此处不操作)*/
		//未成为代理和未到达顶级(vip3)的代理
		if($storeInfo['status'] != '1' || $storeInfo['vip'] != '3' ){
// 			$product_cart_model = M('product_cart');
// 			$map['token']=$this->token;
// 			$map['wecha_id']=$this->wecha_id;
// 			$map['ostatus']=array('in','2,3,4,10');//订单（待发货，待收货，交易完成）消费总额
// 			//$pcInfo=$product_cart_model->where($map)->field('truename,tel')->select();//要找address这个表才行
// 			$pcSum = $product_cart_model->where($map)->field('price')->sum('binary price');
			$setInfo=M('distribution_agent')->where(array('token'=>$this->token))->find();
			$data = array('id' => $storeInfo['id']);
			//代理等级自动升级
			if(empty($setInfo)){
				$this->writeUpLevelLog('商家未设置代理资格，无法自动审核成为店主，token:'.$token, Log::INFO);//exit;
			}else{
				if($storeInfo['status'] != '1')	//未成为代理时给applytime赋值
					$data['applytime'] = time();
				if($price >= $setInfo['level3']
					|| ($storeInfo['vip'] == '2' && $price >= $setInfo['level3'] - $setInfo['level2'])
					|| ($storeInfo['vip'] == '1' && $price >= $setInfo['level3'] - $setInfo['level1'])){
					//钻眼代理 vip=3
					$data['vip'] = 3;
					$data['status'] = 1;
					$data['applyhandled'] = time();//处理申请的时间
					// 标志订单为升级为钻代订单
					M('product_cart')->where(array('id'=>$cart_id))->save(array('zstatus'=>'1'));
					$this->writeUpLevelLog('store_id: '.$storeInfo['id'].' upgrade to level3, cart_id:'.$cart_id, Log::INFO);
				}elseif(($storeInfo['vip'] == '0' && $price >= $setInfo['level2'])
					|| ($storeInfo['vip'] == '1' && $price >= $setInfo['level2'] - $setInfo['level1'])){
					//金眼代理 vip=2
					$data['vip'] = 2;
					$data['status'] = 1;
					$data['applyhandled'] = time();//处理申请的时间
					$this->writeUpLevelLog('store_id: '.$storeInfo['id'].' upgrade to level2, cart_id:'.$cart_id);
				}elseif($storeInfo['vip'] == '0' && $price >=$setInfo['level1']){
					//银眼代理 vip=1
					$data['vip'] = 1;
					$data['status'] = 1;
					$data['applyhandled'] = time();//处理申请的时间
					$this->writeUpLevelLog('store_id: '.$storeInfo['id'].' upgrade to level1, cart_id:'.$cart_id);
				}else{
					//不符合资格，交给后台审核
					//$data['status'] = 3;
					$this->writeUpLevelLog('store_id: '.$storeInfo['id'].' not match upgrade');
				}
			}
			if($storeInfo['status'] != '1'){
				//从代理分享链接购买，fid为赋值时，设置fid,gid,ggid
				if(!$storeInfo['fid'] && $proxy_store_id){
					//获取上级store信息
					$parent_store = $model->field('id,fid,gid,ggid')->find($proxy_store_id);
					if($parent_store){
						$data['fid'] = $parent_store['id'];
						$data['gid'] = $parent_store['fid'];
						$data['ggid'] = $parent_store['gid'];
					}
					//个人地址信息
					$pcInfo = M('address')->where(array('token'=>$this->token,'openid'=>$this->wecha_id))->field('name,tel')->find();
					$data['username'] =  $pcInfo['name'];
					$data['telphone'] =  $pcInfo['tel'];
			
					Log::write('update store fid and vip: '.json_encode($data).' original level: '.$storeInfo['vip']);
					$model->save($data);
					//成为下级之后增加推送消息
					//增加微信回复(A推荐B)
					$storeNewInfo=$model->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->field('fid,vip,username')->find();
					$vipMap = M('Dictionary')->where("type='store_vip'")->getField("keyvalue,keyname");	

					$storeNewInfo['vip'] =$vipMap[$storeNewInfo['vip']];

					$upInfoFid=$model->where(array('id'=>$storeNewInfo['fid']))->field('wecha_id,username')->find();
					if($storeNewInfo['gid']){
						$upInfoGid=$model->where(array('id'=>$storeNewInfo['gid']))->field('wecha_id,username')->find();
					}
					if($storeNewInfo['ggid']){
						$upInfoGgid=$model->where(array('id'=>$storeNewInfo['ggid']))->field('wecha_id,username')->find();
					}
					//$content2 = "轻轻系统微赢利模式，玩出财富来！您已成功推荐".$storeNewInfo['username']."成为您的".$storeNewInfo['vip'];
					//$content1 = "轻轻系统微赢利模式，玩出财富来！恭喜您已成为".$upInfoFid['username']."的".$storeNewInfo['vip'];
					$content1 = "通过".$upInfoFid['username']."的分享，您已成功消费".$price."元";
					$content2 = "尊敬的".$upInfoFid['username']."，感谢您的分享，".$storeNewInfo['username']."在Dora商城消费".$price."元";
					$content3 = "尊敬的".$upInfoGid['username']."，感谢您的直1消费圈，".$upInfoFid['username']."的分享，".$storeNewInfo['username']."在Dora商城消费".$price."元";
					$content4 = "尊敬的".$upInfoGgid['username']."，感谢您的间1消费圈，".$upInfoFid['username']."的分享，".$storeNewInfo['username']."在Dora商城消费".$price."元";
					
					Log::write("购买者信息".json_encode($storeNewInfo)."推荐人信息".json_encode($upInfoFid), Log::DEBUG, 3, LOG_PATH.'tuijian.log');
					$access_token = getAccessToken($this->token);
					if($access_token['status']){
						$data1='{"touser":"'.$this->wecha_id.'","msgtype":"text","text":{"content":"'.$content1.'"}}';
						
						$data2='{"touser":"'.$upInfoFid['wecha_id'].'","msgtype":"text","text":{"content":"'.$content2.'"}}';
						$url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token['info'];
						
						$data3='{"touser":"'.$upInfoGid['wecha_id'].'","msgtype":"text","text":{"content":"'.$content3.'"}}';
						$url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token['info'];
						
						$data4='{"touser":"'.$upInfoGgid['wecha_id'].'","msgtype":"text","text":{"content":"'.$content4.'"}}';
						$url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token['info'];
						
						$this->api_notice_increment($url,$data1);
						$this->api_notice_increment($url,$data2);
						if($storeNewInfo['gid']){
							$this->api_notice_increment($url,$data3);
						}
						if($storeNewInfo['ggid']){
							$this->api_notice_increment($url,$data4);
						}
						
					}else{
						Log::write("Wap/ProductAction.orderCart - getAccessToken:".$access_token["info"]);
					}
					//==============
				}elseif($storeInfo['fid']){//有上级代理时更新状态和等级
					Log::write('update store status and vip: '.json_encode($data).' original level: '.$storeInfo['vip']);
					$model->save($data);
				}
			}elseif($data['vip']){
				Log::write('update store vip: '.json_encode($data).' original level: '.$storeInfo['vip']);
				$model->save($data);
			}
		}
		
		
		/**  原代码   for xulin before 20150417
		if($storeInfo['status'] == 1 ){	
			//('你已经是微店主') //('微店主申请审核中')
	
		}else{
			//个人地址信息
			$pcInfo=M('address')->where(array('token'=>$this->token,'openid'=>$this->wecha_id))->field('name,tel')->find();
			//统计个人消费金额
			$product_cart_model=M('product_cart');
			$map['token']=$this->token;
			$map['wecha_id']=$this->wecha_id;
			$map['ostatus']=array('in','2,3,4');//订单（待发货，待收货，交易完成）消费总额
			//$pcInfo=$product_cart_model->where($map)->field('truename,tel')->select();//要找address这个表才行
			$pcSum=$product_cart_model->where($map)->field('price')->sum('binary price');
			//支付成功代表肯定是有一条订单数据的，所以$pcSum不是空的
			if(empty($pcSum)){
				//exit('订单有错误，价格不能是空');
			}else{
				//echo '这里执行自动审核操作。';
				//读取代理资格配置表
				$setInfo=M('distribution_agent')->where(array('token'=>$this->token))->find();
				if(empty($setInfo)){$this->error("商家未设置代理资格，无法自动审核成为店主，请稍后再试");exit;}
				$store = M('store');
				$data['id'] =  $storeInfo['id'];//店铺ID
				$data['username'] =  $pcInfo['name'];
				$data['telphone'] =  $pcInfo['tel'];				
				$data['applytime'] = time();//申请时间
				$data['status'] = 3;   //状态由未申请（0）变成审核中（3）  0未申请 3审核中 1申请通过 2申请拒绝
				if($pcSum>=$setInfo['level3']){
					//钻眼代理 vip=3
					$data['vip'] = 3;    
					$data['status'] = 1;
					$data['applyhandled'] = time();//处理申请的时间
					
				}elseif($pcSum>=$setInfo['level2']){
					//金眼代理 vip=2
					$data['vip'] = 2;
					$data['status'] = 1;
					$data['applyhandled'] = time();//处理申请的时间
					
				}elseif($pcSum>=$setInfo['level1']){
					//银眼代理 vip=1
					$data['vip'] = 1;    
					$data['status'] = 1;
					$data['applyhandled'] = time();//处理申请的时间
					
				}else{
					//不符合资格，交给后台审核
					$data['status'] = 3;	
				}	
				$result=$store->save($data);
				if($result){
					//如果userinfo表中还没填写个人信息，把申请的个人信息插入表中
					$userinfo_model=M('Userinfo');
					$thisUser=$userinfo_model->where(array('token'=>$this->token,'wecha_id'=>$this->wecha_id))->find();
					if (!$thisUser){
						$userRow['token']=$this->token;
						$userRow['wecha_id']=$this->wecha_id;
						$userRow['truename'] =  $pcInfo['name'];
						$userRow['telphone'] =  $pcInfo['tel'];
						$userinfo_model->add($userRow);
					}
				}
			}	
		}	*/
	}
	
	//支付成功之后刷新购买者所有上级的广告圈收入
	private function refreshAdIncome($orderDetail){
		$storeInfo = M('store')->where(array('wecha_id'=>$orderDetail['wecha_id']))->find();
		if(!empty($storeInfo)){
			$parent['fid'] = $storeInfo['fid'];
			$parents = array();
			$i = 0;
			while(!empty($parent['fid'])){
				if($parents[$parent['fid']]){
					echo 'id:'.$parent['id'].'  fid:'.$parent['fid'];break;
				}
				$parent = M('store')->where(array('id'=>$parent['fid'],'token'=>$storeInfo['token']))->find();
				
				//（newAdCountSale）新增广告圈销售额累加
				$parent['newAdCountSale'] = $parent['newAdCountSale'] + $orderDetail['price'];
				//(adCountSale)广告圈销售额累加
				$parent['adCountSale'] = $parent['adCountSale'] + $orderDetail['price'];			
				if($i == 0){
					//（newDirectlySale）新增直营销售额累加
					$parent['newDirectlySale'] = $parent['newDirectlySale'] + $orderDetail['price'];
					//（directlySale）直营销售额累加
					$parent['directlySale'] = $parent['directlySale'] + $orderDetail['price'];
					
					$data['directlySale'] = $orderDetail['price'];
					$data['indirectlySale'] = 0;
				}else {
					$data['directlySale'] = 0;
					$data['indirectlySale'] = $orderDetail['price'];
				}
				if($i == 1){
					//（j1CountSale）间1销售额累加
					$parent['j1CountSale'] = $parent['j1CountSale'] + $orderDetail['price'];
				}
				if($i == 2){
					//（j2CountSale）间2销售额累加
					$parent['j2CountSale'] = $parent['j2CountSale'] + $orderDetail['price'];
				}
				M('store')->save($parent);
				$parents[$parent['id']] = $parent;
				//把销售额的细节存起来，方便以后当日记查看。
				$data['token'] = $storeInfo['token'];
				$data['storeid'] = $parent['id'];
				$data['wecha_id'] = $orderDetail['wecha_id'];
				$data['orderid'] = $orderDetail['orderid'];
				$data['status'] = 0; //未重置清空
				$data['createtime'] = time();
				M('sale_details')->add($data);
				$i++;
			}
		}
	}
	//混搭模式下的订单，需要扣除微币，微币写入明细表
	private function mashup($orderDetail){
		//成功支付之后，扣掉抵扣的微币
		$StoreInfo=M('store')->where(array('wecha_id'=>$orderDetail['wecha_id']))->setDec('income',$orderDetail['weibi']);
		$storeUser = M('store')->where(array('wecha_id'=>$orderDetail['wecha_id']))->find();
		//写入微币明细表
		$record['storeid'] 	= $storeUser['id'];
		$record['orderid'] 	= $orderDetail['orderid'];
		$record['noincome'] = -$orderDetail['weibi'];  //消费是正，代表是退货的，是负数，代表是消费
		$record['status'] 	= 2;
		$record['recordtime'] 	= time();
		$record['type'] 	= 4;
		M('rebate_record')->add($record);
	}
	public function warnInfo(){
		echo 'success';
		exit();
	}
	
	public function api_notice_increment($url, $data){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$tmpInfo = curl_exec($ch);
		if (curl_errno($ch)) {
			return false;
		}else{

			return true;
		}
	}
	function trimall($str)//删除空格
	{
		$qian=array(" ","　","\t","\n","\r");$hou=array("","","","","");
		return str_replace($qian,$hou,$str);    
	}
	
	private function writeUpLevelLog($msg, $level = 'DEBUG'){
		if(APP_DEBUG || $level != Log::DEBUG){
			Log::write($msg, $level, Log::FILE, LOG_PATH.'order_operate.log');
		}
	}
}
?>