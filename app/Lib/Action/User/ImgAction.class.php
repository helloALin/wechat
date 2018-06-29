<?php
/**
 *文本回复
**/
class ImgAction extends UserAction{
	private $errorInfo;
	
	public function index(){
		$db=D('Img');
		$fid=$this->_get('fid','intval');
		if($fid!=''){
		$where['classid']=$fid;
		}
		//$where['uid']=session('uid');
		$where['token']=session('token');
		$count=$db->where($where)->count();
		$page=new Page($count,25);
		$info=$db->where($where)->order('createtime DESC')->limit($page->firstRow.','.$page->listRows)->select();
		foreach($info as $k=>&$v){
			$count=M('comment')->where(array('token'=>session('token'),'imgid'=>$v['id']))->count();
			$v['uname']=$count;
		}
		$class=M('Classify')->where(array('token'=>session('token'),'fid'=>0))->order('sorts desc,id asc')->select();
		$classify_list=$this->classify_list($class,$fid);
		$this->assign('class_list',$classify_list);
		$this->assign('page',$page->show());
		$this->assign('info',$info);
		$this->display();
	}
	public function add(){
		$isclass=M('Classify')->where(array('token'=>session('token')))->select();
		if($isclass==false){$this->error('请先添加3G网站分类',U('Classify/index',array('token'=>session('token'))));}
		$class=M('Classify')->where(array('token'=>session('token'),'fid'=>0))->order('sorts desc,id asc')->select();
		$classify_list=$this->classify_list($class);
		$this->assign('class_list',$classify_list);
		$this->assign('info',$info);
		$this->display();
	}
	public function edit(){
		$class=M('Classify')->where(array('token'=>session('token'),'fid'=>0))->order('sorts desc,id asc')->select();
		$where['id']=$this->_get('id','intval');
		//$where['uid']=session('uid');
		$res=D('Img')->where($where)->find();
		$classify_list=$this->classify_list($class,$res['classid']);
		$this->assign('class_list',$classify_list);
		$this->assign('info',$res);
		$this->display();
	}
	public function del(){
		$where['id']=$this->_get('id','intval');
		$where['uid']=session('uid');
		if(D(MODULE_NAME)->where($where)->delete()){
			M('Keyword')->where(array('pid'=>$this->_get('id','intval'),'token'=>session('token'),'module'=>'Img'))->delete();
			$this->success('操作成功',U(MODULE_NAME.'/index'));
		}else{
			$this->error('操作失败',U(MODULE_NAME.'/index'));
		}
	}
	public function insert(){
		//$pat = '/<(\/?)(script|i?frame|style|html|body|title|font|strong|span|div|marquee|link|meta|\?|\%)([^>]*?)>/isU';
		//$_POST['info'] = preg_replace($pat,'',$_POST['info']);
		//$_POST['info']=strip_tags($this->_post('info'),'<a> <p> <br>');  
		//dump($_POST['info']);
		$this->all_insert();
	}
	public function upsave(){
		$this->all_save();
	}
	public function classify_list($arr,$classid=0){
		$select='<select name="classid">';
		$select.='<option  value="">请选择</option>';
		foreach ($arr as $vo){
			if($classid==$vo['id']){
				$select.='<option value="'.$vo['id'].','.$vo['name'].'" selected="selected">'.$vo['name'].'</option>';
			}else{
				$select.='<option value="'.$vo['id'].','.$vo['name'].'">'.$vo['name'].'</option>';
			}
			$f=M('Classify')->where(array('fid'=>$vo['id']))->order('sorts desc')->select();
			if($f){
				foreach ($f as $vo1){
					if($classid==$vo1['id']){
						$select.='<option value="'.$vo1['id'].','.$vo1['name'].'" selected="selected">|-'.$vo1['name'].'</option>';
					}else{
						$select.='<option value="'.$vo1['id'].','.$vo1['name'].'">|-'.$vo1['name'].'</option>';
					}
				}
			}
		}
		$select.='</select>';
		return $select;
	}
	
	/**
	 * 图文群发
	 */
	public function massSend(){
		$this->display();
	}
	
	public function massSent(){
		$model=M('MassImgLog');
// 		$fid=$this->_get('fid','intval');
// 		if($fid!=""){
// 			$where['classid']=$fid;
// 		}
		//$where['uid']=session('uid');
		$where['token']=session('token');
		$count=$model->where($where)->count('1');
		$page=new Page($count,15);
		$log_list = $model->field('img_ids,status,descript,create_time')->where($where)
						->order('create_time DESC')->limit($page->firstRow.','.$page->listRows)->select();
		$statusMap = M('Dictionary')->where('type=\'mass_img_log_status\'')->getField('keyvalue,keyname');
		$imgid_array = array();
		foreach($log_list as &$log){
			$log['img_ids'] = explode(',', $log['img_ids']);
			$imgid_array = array_merge($imgid_array, $log['img_ids']);
			$log['create_time'] = date('Y-m-d H:i:s', $log['create_time']);
		}
		$imgid_array = array_unique($imgid_array);
		$where['id'] = array('in', $imgid_array);
		$imgs = M('Img')->where($where)->getField('id,pic,title');
		$this->assign('log_list', $log_list);
		$this->assign('imgs', $imgs);
		$this->assign('page', $page->show());
		$this->assign('statusMap', $statusMap);
		$this->display();
	}
	
