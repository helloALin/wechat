<?php
//店员管理控制器
class StaffAction extends BackAction{
	public function _initialize() {
        parent::_initialize();  //RBAC 验证接口初始化
    }

	public function index(){

		$company_staff_db = M('Company_staff');
        $data             = $company_staff_db->where(array(
            'token' => TOKEN
        ))->order('id desc')->select();
        $company_db       = M('Company');
        $companys         = $company_db->where(array(
            'token' => TOKEN
        ))->order('id ASC')->select();
        $companysByID     = array();
        if ($companys) {
            foreach ($companys as $c) {
                $companysByID[$c['id']] = $c;
            }
        }
        if ($data) {
            $i = 0;
            foreach ($data as $d) {
                $data[$i]['companyName'] = $companysByID[$d['companyid']]['name'];
                $i++;
            }
        }
        $this->assign('staffs', $data);
        $this->display();
    }
    public function staffSet()
    {
        $company_staff_db = M('Company_staff');
        if (IS_POST) {
            if (!trim($_POST['name']) || !trim($_POST['username']) || !intval($_POST['companyid'])) {
                $this->error('姓名、用户名和店铺都不能为空');
                exit();
            }
            $_POST['token']    = TOKEN;
            $_POST['time']     = time();
            $_POST['password'] = md5($_POST['password']);
            if (!isset($_GET['itemid'])) {
                $company_staff_db->add($_POST);
            } else {
                if (!trim($_POST['password'])) {
                    unset($_POST['password']);
                }
                $company_staff_db->where(array(
                    'id' => intval($_GET['itemid'])
                ))->save($_POST);
            }
            $this->success('操作成功', U('Staff/index'));
        } else {
            $company_db = M('Company');
            $companys   = $company_db->where(array(
                'token' => TOKEN
            ))->order('id ASC')->select();
            $this->assign('companys', $companys);

            if (isset($_GET['itemid'])) {
                $thisItem = $company_staff_db->where(array(
                    'id' => intval($_GET['itemid'])
                ))->find();
            } else {
                $thisItem['companyid'] = 0;
            }
            $this->assign('item', $thisItem);
            $this->display('staffSet');
        }
    }
    public function staffDelete()
    {
        $company_staff_db = M('Company_staff');
        $thisItem         = $company_staff_db->where(array(
            'id' => intval($_GET['itemid'])
        ))->find();
        if ($thisItem['token'] == TOKEN) {
            $data = $company_staff_db->where(array(
                'token' => TOKEN,
                'id' => $this->_get('itemid')
            ))->delete();
            $this->success('操作成功', U('Staff/index'));
        }
    }

}