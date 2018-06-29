<?php
class CompanyAction extends BaseAction{
	public $token;
	public $apikey;
	public function _initialize() {
		if (!defined('RES')) {
            define('RES', THEME_PATH . 'common');
        }
		$this->token=$this->_get('token');
		$this->assign('token',$this->token);
		$this->apikey=C('baidu_map_api');
		$this->assign('apikey',$this->apikey);
		$this->assign('staticFilePath',str_replace('./','/',THEME_PATH.'common/css/product'));
	}
	//公司信息
	public function companyDetail()
    {
        $company_model = M('Company');
        $where         = array(
            'token' => $this->token
        );
        $companies     = $company_model->where($where)->order('isbranch ASC')->select();
        $this->assign('companies', $companies);
        $infoType = 'companyDetail';
        $this->assign('infoType', $infoType);
        $this->display();
    }
	public function companyIntro()
    {
        $company_model = M('Company');
        $where         = array(
            'token' => $this->token
        );
        if (isset($_GET['companyid'])) {
            $where['id'] = intval($_GET['companyid']);
        }
        $thisCompany = $company_model->where($where)->find();
        $this->assign('thisCompany', $thisCompany);
        $infoType = 'companyDetail';
        $this->assign('infoType', $infoType);
        $this->display();
    }
	public function companyMap()
    {
        $this->apikey = C('baidu_map_api');
        $this->assign('apikey', $this->apikey);
        $company_model = M('Company');
        $where         = array(
            'token' => $this->token
        );
        if (isset($_GET['companyid'])) {
            $where['id'] = intval($_GET['companyid']);
        }
        $thisCompany = $company_model->where($where)->find();
        $this->assign('thisCompany', $thisCompany);
        $infoType = 'companyDetail';
        $this->assign('infoType', $infoType);
        $this->display();
    }
	public function map(){
		//店铺信息
		$company_model=M('Company');
		$where=array('token'=>$this->token);
		if (isset($_GET['companyid'])){
			$where['id']=intval($_GET['companyid']);
		}
		
		$thisCompany=$company_model->where($where)->find();
		$this->assign('thisCompany',$thisCompany);
		//分店信息
		$branchStores=$company_model->where(array('token'=>$this->token,'isbranch'=>1))->order('taxis ASC')->select();
		$branchStoreCount=count($branchStores);
		$this->assign('branchStoreCount',$branchStoreCount);
		$this->assign('branchStores',$branchStores);
		$this->assign('metaTitle','地图');
		$this->display();
	}
	public function walk($display=1){
		$company_model=M('Company');
		$where=array('token'=>$this->token);
		if (isset($_GET['companyid'])){
			$where['id']=intval($_GET['companyid']);
		}
		$thisCompany=$company_model->where($where)->find();
		$this->assign('thisCompany',$thisCompany);
		$this->assign('metaTitle','步行路线');
		if ($display){
		$this->display();
		}
	}
	public function bus(){
		$this->walk(0);
		$this->assign('metaTitle','公交地铁路线');
		$this->display('bus');
	}
	public function drive(){
		$this->walk(0);
		$this->assign('metaTitle','开车路线');
		$this->display('drive');
	}
}


?>