	public function imgList(){
		$db=D('Img');
		$fid=$this->_get('fid','intval');
		if($fid!=''){
			$where['classid']=$fid;
		}
		$where['token']=session('token');
		$count=$db->where($where)->count('1');
		$page=new Page($count,25);
		$img_list=$db->where($where)->order('createtime DESC')->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('img_list',$img_list);
		$this->display();
	}
	
	public function massSendAct(){
		$ids = $this->_post('ids', 'trim');
		if(empty($ids))
			$this->error('请选择群发的图文消息！');
		$id_array = array_unique(explode(',', $ids));
		//获取accessToken
		$this->token = session('token');
		$access_token = getAccessToken($this->token);
		if($access_token['status']){
			$access_token = $access_token['info'];
		}else{
			$this->error($access_token['info']);
		}
		$where = array('token'=>$this->token, 'id'=>array('in', $id_array));
		$imgs = M('Img')->where($where)->select();	
		if(empty($imgs))
			$this->error('请选择群发的图文消息！', U('Img/massSend'));
		$massImgs = array();//根据选择图文的顺序进行重新组合
		foreach ($id_array as $id){
			foreach ($imgs as $img){
				if($img['id'] == $id)
					$massImgs[] = $img;
			}
		}
		ignore_user_abort(true);	//忽略用户主动断开请求
		set_time_limit(300);	 //关闭执行时间限制5分钟
		$mpnews_meida_id = $this->getArticlesMediaId($massImgs, $access_token);
		$mass_send_json = '';	$mass_send_url = '';
		if($mpnews_meida_id){	//生成群发json字符串
			// 使用根据分组群发经常会出现发送失败，下面提供根据openid进行群发进从数据库获取10000个用户进行群发(使用时根据后面的注释切换)。
			//获取10000个用户的openid，作为群发对象
			/**使用根据OPENID群发开始
			 * 从Store表获取openid
			* $openIds = M('Store')->where(array('token'=>$this->token))->getField('wecha_id', 10000);  //
			* 从微信平台获取openid
			$user_list_str = curlGet('https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$access_token);
			$user_list_json = json_decode($user_list_str, true);
			$openIds = $user_list_json['data']['openid'];
			if(empty($openIds)){
				$this->errorInfo = '没有找到匹配的用户列表';
			}else{
				$mass_send_json = json_encode(array(
					'touser'=>$openIds,	//使用根据OPENID群发
					'mpnews'=>array('media_id'=>$mpnews_meida_id),
					'msgtype'=>'mpnews'
				));
				$mass_send_url = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token='.$access_token; 
			}*/
			//使用根据OPENID群发结束
			
			/*使用根据分组群发开始*/
			$mass_send_json = json_encode(array(
				'filter' =>array('is_to_all'=>true),
				'mpnews'=>array('media_id'=>$mpnews_meida_id),
				'msgtype'=>'mpnews'
			));
			$mass_send_url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token='.$access_token;
			//使用根据分组群发结束
			$this->writeLog('群发发送json'.$mass_send_json);
		}
		if($mass_send_json){
			$mass_send_result = curlPost($mass_send_url, $mass_send_json);
			$mass_send_result_json = json_decode($mass_send_result, true);
			if($mass_send_result_json){
				if($mass_send_result_json['errcode'] == 0){
					$this->saveMassImgLog('已发送到微信', 2, $ids, $mass_send_result_json);
					$this->success('群发成功', U('Img/massSend'));exit;
				}elseif($mass_send_result_json && 
						($mass_send_result_json['errcode'] == 48001 || $mass_send_result_json['errcode'] == 50001)) {
					$this->errorInfo = '您没有获得消息群发的权限，请公众号是否通过认证后再进行操作';
					Log::write('群发请求失败，无权限token: '.$this->token.' fail: '.$result);
					$this->saveMassImgLog($this->errorInfo, 1, $ids, $mass_send_result_json);
				}else{
					$this->errorInfo = '群发请求失败！错误码：'.$mass_send_result_json['errcode'];
					$this->saveMassImgLog($this->errorInfo, 1, $ids, $mass_send_result_json);
				}
				$this->writeLog('群发完成: '.$mass_send_result);
			}else{
				$this->errorInfo = '群发请求失败，请联系开发人员或查看系统日志';
				$this->saveMassImgLog($this->errorInfo, 1, $ids);
				Log::write('群发失败: '.$mass_send_result);
			}
		}
		if(empty($this->errorInfo)) {//生成推送多图内容失败
			$this->errorInfo = '群发失败';
		}
		$this->error($this->errorInfo, U('Img/massSend'));
	}
	
