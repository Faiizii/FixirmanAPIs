<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API Controller
 *
 *
 * @package		CodeIgniter
 * @subpackage	Rest Server
 * @category	Model
 * @author		Faiizii Awan
*/


require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

use Restserver\Libraries\REST_Controller;

class User extends REST_Controller {
    
    function __construct()
    {
        // Construct our parent class
        parent::__construct();
		
		ini_set('always_populate_raw_post_data', -1);
		$this->load->helper('url');
		$this->load->model('User_model', '', TRUE);
    }
	# validate post parameters
	private function validate_post($array_to_validate=array())
	{
		if($array_to_validate && count($array_to_validate)){

			foreach($array_to_validate as $ele){

				if(empty($this->input->post($ele))){

					$response['success'] = 0;
					$response['message'] = 'Value missing for '.$ele;
					$this->response($response, 200);

				}

			}
		}
    }
    private function response_failed(){
        $response['success'] = 0;
        $response['message'] = 'Something went wrong';
        $response['error'] = implode($this->db->error());
        return $response;
    }

    //usage 
    //$posts['image'] = $this->upload_image($_FILES['image']['name'], "./uploads/directory_name/");
	
    private function upload_image($file, $path)
	{
		if (!empty($file)) {

			$this->load->library('upload');
			if (!is_dir(".".$path)) {
				mkdir(".".$path, 0755,true);
			}
			$config = array(
				'upload_path' => ".".$path,
				'allowed_types' => "gif|jpg|png|jpeg",
				'overwrite' => TRUE,
				'max_size' => "2048000", // Can be set to particular file size , here it is 2 MB(2048 Kb)
				'max_height' => "0",
				'max_width' => "0"
			);
			$new_name = time() . '123';
			$config['file_name'] = $new_name;

			$this->load->library('upload', $config);
			$this->upload->initialize($config);

			if ($this->upload->do_upload('image')) {
				$upload_data =  $this->upload->data();
				return $path.$upload_data['file_name'];
			} else {
				$response['success'] = 0;
				$response['message'] = $this->upload->display_errors();
				$this->response($response, 200);
			}
		}
	}

	//TODO save it to your laptop collection
	public function check_phone_number_post(){
		$params = $this->input->post();
		$this->validate_post(array('phone','user_type','type'));

		$phoneResponse = $this->User_model->check_phone_number($params);
		if($phoneResponse){
			$this->response($phoneResponse, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	
	//TODO save it to your laptop collection
	public function get_user_info_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','phone','user_type'));

		$userInfo = $this->User_model->get_user($params['user_type'],$params['user_id'],$params['phone']);
		if($userInfo){
			$this->response($userInfo, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}

	//TODO save it to your laptop collection
	public function login_post(){
		$params = $this->input->post();
		$this->validate_post(array('phone','user_type','password','login_with','token','serial'));

		$userInfo = $this->User_model->check_login($params);
		if($userInfo){
			$this->response($userInfo, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}

	//TODO save it to your laptop collection
	public function update_password_post(){
		$params = $this->input->post();
		$this->validate_post(array('phone','user_type','password','login_with','token','serial'));

		$userInfo = $this->User_model->update_password($params);
		if($userInfo){
			$this->response($userInfo, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function update_profile_post(){
        $params = $this->input->post();
		$this->validate_post(array('name','description','email','cnic','phone','user_type'));
		if(isset($_FILES['image']['name'])){
			$params['image'] = $this->upload_image($_FILES['image']['name'], "/uploads/user_images/");
        }
		

		$userInfo = $this->User_model->update_profile($params);
		if($userInfo){
			$this->response($userInfo, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function save_address_post(){
		$params = $this->input->post();

		$this->validate_post(array('latitude','longitude','address','user_id'));

		$isSaved = $this->User_model->save_address($params);

		if($isSaved){
			$this->response($isSaved, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function delete_address_post(){
		$params = $this->input->post();

		$this->validate_post(array('address_id','user_id'));

		$isDeleted = $this->User_model->delete_address($params);

		if($isDeleted){
			$this->response($isDeleted, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function get_faq_post(){
		$params = $this->input->post();

		$this->validate_post(array('language_id','user_id'));

		$faq = $this->User_model->get_faq($params);

		if($faq){
			$this->response($faq, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
    
}
