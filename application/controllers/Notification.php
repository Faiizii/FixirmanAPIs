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

class Notification extends REST_Controller {

	function __construct()
    {
        // Construct our parent class
        parent::__construct();
		
		ini_set('always_populate_raw_post_data', -1);
		$this->load->helper('url');
		$this->load->model('Notification_model', '', TRUE);
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
        $response['error'] = $this->db->error();
        return $response;
	}
	public function load_notifications_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id'));
		$isSent = $this->Notification_model->load_all_notifications($params);
		if($isSent){
			$this->response($isSent, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function send_notifications_post(){
		$params = $this->input->post();
		$isSent = $this->Notification_model->send_notifications();
		$response['success'] = 1;
        $response['message'] = 'Notification Sent';
		$this->response($response, 200);
	}
	public function send_imediate_notification_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','user_type','title','description','notification_type','content_id'));
		$isSent = $this->Notification_model->send_imediate_notification($params);
		if($isSent){
			$this->response($isSent, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function read_notification_post(){
		$params = $this->input->post();
		$this->validate_post(array('notification_id','user_id'));
		$isSent = $this->Notification_model->read_notification($params);
		if($isSent){
			$this->response($isSent, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function read_all_notifications_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_type','user_id'));
		$isSent = $this->Notification_model->read_all_notifications($params);
		if($isSent){
			$this->response($isSent, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
}