	/**
	 * 生成多图文mediaId
	 * @param array $massImgs
	 * @return string mediaId
	 */
	private function getArticlesMediaId($massImgs, $access_token){
		$nowtime = time() + 300;//偏差5分钟
		$articles = array();
		$cacheMediaId = array();//图片mediaId缓存，避免相同的图片多次上传
		foreach($massImgs as $img){
			//上传图片，并更新media_id的值
			if(empty($cacheMediaId[$img['pic']])){
				$img['media_id'] = $this->uploadImg($img['pic'], $access_token);
				if($img['media_id'] == false){
					$this->errorInfo = '图文："'.$img['title'].'", '.$this->errorInfo;
					break;
				}
				$cacheMediaId[$img['pic']] = $img['media_id'];
			}else{
				$img['media_id'] = $cacheMediaId[$img['pic']];
			}
			//添加存在media_id的情报的上传情报列表
			if(!empty($img['media_id'])){
				$articles[] = array(
					'thumb_media_id' 	=>	$img['media_id'],
					//'author' 			=>	$img['media_id'],
					'title' 			=>	$img['title'],
					'content_source_url'=>	$img['url'],
					'content' 			=>	htmlspecialchars_decode($img['info']),//提交内容默认进行转义
					'digest' 			=>	$img['text'],
					'show_cover_pic' 	=>	$img['showpic']
				);
			}
		}
		//判断情报添加过程图片上传是否出错
		if(!empty($this->errorInfo)){
			return false;
		}
		//含有匹配条件情报时，上传情报图文到微信
		if(count($articles) > 0){
			$article_str = json_encode_forCH(array('articles'=>$articles));
			$this->writeLog('生成的情报内容：'.$article_str);
			//上传多图文到微信
			$upload_news_url = 'https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token='.$access_token;
			$upload_news_result = curlPost($upload_news_url, $article_str);
			$upload_news_json = json_decode($upload_news_result);
			if($upload_news_json && $upload_news_json->media_id){
				$this->writeLog('群发多图文上传成功：'.$upload_news_result);
				return $upload_news_json->media_id;
			} elseif($upload_news_json && ($upload_news_json->errcode == 48001 || $upload_news_json->errcode == 50001)) {
				$this->errorInfo = '您没有获得消息群发的权限，请公众号是否通过认证后再进行操作';
				Log::write('群发多图文上传失败，无权限token: '.$this->token.' fail: '.$result);
				return false;
			}else{
				$this->errorInfo = '上传情报请求失败，请重试或联系开发人员或查看系统日志';
				Log::write('群发多图文上传失败：'.$upload_news_result);
				return false;
			}
		} else {
			$this->writeLog('没有匹配情报！');
			return false;
		}
	}
	
	/**
	 * 上传图片到微信服务器
	 * @param string $media_url  上传图片url
	 * @return boolean
	 */
	private function uploadImg($media_url, $access_token){
		$this->writeLog('upload image oparate file_url:'.$media_url);
		$url = preg_replace('/^http:\/\/[^\/]+/i', '', $media_url);
		if(strpos($url, '/') !== 0) $url = '/'.$url; 
		$filepath = $_SERVER['DOCUMENT_ROOT'] . $url;
		if($url == '/' || !file_exists($filepath)){
			$this->errorInfo = '封面图片不存在';echo 'file not exists';
			return false;
		}
		$filedata = array('media' => '@' . $filepath);
		$uploadUrl = 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=' 
				. $access_token . '&type=image';
		$result = curlPost($uploadUrl, $filedata);
		$json = json_decode($result);
		$this->writeLog($result);
		if($json && empty($json->errcode)){
			return $json->media_id;
		}elseif($json && ($json->errcode == 48001 || $json->errcode == 50001)) {
			$this->errorInfo = '您没有获得消息群发的权限，请公众号是否通过认证后再进行操作';
			Log::write('上传情报图片失败，无权限token: '.$this->token.' 文件名: '.$filepath.' fail: '.$result);
			return false;
		}else{
			$this->errorInfo = '封面图片上传请求失败，请重试或联系管理员';
			Log::write('上传情报图片失败，文件名: '.$filepath.' fail: '.$result);
			return false;
		}
	}
	
	/**
	 * 保存/更新推送日志
	 * @param string $desc
	 * @param string $msg
	 * @param string $msgId
	 * @param string $returnMsg
	 * @return Ambigous <mixed, boolean, unknown>
	 */
	private function saveMassImgLog($desc, $status, $ids, $returnJSON = ''){
		$log_data = array(
				'descript'	=>	$desc,
				'status'	=>	$status,
				'img_ids'	=>	$ids,
				'token'		=>	$this->token,
				'create_time'	=>time()
			);
		if($returnJSON){
			$log_data['msgid']	= $returnJSON['msg_id'];
			$log_data['errcode']= $returnJSON['errcode'];
			$log_data['errmsg']	= $returnJSON['errmsg'];
		}
		return M('MassImgLog')->add($log_data);
	}
	
	private function writeLog($msg){
		if(APP_DEBUG){
			Log::write($msg, Log::DEBUG, Log::FILE, LOG_PATH.'masssend.log');
		}
	}
}
?>