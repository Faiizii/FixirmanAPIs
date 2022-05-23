<?php

/**
 * API Model
 *
 *
 * @package		CodeIgniter
 * @subpackage	Rest Server
 * @category	Model
 * @author		Faiizii Awan
*/

class Api_model extends CI_Model {
	public function __construct()
    {
       // Call the CI_Model constructor
        parent::__construct();
        $this->load->model('User_model', '', TRUE);
        $this->load->model('Notification_model', '', TRUE);
    }
    
    private $TYPE_PROVIDER = 'provider';
    private $TYPE_USER = 'user';

    private function send_response($successCode,$message,$resultKey = null,$resutlt = null){
        $response['success'] = $successCode;
        $response['message'] = $message;
        if($resultKey){
            $response[$resultKey] = $resutlt;
        }
        return $response;
    }
    public function currencyConverter_get($currency_input = 0,$currency_to ="PKR") {
		
		$req_url = "https://api.exchangerate-api.com/v4/latest/USD";
		$response_json = file_get_contents($req_url);

		// Continuing if we got a result
		if($response_json) {
			try {
				$response_object = json_decode($response_json);
				if($currency_to == "PKR"){
					return round(($currency_input * $response_object->rates->PKR), 2);
				}else{
					return round(($currency_input * $response_object->rates->EUR), 2);
				}				
			}
			catch(Exception $e) {
				return $currency_input;
			}
		}else{
			return $currency_input;
		}
	}
    public function load_categories($params){
        $data = $this->db->select('*')->from('category')->where('is_active','Y')->get();
        if(!$data){
            return false;
        }else{
            return $this->send_response(1,'Categories are loaded','categories',$data->result());
        }
    }
    public function load_services($params){
        $data = $this->db->select('*')->from('service')->where(array('is_active'=>'Y','category_id'=>$params['category_id']))->get();
        if(!$data){
            return false;
        }else{
            foreach ($data->result() as $key => $value) {
                # code...
                $value->price = $this->currencyConverter_get($value->price);
            }
            return $this->send_response(1,'Services are loaded','services',$data->result());
        }
    }
    public function get_time_slots($params){
        $slots = array('12:00 AM - 02:00 AM','02:00 AM - 04:00 AM','4:00 AM - 06:00 AM','06:00 AM - 08:00 AM',
        '08:00 AM - 10:00 AM','10:00 AM - 12:00 PM','12:00 PM - 02:00 PM','02:00 PM - 04:00 PM',
        '4:00 PM - 06:00 PM','06:00 PM - 08:00 PM',
        '08:00 PM - 10:00 PM','10:00 PM - 12:00 PM');
        $dates = array();
        $datetime = new DateTime('now');
        for ($i = 0; $i < 7; $i++) { 
            
            $date['day_name'] = $datetime->format('D');
            $date['date'] = $datetime->format('Y-m-d');
            $date['day_number'] = $datetime->format( 'd' );
            // if($datetime->format( 'N' ) >= 6){
            //     $i--; //remove saturday & sunday
            // }else{
            //     array_push($dates,$date);
            // }
            array_push($dates,$date);
            $datetime->modify('+1 day');
        }
        return $this->send_response(1,'Information loaded','result',array('dates'=>$dates,'slots'=>$slots));
    }
    public function load_service_types($params){
        $result = $this->db->select('*')->from('service_type')->where('is_active','Y')->get();
        if(!$result){
            return false;
        }else{
            return $this->send_response(1,"service types loaded",'service_types',$result->result());
        }
    }
    public function apply_coupon($params){
        $result = $this->db->select('*')->from('coupons')
            ->where(array('code'=>$params['coupon_code'],'is_active'=>'Y'))->get();
        if(!$result){
            return false;
        }else{
            if(count($result->result()) > 0){
                return $this->send_response(1,"service types loaded",'coupon_id',$result->row()->id);
            }else{
                return $this->send_response(0,"Invalid Coupon");
            }
        }
    }
    public function get_coupon($couponId){
        $result = $this->db->select('*')->from('coupons')->where('id',$couponId)->get();
        if(!$result){
            return false;
        }else{
            return $result->row();
        }
    }
    public function get_service_type($typeId){
        $result = $this->db->select('*')->from('service_type')->where('id',$typeId)->get();
        if(!$result){
            return false;
        }else{
            return $result->row();
        }
    }
    public function get_service_detail($serviceId){
        $result = $this->db->select('*')->from('service')->where('id',$serviceId)->get();
        if(!$result){
            return false;
        }else{
            return $result->row();
        }
    }
    public function get_category_detail($categoryId){
        $result = $this->db->select('*')->from('category')->where('id',$categoryId)->get();
        if(!$result){
            return false;
        }else{
            return $result->row();
        }
    }
    public function get_user_bid($userId,$requestId){
        $result = $this->db->select('*')->from('request_bid')->where(array('worker_id'=>$userId,'request_id'=>$requestId))->get();
        if(!$result){
            return null;
        }else{
            foreach ($result->result() as $key => $value) {
                $providerDetail = $this->User_model->get_user($this->TYPE_PROVIDER,$value->worker_id);
                if($providerDetail){
                    $value->name = $providerDetail->name;
                    $value->image = $providerDetail->image ? $providerDetail->image : '';
                    $value->rating = $providerDetail->rating;
                }
            }
            return $result->row();
        }
    }
    public function get_bid($bidId){
        $result = $this->db->select('*')->from('request_bid')->where('id',$bidId)->get();
        if(!$result){
            return array();
        }else{
            foreach ($result->result() as $key => $value) {
                $providerDetail = $this->User_model->get_user($this->TYPE_PROVIDER,$value->worker_id);
                if($providerDetail){
                    $value->name = $providerDetail->name;
                    $value->image = $providerDetail->image ? $providerDetail->image : '';
                    $value->rating = $providerDetail->rating;
                }
            }
            return $result->row();
        }
    }
    public function get_biders($requestId){
        $result = $this->db->select('*')->from('request_bid')->where('request_id',$requestId)->get();
        if(!$result){
            return array();
        }else{
            foreach ($result->result() as $key => $value) {
                $providerDetail = $this->User_model->get_user($this->TYPE_PROVIDER,$value->worker_id);
                if($providerDetail){
                    $value->name = $providerDetail->name;
                    $value->image = $providerDetail->image ? $providerDetail->image : '';
                    $value->rating = $providerDetail->rating;
                }
            }
            return $result->result();
        }
    }
    public function get_all_requests(){
        $requests = $this->db->select('*')->from('request')->get();
        if(!$requests){
            return array();
        }else{
            return $requests->result();
        }
    }
    public function get_all_user_requests($userId){
        $requests = $this->db->select('*')->from('request')->where('created_by',$userId)->get();
        if(!$requests){
            return array();
        }else{
            return $requests->result();
        }
    }
    
