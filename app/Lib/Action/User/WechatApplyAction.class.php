<?php
class WechatApplyAction extends UserAction{
	//报名表
	public function index(){
		$apply=M('wechat_apply');
		$apply_cnt=$apply->count();
		$Page       = new Page($apply_cnt,20);
		$show       = $Page->show();
		
		$apply_info=$apply->limit($Page->firstRow.','.$Page->listRows)->order('time desc')->where(array('token'=>session('token')))->select();
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
        $letterArr = explode(',', strtoupper('a,b,c,d,e'));
        $arr = array(array('en' => 'id', 'cn' => '报名表号'), array('en' => 'name', 'cn' => '姓名'), array('en' => 'tel', 'cn' => '手机'),array('en' => 'duty', 'cn' => '职务'),array('en' => 'time', 'cn' => '报名时间'));

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
		$apply=M('wechat_apply');
		$sns=$apply->where(array('token'=>session('token')))->order('time desc')->select();
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
	
	public function del(){
		$TrainModel = D('wechat_apply');
		$where['id']=$this->_get('id','intval');
		
		if($TrainModel->where($where)->delete()){
			$this->success('删除成功',U('WechatApply/index',array('token'=>session('token'))));
		}else{
			$this->error('删除失败',U('WechatApply/index',array('type'=>session('token'))));
		}
	}
}