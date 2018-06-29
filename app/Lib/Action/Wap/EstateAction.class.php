<?php
class EstateAction extends BaseAction
{
    public $token;
    public $wecha_id;
    public function _initialize()
    {
        parent::_initialize();
        $this->token    = $this->_get('token');
        $this->wecha_id = $this->_get('wecha_id');
        $this->assign('token', $this->token);
        $this->assign('wecha_id', $this->wecha_id);
        $get_ids = M('Estate')->where(array(
            'token' => $this->token
        ))->field('res_id,classify_id')->find();
        $this->assign('rid', $get_ids['res_id']);
    }
    public function index()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (!strpos($agent, "icroMessenger")) {
        }
        $data        = M("Estate");
        $this->token = $this->_get('token');
        $where       = array(
            'token' => $this->token
        );
        $es_data     = $data->where($where)->find();
        $this->assign('es_data', $es_data);
        $this->display();
    }
    public function news()
    {
        $data        = M("Estate");
        $this->token = $this->_get('token');
        $where       = array(
            'token' => $this->token
        );
        $cid         = $data->where($where)->getField('classify_id');
        if ($cid != null) {
            $t_classify = M('Classify');
            $where      = array(
                'token' => $this->token,
                'id' => $cid
            );
            $classify   = $t_classify->where($where)->find();
        }
        $t_img  = M('Img');
        $where  = array(
            'classid' => $classify['id'],
            'token' => $this->_get('token')
        );
        $imgtxt = $t_img->where($where)->field('id as mid,title,pic,createtime')->select();
        $this->assign('imgtxt', $imgtxt);
        $this->assign('classify', $classify);
        $this->display();
    }
    public function newlist()
    {
        $token  = $this->_get('token');
        $mid    = (int) $this->_get('mid');
        $t_img  = M('Img');
        $where  = array(
            'id' => $mid,
            'token' => $token
        );
        $imgtxt = $t_img->where($where)->find();
        $this->assign('imgtxt', $imgtxt);
        $this->display();
    }
    public function album()
    {
        $Photo      = M("Photo");
        $photo_list = M('Photo_list');
        $t_album    = M('Estate_album');
        $album      = $t_album->where(array(
            'token' => $this->_get('token')
        ))->field('id,poid')->select();
        foreach ($album as $k => $v) {
            $list_photo  = $Photo->where(array(
                'token' => $this->_get('token'),
                'id' => $v['poid']
            ))->field('id')->find();
            $photolist[] = $photo_list->where(array(
                'token' => $this->_get('token'),
                'pid' => $list_photo['id']
            ))->select();
        }
        $this->assign('photolist', $photolist);
        $this->assign('album', $list_photo);
        $this->display();
    }
    public function show_album()
    {
        $t_housetype = M('Estate_housetype');
        $id          = (int) $this->_get('id');
        $where       = array(
            'token' => $this->_get('token'),
            'id' => $id
        );
        $housetype   = $t_housetype->where($where)->order('sort desc')->find();
        $data        = M("Estate");
        $this->token = $this->_get('token');
        $where       = array(
            'token' => $this->token
        );
        $es_data     = $data->where($where)->field('id,title')->find();
        if (!empty($es_data)) {
            $housetype = array_merge($housetype, $es_data);
        }
        $this->assign('housetype', $housetype);
        $this->display();
    }
    public function impress()
    {
        $t_impress = M('Estate_impress');
        $impress   = $t_impress->where('is_show=1')->order('sort desc')->select();
        $this->assign('impress', $impress);
        $t_expert = M('Estate_expert');
        $expert   = $t_expert->order('sort desc')->select();
        $this->assign('expert', $expert);
        $this->display();
    }
    public function impress_add()
    {
        $t_impress = M('Estate_impress');
        $t_imp_add = M("Estate_impress_add");
        $imp_id    = (int) $this->_post('imp_id');
        $token     = $this->_post('token');
        $wecha_id  = $this->_post('wecha_id');
        $where     = array(
            'imp_id' => $imp_id,
            'wecha_id'
        );
        $check     = $t_imp_add->where($where)->find();
        $data      = array(
            'imp_id' => $imp_id,
            'msg' => $wecha_id
        );
        echo json_encode($data);
        exit;
        if ($check != null) {
            $data = array(
                'success' => 1,
                'msg' => "谢谢您的赞。"
            );
            echo json_encode($data);
            exit;
        }
        if ($id = $t_imp_add->add($_POST)) {
            $t_impress->where(array(
                'id' => $imp_id
            ))->setInc('comment');
            $data = array(
                'success' => 1,
                'msg' => "谢谢您的赞。"
            );
            echo json_encode($data);
            exit;
        } else {
            $data = array(
                'success' => 0,
                'msg' => "点赞失败，请再来一次吧。"
            );
            echo json_encode($data);
            exit;
        }
    }
    public function aboutus()
    {
        $company = M('Company');
        $token   = $this->_get('token');
        $about   = $company->where(array(
            'token' => $token
        ))->find();
        $this->assign('about', $about);
        $this->display();
    }
    //
     public function EstateReserveBook(){
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if(!strpos($agent,"icroMessenger")) {
            //echo '此功能只能在微信浏览器中使用';exit;
        }

        $addtype = $this->_get('addtype');
        $token   = $this->_get('token');
        $wecha_id = $this->_get('wecha_id');
        $this->assign('addtype',$addtype);
        $where = array('token'=>$token,'addtype'=>$addtype);
        

        $t_res = M('Reservation');
        $reser =  $t_res->where($where)->find();
        //如果$reser是空，则没有添加预约。
        $t_housetype = M('Estate_housetype');
        $where       = array('token'=>$this->_get('token'));
        $count      = $t_housetype->where($where)->count();
        $Page       = new Page($count,5);
        $show       = $Page->show();
        $housetype  = $t_housetype->where($where)->order('sort desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('page',$show);
        $this->assign('housetype',$housetype);
        $this->assign('reser',$reser);
		//$id = '132';
        $where4 = array('token'=>$token,'wecha_id'=>$wecha_id,'type'=>$addtype);
        $count = M('Reservebook')->where($where4)->count();
        $this->assign('count',$count);
		$vip=M('userinfo')->where(array('token'=>$token,'wecha_id'=>$wecha_id))->select();//vip查找数据
		$this->assign('tel',$vip[0]['tel']);
		$this->assign('truename',$vip[0]['truename']);
		
		if(IS_POST){
	
        $da['token']      = strval($this->_get('token'));
        $da['wecha_id']   = strval($this->_post('wecha_id'));
        $da['rid']        = (int)$this->_post('rid');
        $da['truename']   = strval($this->_post("truename"));
        $da['dateline']   = strval($this->_post("dateline"));
        $da['timepart']   = strval($this->_post("timepart"));
        $da['info']       = strval($this->_post("info"));
        $da['tel']        = strval($this->_post("tel"));
        $da['type']       = strval($this->_post('type'));
        $da['housetype']  = $this->_post('housetype');
        $da['booktime']   = time();
        $das['id']        = (int)$this->_post('id');
         $book   =   M('Reservebook');
         $token = strval($this->_get('token'));
         $wecha_id = strval($this->_get('wecha_id'));
		 
         $url ='http://'.$_SERVER['HTTP_HOST'];
         $url .= U('Estate/ReserveBooking',array('token'=>$token,'wecha_id'=>$wecha_id,'type'=>$addtype));
         $t_res = M("Reservation");
         $check = $t_res->where(array('id'=>$da['rid'],'addtype'=>'estate','token'=>$da['token']))->find();
        $ok = $book->data($da)->add();
        if(!empty($ok)){
            $t_res->where(array('id'=>$da['rid'],'addtype'=>'estate','token'=>$da['token']))->setDec('typename');
            $arr=array('errno'=>0,'msg'=>'恭喜预约成功','token'=>$token,'wecha_id'=>$wecha_id,'url'=>$url);
            echo json_encode($arr);
            exit;
        }else{
             $arr=array('errno'=>1,'msg'=>'预约失败，请重新预约','token'=>$token,'wecha_id'=>$wecha_id,'url'=>$url);
            echo json_encode($arr);
            exit;
        }
		
		//p($da);die;
      }else{
        $this->display();}
    }
  
    /*public function add(){
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if(!strpos($agent,"icroMessenger")) {
           // exit('此功能只能在微信浏览器中使用');
        }
    }*/
	public function edit(){      
         if(IS_POST){
			$wecha_id   = $this->_get('wecha_id');
			$this->assign('wecha_id',$wecha_id);
            $data=M('Reservation');
            $where=array('id'=>(int)$this->_post('id'),'token'=>session('token'));
            $check=$data->where($where)->find();
			var_dump($check);die;
            if(empty($check)){
            $this->error('非法操作');
            }
            if($data->create()){
                $_POST['addtype'] = 'estate';
                $_POST['token'] = session('token');
                if($data->where($where)->save($_POST)){
                    $data1['pid']=(int)$this->_post('id');
                    $data1['module']='Reservebook';
                    $data1['token']=session('token');

                    $da['keyword']=trim($_POST['keyword']);
                    M('Keyword')->where($data1)->save($da);
					$data = M("Reservation");
					$where2 = array('token'=>$token,'addtype'=>$type);
        //var_dump($headpic);
					$this->assign('reser',$reser);
                    $this->success('操作成功',U('Estate/ReserveBooking',array('token'=>session('token'))));
                }else{
                    $this->error('操作失败');
                }
            }else{
                $this->error($data->getError());
            }
        }else{
            $id=$this->_get('id');
			$wecha_id=$this->_get('wecha_id');
			$type=$this->_get('addtype');
            $where=array('wecha_id'=>$wecha_id,'type'=>$type,'id'=>$id,'token'=>session('token'));
            $data=M('Reservebook');
            $check=$data->where($where)->find();
			
            if(empty($check))$this->error('非法操作');
            $reslist=$data->where($where)->find();
			//p($reslist);die;
            $this->assign('reslist',$reslist);
            $this->display('EstateReserveBook');
        }
    }

    public function ReserveBooking(){

        $token      = $this->_get('token');
        $wecha_id   = $this->_get('wecha_id');
        $type   =   $this->_get('addtype');
        $this->assign('token',$token);
        $this->assign('wecha_id',$wecha_id);
        $book   =   M('Reservebook');
        $where = array('token'=>$token,'wecha_id'=>$wecha_id,'type'=>'estate');
        $books  = $book->where($where)->order('id desc')->select();
		 $count     = $book->where($where)->count();
        $Page       = new Page($count,10);
        $show       = $Page->show();
        $books      = $book->where($where)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('page',$show);
        $this->assign('books',$books);
		//p($books);die;

        $data = M("Reservation");
        $where2 = array('token'=>$token,'addtype'=>$type);
        $reser = $data->where($where2)->field('headpic,addtype')->find();
        //var_dump($headpic);
        $this->assign('reser',$reser);
		
        $this->display();
    }
    public function reservation(){
        $data = M("Reservation");
        $where = "`token`='".session('token')."' AND (`addtype`='estate' OR `addtype`='maintain')";
        $count      = $data->where($where)->count();
        $Page       = new Page($count,12);
        $show       = $Page->show();
        $reslist = $data->where($where)->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $house_count = $data->where(array('addtype' => 'estate','token'=>session('token')))->count();
        $maintain_count = $data->where(array('addtype' => 'maintain','token'=>session('token')))->count();
        $this->assign('house_count',$house_count);
        $this->assign('maintain_count',$maintain_count);
        $this->assign('page',$show);
        $this->assign('reslist',$reslist);
        $this->display();
    }
    public function func_post(){
            //$das['token']      = strval($this->_get('token'));
            $das['wecha_id']   = strval($this->_post('wecha_id'));
            //$da['rid']        = (int)$this->_post('rid');
            $da['truename']   = strval($this->_post("truename"));
            $da['tel']        = strval($this->_post("tel"));
            $da['dateline']   = strval($this->_post("dateline"));
            $da['timepart']   = strval($this->_post("timepart"));
            $da['info']       = strval($this->_post("info"));
            $da['type']       = strval($this->_post('booktype'));
            $da['housetype']  = $this->_post('housetype');
            $da['choose']     = $this->_post('choose');
            $da['booktime']   = time();
            $das['id']        = (int)$this->_post('mid');
            if($da['type'] =='maintain'){
                $da['carnum']   = strval($this->_post("carnum"));
                $da['km']       = (int)$this->_post('km');
            }
            $t_book   =   M('Reservebook');
            if($das['id'] != ''){
                $o = $t_book->where(array('id'=>$das['id'],'wecha_id'=>$das['wecha_id']))->save($da);
                if($o){
                     $arr=array('errno'=>0,'msg'=>'修改成功','token'=>$this->_get('token'),'wecha_id'=>$das['wecha_id'],'url'=>'');
                     echo json_encode($arr);
                     exit;
                }else{
                     $arr=array('errno'=>1,'msg'=>'修改失败','token'=>$this->_get('token'),'wecha_id'=>$das['wecha_id'],'url'=>'');
                    echo json_encode($arr);
                    exit;
                }
            }
    }
	
    public function delOrder(){

        $id = (int)$this->_get('id');
        $token = $this->_get('token');
        $wecha_id = $this->_get('wecha_id');
        $t_book   =   M('Reservebook');
        $check = $t_book->where(array('id'=>$id,'wecha_id'=>$wecha_id))->find();
        if($check){
            $t_book->where(array('id'=>$check['id']))->delete();
            $this->success('删除成功',U('Estate/mylist',array('token'=>$token,'wecha_id'=>$wecha_id,'addtype'=>$this->_get('addtype'))));
             exit;
         }else{
            $this->error('非法操作！');
             exit;
         }

    }
    //
}
?>