    public function get_all_provider_requests($userId){
        
        $requests = $this->db->select('*')->from('request')->where('worker_id',$userId)->or_where('worker_id',0)->get();
        if(!$requests){
            return array();
        }else{
            return $requests->result();
        }
    }
    public function get_request($requestId){
        $requests = $this->db->select('*')->from('request')->where('id',$requestId)->get();
        if(!$requests){
            return false;
        }else{
            return $requests->row();
        }
    }

    public function get_request_services($requestId){
        $requestCategories = $this->db->select('*')->from('request_service')->where('request_id',$requestId)->get();
        if(!$requestCategories){
            return array();
        }else{
            return $requestCategories->result();
        }
    }
    public function create_request($params){
        $requestInput = array(
            'created_by' => $params['user_id'],
            'payment_method' => $params['payment_method'],
            'address' => $params['address'],
            'latitude' => $params['latitude'],
            'longitude' => $params['longitude'],
            'create_date' => date('Y-m-d H:i:s')
        );
        if(isset($params['address_id']) AND $params['address_id'] > 0){
            $userAddress = $this->User_model->get_address_info($params['address_id']);
            if($userAddress){
                $requestInput['address_id'] = $params['address_id'];
                $requestInput['address'] = $userAddress->address;
                $requestInput['latitude'] = $userAddress->latitude;
                $requestInput['longitude'] = $userAddress->longitude;
                $requestInput['address_info'] = $userAddress->address_title;
            }
        }else{
            $requestInput['address_id'] = 0;
        }
        $this->db->trans_begin();
        

        $itemsArray = json_decode($params['items'],true);
        $serviceInput = array();
        $initialCost = 0;
        foreach ($itemsArray as $key => $item) {
            
            $coupon = $this->get_coupon($item['couponId']);
            $serviceType = $this->get_service_type($item['serviceTypeId']);

            $requestInput['category_id'] = $item['category']['id'];
            $requestInput['date'] = $item['date'];
            $requestInput['time_slot'] = $item['time'];
            $requestInput['description'] = $item['description'];
            $requestInput['service_type'] = $item['serviceTypeId'];
            $requestInput['coupon_id'] = $item['couponId'];
            
            if($coupon){
                $requestInput['coupon_discount'] = $coupon->discount;
            }
           
            $isInserted = $this->db->insert('request',$requestInput);
            if(!$isInserted){
                $this->db->trans_rollback();
                return false;
            }
            $requestId = $this->db->insert_id();

            foreach ($item['services'] as $key => $service) {
                $serviceDetails = $this->get_service_detail($service['id']);
                $tempServiceInput = array(
                    'service_id' =>  $service['id'],
                    'create_date'    =>  date('Y-m-d H:i:s'),
                    'request_id'    => $requestId
                );

                if($serviceDetails){
                    $tempServiceInput['price'] = $serviceDetails->price;
                    $initialCost = $initialCost + $serviceDetails->price;
                }

                array_push($serviceInput,$tempServiceInput);
            }
            $initialCost = $initialCost + ($initialCost * ($serviceType->factor / 100));
        }
        //TODO set is paid to Y if payment is online
        
        $this->Notification_model->insert_new_request_found_notification($params['user_id'],$requestId);

        $requestStatus = array(
            'request_id'  =>  $requestId,
            'create_date'   =>  date('Y-m-d H:i:s'),
            'update_by'    =>  $params['user_id']
        );
        $isStatusInserted = $this->db->insert('request_status',$requestStatus);
        if(!$isStatusInserted){
            $this->db->trans_rollback();
            return false;
        }
        $isCostUpdated = $this->db->set('initial_cost',$initialCost)->where('id',$requestId)->update('request');
        if(!$isCostUpdated){
            $this->db->trans_rollback();
            return false;
        }

        $isServiceInserted = $this->db->insert_batch('request_service',$serviceInput);
        if(!$isServiceInserted){
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return $this->get_request_details($requestId,'');
    }
    public function get_request_details($requestId,$userId){
        $requestDetail = $this->get_request($requestId);
        if(!$requestDetail){
            return false;
        }else{
            $userDetail = $this->User_model->get_user($this->TYPE_USER,$requestDetail->created_by);
            if($userDetail){
                $requestDetail->user_name = $userDetail->name;
                $requestDetail->user_image = $userDetail->image ? $userDetail->image : '';
                $requestDetail->user_phone = $userDetail->phone;
                $tempRating = $this->User_model->get_user_avg_rating($requestDetail->created_by);
                $requestDetail->user_rating =  $tempRating ? $tempRating : 0;
            }
            $providerDetail = $this->User_model->get_user($this->TYPE_PROVIDER,$requestDetail->worker_id);
            if($providerDetail){
                $requestDetail->provider_name = $providerDetail->name;
                $requestDetail->provider_image = $providerDetail->image ? $providerDetail->image : '';
                $requestDetail->provider_phone = $providerDetail->phone;
                $requestDetail->provider_description = $providerDetail->description;
                $tempRating = $this->User_model->get_user_avg_rating($requestDetail->worker_id);
                $requestDetail->provider_rating =  $tempRating ? $tempRating : 0;
            }
            $tempModel = $this->get_category_detail($requestDetail->category_id);
            if($tempModel){
                $requestDetail->category_name = $tempModel->title;
                $requestDetail->category_image = $tempModel->image ? $tempModel->image : '';
            }
            $serviceType = $this->get_service_type($requestDetail->service_type);
            if($serviceType){
                $requestDetail->service_type_name = $serviceType->title;
                $requestDetail->service_type_image = $serviceType->image ? $serviceType->image : '';
                $requestDetail->service_type_factor = $serviceType->factor;
            }
            $services = $this->get_request_services($requestId);
            foreach ($services as $key => $service) {
                $tempModel = $this->get_service_detail($service->service_id);
                if($tempModel){
                    $service->service_name = $tempModel->title;
                    $service->price = $this->currencyConverter_get($tempModel->price);
                }
            }
            if($requestDetail->worker_id == 0){
                $userBid = $this->get_user_bid($userId,$requestId);
                if($userBid){
                    $requestDetail->status = $userBid->status;
                }
            }

            $requestDetail->services = $services;
            $temp = $this->isSubmittedRating($userId,$requestId);
            if($temp>0){
                $requestDetail->status = 'RATED';
            }
            $requestDetail->bidders = $this->get_biders($requestId);
            //TODO check rated status on the basis of user id
            return $this->send_response(1,'Request detail loaded','request_detail',$requestDetail);
        }
    }
    private function isSubmittedRating($userId,$requestId){
        $ratingData = $this->db->select('*')->from('rating')->where(array('rated_by'=>$userId,'request_id'=>$requestId))->get();
        if(!$ratingData){
            return 0;
        }else{
            return count($ratingData->result());
        }
    }
    public function get_user_requests($params){
        if($params['user_type'] == $this->TYPE_USER){
            $requests = $this->get_all_user_requests($params['user_id']);
        }else if($params['user_type'] == $this->TYPE_PROVIDER){
            $requests = $this->get_all_provider_requests($params['user_id']);
        }
        $responseData = array();
        foreach ($requests as $key => $request) {
            $userDetail = $this->User_model->get_user($this->TYPE_USER,$request->created_by);
            if($userDetail){
                $request->user_name = $userDetail->name;
                $request->user_image = $userDetail->image;
                $request->user_phone = $userDetail->phone;
                $tempRating = $this->User_model->get_user_avg_rating($request->created_by);
                $request->user_rating =  $tempRating ? $tempRating : 0;
            }
            $providerDetail = $this->User_model->get_user($this->TYPE_PROVIDER,$request->worker_id);
            if($providerDetail){
                $request->provider_name = $providerDetail->name;
                $request->provider_image = $providerDetail->image;
                $request->provider_phone = $providerDetail->phone;
                $tempRating = $this->User_model->get_user_avg_rating($request->worker_id);
                $request->provider_rating =  $tempRating ? $tempRating : 0;
            }
            
            $tempModel = $this->get_category_detail($request->category_id);
            if($tempModel){
                $request->category_name = $tempModel->title;
                $request->category_image = $tempModel->image;
            }
            $serviceType = $this->get_service_type($request->service_type);
            if($serviceType){
                $request->service_type_name = $serviceType->title;
                $request->service_type_image = $serviceType->image;
                $request->service_type_factor = $serviceType->factor;
            }
            if($request->worker_id == 0){
                $userBid = $this->get_user_bid($params['user_id'],$request->id);
                if($userBid){
                    $request->status = $userBid->status;
                }
            }
            array_push($responseData,$request);
        }

        return $this->send_response(1,'All qequest are loaded','requests',$responseData);
    }
    public function bid_request($params){
        $requestDetail = $this->get_request($params['request_id']);
        if(!$requestDetail){
            return $this->send_response(0,"Looks like this request has been removed. Please try again later");
        }

        $inputData = array(
            'request_id'  =>  $params['request_id'],
            'description'  =>  $params['description'],
            'worker_id'  =>  $params['user_id'],
            'create_date'  =>  date('Y-m-d H:i:s')
        );

        $isInserted = $this->db->insert('request_bid',$inputData);
        if(!$isInserted){
            return false;
        }else{
            $insertedId = $this->db->insert_id();
            $this->Notification_model->insert_new_bid_received($params['request_id'],$requestDetail->created_by);
            return $this->send_response(1,"Your request has been sent.",'bid',$this->get_bid($insertedId));
        }
    }
    public function change_bid_status($params){
        $bidDetail = $this->get_bid($params['bid_id']);
        if(!$bidDetail){
            return $this->send_response(1,"Invalid bid request");
        }

        $this->db->trans_begin();
        $this->db->set('status',$params['status']);
        $this->db->where('id',$params['bid_id']);
        $isUpdated = $this->db->update('request_bid');
        if(!$isUpdated){
            $this->db->trans_rollback();
            return false;
        }
        if($params['status'] == 'REJECT'){
            $this->Notification_model->insert_request_change_notification($bidDetail->worker_id,'provider', $bidDetail->request_id,"REJECT");
        }
        $this->db->trans_commit();
        return $this->send_response(1,"Bid status has been changed");
    }
    public function bid_accept($params){
        $bidDetail = $this->get_bid($params['bid_id']);
        if(!$bidDetail){
            return $this->send_response(1,"Invalid bid request");
        }

        $requestDetail = $this->get_request($bidDetail->request_id);
        if(!$requestDetail){
            return $this->send_response(0,"Looks like this request has been removed. Please try again later");
        }
        if($requestDetail->worker_id != 0){
            return $this->send_response(0,"The task has already been assigned to someone else");
        }

        $this->db->trans_begin();
        $this->db->set('status',"ACCEPT");
        $this->db->set('worker_id',$bidDetail->worker_id);
        $this->db->where('id',$bidDetail->request_id);
        $isUpdated = $this->db->update('request');
        if(!$isUpdated){
            $this->db->trans_rollback();
            return false;
        }
        $isDeleted = $this->db->delete('request_bid','request_id = '.$bidDetail->request_id);
        if(!$isDeleted){
            $this->db->trans_rollback();
            return false;
        }

        $inputData = array(
            'request_id'  =>  $bidDetail->request_id,
            'status'  =>  "ACCEPT",
            'update_by'  =>  $params['user_id'],
            'create_date'  =>  date('Y-m-d H:i:s')
        );
        $isInserted = $this->db->insert("request_status",$inputData);
        if(!$isInserted){
            $this->db->trans_rollback();
            return false;
        }

        $this->Notification_model->insert_request_change_notification($bidDetail->worker_id,'provider', $bidDetail->request_id,"ACCEPT");

        $this->db->trans_commit();

        return $this->send_response(1,"Status updated successfully");


    }
    public function change_request_status($params){
        $requestDetail = $this->get_request($params['request_id']);
        if(!$requestDetail){
            return $this->send_response(0,"Looks like this request has been removed. Please try again later");
        }
        if($requestDetail->worker_id != 0){
            if($requestDetail->worker_id != $params['user_id'] AND $requestDetail->created_by != $params['user_id']){
                return $this->send_response(0,"Looks like you dont have permission to ".$params['status']." it. Please referesh your list");
            }
        }
        $inputData = array(
            'request_id'  =>  $params['request_id'],
            'status'  =>  $params['status'],
            'update_by'  =>  $params['user_id'],
            'create_date'  =>  date('Y-m-d H:i:s')
        );
        $this->db->trans_begin();
        $this->db->set('status',$params['status']);
        $this->db->where('id',$params['request_id']);
        $isUpdated = $this->db->update('request');
        if(!$isUpdated){
            $this->db->trans_rollback();
            return false;
        }
        if($this->db->affected_rows() < 1){
            $this->db->trans_rollback();
            return $this->send_response(1,'The request already have this status');
        } 
        $isInserted = $this->db->insert("request_status",$inputData);
        if(!$isInserted){
            $this->db->trans_rollback();
            return false;
        }
        
        $userId = $requestDetail->worker_id == $params['user_id'] ? $requestDetail->created_by : $params['user_id'];
        $userType = $requestDetail->worker_id == $params['user_id'] ? "user" : "provider" ;

        $this->Notification_model->insert_request_change_notification($userId,$userType,$params['request_id'],$params['status']);

        $this->db->trans_commit();

        return $this->send_response(1,"Status updated successfully");
        
    }
    public function submit_rating($params){
        $inputData = array(
            'user_id'  =>  $params['user_id'],
            'category_id'  =>  $params['category_id'],
            'request_id'  =>  $params['request_id'],
            'star'  =>  $params['rating'],
            'feedback'  =>  $params['feedback'],
            'rated_by'  =>  $params['rating_by'],
            'create_date' => date('Y-m-d H:i:s')
        );

        $this->db->trans_begin();
        $isInserted = $this->db->insert("rating",$inputData);

        if(!$isInserted){
            $this->db->trans_rollback();
            return false;
        }
        if($this->isSubmittedRating($params['user_id'],$params['request_id']) > 0){
            //update the request status if both have submitted the rating
            //otherwise rated status will be given on the basis of the user id in request detail
            $this->db->set('status',"RATED");
            $this->db->where('id',$params['request_id']);
            $isUpdated = $this->db->update('request');

            if(!$isUpdated){
                $this->db->trans_rollback();
                return false;
            }
        }
        
        $this->db->trans_commit();
        return $this->send_response(1,"Thank you for submitting your feedback.");

    }
}

