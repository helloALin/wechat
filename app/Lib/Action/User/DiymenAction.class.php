<?php
class DiymenAction extends UserAction{
	//自定义菜单配置
	public function index(){
		$data=M('Diymen_set')->where(array('token'=>$_SESSION['token']))->find();
		if(IS_POST){
			$_POST['token']=$_SESSION['token'];
			if($data==false){
				$this->all_insert('Diymen_set');
			}else{
				$_POST['id']=$data['id'];
				$this->all_save('Diymen_set');
			}
		}else{
			$this->assign('diymen',$data);
			$class=M('Diymen_class')->where(array('token'=>session('token'),'pid'=>0))->order('sort desc')->select();//dump($class);
			foreach($class as $key=>$vo){
				$c=M('Diymen_class')->where(array('token'=>session('token'),'pid'=>$vo['id']))->order('sort desc')->select();
				$class[$key]['class']=$c;
			}
			//dump($class);
			$this->assign('class',$class);
			$this->display();
		}
	}


	public function  class_add(){
		if(IS_POST){
            $_POST['token'] = session('token');
			$this->all_insert('Diymen_class','/class_add');
		}else{
			$class=M('Diymen_class')->where(array('token'=>session('token'),'pid'=>0))->order('sort desc')->select();
			$this->assign('class',$class);
			$this->display();
		}
	}
	public function  class_del(){
		$class=M('Diymen_class')->where(array('token'=>session('token'),'pid'=>$this->_get('id')))->order('sort desc')->find();
		//echo M('Diymen_class')->getLastSql();exit;
		if($class==false){
			$back=M('Diymen_class')->where(array('token'=>session('token'),'id'=>$this->_get('id')))->delete();
			if($back==true){
				$this->success('删除成功');
			}else{
				$this->error('删除失败');
			}
		}else{
			$this->error('请删除该分类下的子分类');
		}


	}
	public function  class_edit(){
		if(IS_POST){
			$_POST['id']=$this->_get('id');
			$this->all_save('Diymen_class','/class_edit?id='.$this->_get('id'));
		}else{
			$data=M('Diymen_class')->where(array('token'=>session('token'),'id'=>$this->_get('id')))->find();
			if($data==false){
				$this->error('您所操作的数据对象不存在！');
			}else{
				$class=M('Diymen_class')->where(array('token'=>session('token'),'pid'=>0))->order('sort desc')->select();//dump($class);
				$this->assign('class',$class);
				$this->assign('show',$data);
			}
			$this->display();
		}
	}
	public function  class_send(){
		if(IS_GET){
			$token = session("token");
			$access_token = getAccessToken($token);
			if($access_token["status"]){
				$access_token = $access_token["info"];
			}else{
				$this->error($access_token["info"]);
			}
			
			$data = '{"button":[';

			$class=M('Diymen_class')->where(array('token'=>$token,'pid'=>0))->limit(3)->order('sort desc')->select();//dump($class);
			$kcount=M('Diymen_class')->where(array('token'=>$token,'pid'=>0))->limit(3)->order('sort desc')->count();
			$k=1;
			foreach($class as $key=>$vo){
				//主菜单

				$data.='{"name":"'.$vo['title'].'",';
				$c=M('Diymen_class')->where(array('token'=>$token,'pid'=>$vo['id']))->limit(5)->order('sort desc')->select();
				//dump($c);
				$count=M('Diymen_class')->where(array('token'=>$token,'pid'=>$vo['id']))->limit(5)->order('sort desc')->count();
				//子菜单
				if($c!=false){
					$data.='"sub_button":[';
				}else{
					if ($vo['url']) {
						$data.='"type":"view","url":"'.str_replace(array('&amp;'),array('&'),$vo['url']).'"';
					}else{
						$data.='"type":"click","key":"'.$vo['keyword'].'"';
					}
				}
				$i=1;
				foreach($c as $voo){
					$voo['url']=str_replace(array('&amp;'),array('&'),$voo['url']);
					if($i==$count){
						if($voo['url']){
							$data.='{"type":"view","name":"'.$voo['title'].'","url":"'.$voo['url'].'"}';
						}else{
							$data.='{"type":"click","name":"'.$voo['title'].'","key":"'.$voo['keyword'].'"}';
						}
					}else{
						if($voo['url']){
							$data.='{"type":"view","name":"'.$voo['title'].'","url":"'.$voo['url'].'"},';
						}else{
							$data.='{"type":"click","name":"'.$voo['title'].'","key":"'.$voo['keyword'].'"},';
						}
					}
					$i++;
				}
				if($c!=false){
					$data.=']';
				}

				if($k==$kcount){
					$data.='}';
				}else{
					$data.='},';
				}
				$k++;
			}
			$data.=']}';

			curlGet('https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$access_token);

			$url='https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$access_token;

			if($this->api_create_menu($url,$data)==false){
				$this->error('操作失败');
			}else{
				$this->success('操作成功',U('Diymen/index'));
			}
			exit;
		}else{
			$this->error('非法操作');
		}
	}
	
	function api_create_menu($url, $data){
		$content = curlPost($url, $data);
		$json = json_decode($content);
		if ($json && $json->errcode == 0) {
			return true;
		}else{
			Log::write("DiymemAction.class_send - 创建自定义菜单：".$content);
			return false;
		}
	}
}
	?>