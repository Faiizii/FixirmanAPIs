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

class Api extends REST_Controller {

	function __construct()
    {
        // Construct our parent class
        parent::__construct();
		
		ini_set('always_populate_raw_post_data', -1);
		$this->load->helper('url');
		$this->load->model('Api_model', '', TRUE);
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
	
	
	public function load_categories_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id'));

		$categories = $this->Api_model->load_categories($params);
		if($categories){
			$this->response($categories, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function load_services_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','category_id'));

		$subCategories = $this->Api_model->load_services($params);
		if($subCategories){
			$this->response($subCategories, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function get_time_slots_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id'));

		$slots = $this->Api_model->get_time_slots($params);
		if($slots){
			$this->response($slots, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function load_service_types_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id'));

		$serviceTypes = $this->Api_model->load_service_types($params);
		if($serviceTypes){
			$this->response($serviceTypes, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function apply_coupons_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','coupon_code'));

		$serviceTypes = $this->Api_model->apply_coupon($params);
		if($serviceTypes){
			$this->response($serviceTypes, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function create_request_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','payment_method','address','latitude','longitude','items'));

		$requestResponse = $this->Api_model->create_request($params);
		if($requestResponse){
			$this->response($requestResponse, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function get_request_detail_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','request_id'));

		$requestResponse = $this->Api_model->get_request_details($params['request_id'],$params['user_id']);
		if($requestResponse){
			$this->response($requestResponse, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function get_user_requests_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','user_type'));

		$requestResponse = $this->Api_model->get_user_requests($params);
		if($requestResponse){
			$this->response($requestResponse, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function bid_request_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','request_id','description'));
		$submitted = $this->Api_model->bid_request($params);
		if($submitted){
			$this->response($submitted, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function bid_accept_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','bid_id'));
		$accepted = $this->Api_model->bid_accept($params);
		if($accepted){
			$this->response($accepted, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function change_bid_status_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','bid_id','status'));
		$cancel = $this->Api_model->change_bid_status($params);
		if($cancel){
			$this->response($cancel, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function change_request_status_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','request_id','status'));
		$isUpdated = $this->Api_model->change_request_status($params);
		if($isUpdated){
			$this->response($isUpdated, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
	public function submit_rating_post(){
		$params = $this->input->post();
		$this->validate_post(array('user_id','request_id','category_id',"rating","rating_by"));
		$isUpdated = $this->Api_model->submit_rating($params);
		if($isUpdated){
			$this->response($isUpdated, 200);
		}else{
			$this->response($this->response_failed(), 200);
		}
	}
}
