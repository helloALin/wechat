<?php
class ActivityAction extends UserAction{
	
	public function _initialize() {
		parent::_initialize();
		if (!$this->token){
			exit();
		}
		//$this->reply_info_model=M('Gamereply_info');
	}
	
    public function index(){
        $this -> edit();
    }
    
    public function add(){
    	$this -> edit();
    }
    
    public function edit(){
    	$model = D('Activity');
    	$activity = $model->where(array('token' => $this->token))->find();
    	if(IS_POST){
    		$model->create();
    		if(empty($model->name))
    			$this->error('活动名称不能为空！');
    		$model->start_date= strtotime($model->start_date);
    		$model->end_date= strtotime($model->end_date);
    		if(!$model->start_date || !$model->end_date || $model->start_date > $model->end_date){
    			$this->error('活动时间不能为空，且截至时间必须大于开始时间');
    		}
    		$model->token = $this->token;
    		if($activity){
    			$model->id = $activity['id'];
    			$model->update_time = time();
    			if($model->save() !== false){
    				$this->success('保存成功！');
    			}else{
    				Log::write('活动信息保存失败：'.$model->getDbError());
    				$this->error('保存失败！');
    			}
    		}else{
    			$model->__unset('id');
    			$model->create_time = time();
    			if($model->add() !== false){
    				$this->success('保存成功！');
    			}else{
    				Log::write('活动信息添加失败：'.$model->getDbError());
    				$this->error('保存失败！');
    			}
    		}
    	} else {
	    	$this->assign('info', $activity);
	    	$this->display('edit');
    	}
    }
    
    public function applyList(){
    	$where = array('token'=>$this->token);
    	$id = $this->_get('id', 'trim,intval');
    	if($id){
    		$where['id'] = $id;
    	}
    	$activity =  M('Activity')->where($where)->field('id,name')->find();
    	$this->assign('info', $activity);
    	$model = M('ActivityApply');
    	unset($where['id']); $where['activity_id'] = $id;
    	$count  = $model->where($where)->count();
    	$Page   = new Page($count,20);
    	$list	= $model->where($where)->order('create_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$this->assign('page', $Page->show());
    	$this->assign('list', $list);
    	$this->display();
    }
    
    public function exportApplyList(){
    	$where = array('token'=>$this->token);
    	$id = $this->_get('id', 'trim,intval');
    	if($id){
    		$where['activity_id'] = $id;
    	}
    	$list	= M('ActivityApply')->where($where)->order('create_time desc')->field('name,tel,content,wecha_id,create_time')->select();
    	foreach ($list as &$item){
    		$item['wecha_id'] = $item['wecha_id'] ? '微信端报名' : '其他报名';
    		$item['create_time'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';
    	}
    	$tool = new ExcelUtils();
    	$tool->push($list, 'name,tel,content,wecha_id,create_time', '姓名,电话,备注,来源,报名时间', 
    			'活动报名名单', '', array(15,20,50,15,20));
    }
}
?>