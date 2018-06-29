<?php
class ApplyAction extends UserAction{
	//报名表
	public function index(){
		$apply=M('Apply');
		$apply_cnt=$apply->count();
		$Page       = new Page($apply_cnt,20);
		$show       = $Page->show();
		$apply_info=$apply->limit($Page->firstRow.','.$Page->listRows)->order('time desc')->select();
		$this->assign('apply_cnt',$apply_cnt);
		$this->assign('page',$show);
		$this->assign('apply_info',$apply_info);
		$this->display();
	}
	public function add(){

		$apply=D('Apply');
		if(!empty($_POST)){
			if($apply->create()){
				if($apply->add()){
				$this->success('报名成功！');

				//alert("报名成功");
						
				}
				else{
				$this->error('数据写入错误');
				}
			}else{
				$this->error($apply->getError());
			}

		}
		else{

			$this -> display();

		}
	}
	public function show(){

		$apply=M('Apply');
		$apply_cnt=$apply->count();
		$Page       = new Page($apply_cnt,5);
		$show       = $Page->show();
		$apply_info=$apply->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('apply_cnt',$apply_cnt);
		$this->assign('page',$show);
		$this->assign('apply_info',$apply_info);
		$this->display();
	}
	public function applyExcel(){
		$name=date("Y-m-d H:i:s", time()) ;
		header('Content-Type: text/html; charset=utf-8');
        header('Content-type:application/vnd.ms-execl');
        header("Content-Disposition:filename=$name.xls");
        $letterArr = explode(',', strtoupper('a,b,c,d,e,f,g,h,i,j,k'));
        $arr = array(array('en' => 'id', 'cn' => '报名表号'), array('en' => 'name', 'cn' => '姓名'), array('en' => 'tel', 'cn' => '电话'),array('en' => 'duty', 'cn' => '职务'),array('en' => 'class', 'cn' => '参加班级'), array('en' => 'sex', 'cn' => '性别'),  array('en' => 'email', 'cn' => '邮箱'), array('en' => 'company', 'cn' => '公司'), array('en' => 'qq', 'cn' => 'QQ'), array('en' => 'remark', 'cn' => '意向要求'), array('en' => 'time', 'cn' => '报名时间'));

        $i = 0;
        $fieldCount = count($arr);
        $s = 0;
        foreach ($arr as $f) {
            if ($s < $fieldCount - 1) {
                echo iconv('utf-8', 'gbk', $f['cn']) . '	';
            } else {
                echo iconv('utf-8', 'gbk', $f['cn']) . '
';
            }
            $s++;
        }

		//================================================================================
		$apply=M('Apply');
		$sns=$apply->select();
		if ($sns) {
            foreach ($sns as $sn) {
                $j = 0;
                foreach ($arr as $field) {
                    $fieldValue = $sn[$field['en']];
	                    switch ($field['en']) {
	                    default:
	                        break;

//	                    case 'time':
//	                        if ($fieldValue) {
//
//	                            $fieldValue = date('Y-m-d H:i:s', $fieldValue);
//
//	                        } else {
//	                            $fieldValue = '';
//	                        }
//	                        break;

	                    case 'name':
	                        $fieldValue = iconv('utf-8', 'gbk', $fieldValue);
	                        break;
	                    case 'company':
	                        $fieldValue = iconv('utf-8', 'gbk', $fieldValue);
	                        break;
	                    case 'duty':
	                        $fieldValue = iconv('utf-8', 'gbk', $fieldValue);
	                        break;
						case 'class':
	                        $fieldValue = iconv('utf-8', 'gbk', $fieldValue);
	                        break;	
	                    case 'remark':
	                        $fieldValue = iconv('utf-8', 'gbk', $fieldValue);
	                        break;
	                    }


                    if ($j < $fieldCount - 1) {
                        echo $fieldValue . '	';
                    } else {
                        echo $fieldValue . '
';
                    }
                    $j++;
                }
                $i++;
            }
        }
        die;
	}
	//2014年12月11日 13:34:08 by leo
	public function lists(){
		$apply=M('Train_tel');
		$apply_cnt=$apply->where(array('token'=>session('token')))->count();
		$Page       = new Page($apply_cnt,10);
		$show       = $Page->show();
		$apply_info=$apply->where(array('token'=>session('token')))->limit($Page->firstRow.','.$Page->listRows)->order('createtime desc')->select();
		$this->assign('apply_cnt',$apply_cnt);
		$this->assign('page',$show);
		$this->assign('apply_info',$apply_info);

		$this->display();
	}
	public function trainAdd(){
		
		//echo '手机培训报名内容页面';
		$TrainModel = D('Train_tel');		
		if(!empty($_POST)){
			$_POST['statdate'] = strtotime($this->_post('statdate'));
			//p($_POST['statdate']);
			//exit();
			if($TrainModel ->create()){
				$res = $TrainModel ->add();
				if($res){
					//p($_POST);
					$this->success('发布成功', U('Apply/lists',array('token'=>session('token'))));
				}
			}else{
				$this->error($TrainModel->getError());
			}
		
		}else {
			
			$this->display();
			
		}
		
	}
	public function trainEdit(){
		//===============
		$TrainModel = D('Train_tel');
		$id=$this->_get('id','intval');
		$this->assign('id',$id);	
		if(!empty($_POST)){
			$_POST['statdate'] = strtotime($_POST['statdate']);
			$TrainModel->create();
			//$res = $TrainModel ->save($_POST);
			$res = $TrainModel ->save(); //主键名的隐藏域
			if($res){
					echo '编辑成功';
				   $this->success('编辑成功', U('Apply/lists',array('token'=>session('token'))));
			   }
			 
		}else{	
			$where['id']=$id;
			$res=$TrainModel->where($where)->find();
			$this->assign('info',$res);
			$this->display();
		}
	}
	public function del(){
		$TrainModel = D('Train_tel');
		$where['id']=$this->_get('id','intval');
		
		if($TrainModel->where($where)->delete()){
			$this->success('删除成功',U('Apply/lists',array('token'=>session('token'))));
		}else{
			$this->error('删除失败',U('Apply/lists',array('type'=>session('token'))));
		}
	}
}