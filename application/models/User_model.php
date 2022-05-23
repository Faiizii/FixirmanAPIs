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

class User_model extends CI_Model {
	public function __construct()
    {
       // Call the CI_Model constructor
        parent::__construct();
    }

    private $TYPE_PROVIDER = 'provider';
    private $TYPE_USER = 'user';
    private $TYPE_SIGN_UP = 'sign_up';
    private $TYPE_CHECK = 'check';

    private $SELECT_USER_DETAIL = 'id,name,description,image,phone,backup_phone,email,cnic,is_completed,is_verified';

    private function send_response($successCode,$message,$resultKey = null,$resutlt = null){
        $response['success'] = $successCode;
        $response['message'] = $message;
        if($resultKey){
            $response[$resultKey] = $resutlt;
        }
        return $response;
    }
    # Insert User Device
    public function insert_device($params){
        //to be continue
        $deviceInfo = array(
            'user_id'   =>  isset($params['user_id']) ? $params['user_id'] : '',
            'manufacturer'   =>  isset($params['manufacturer']) ? $params['manufacturer'] : '',
            'model'   =>  isset($params['model']) ? $params['model'] : '',
            'last_active_time'   =>  date('Y-m-d H:i:s'),
            'app_version'   =>  isset($params['app_version']) ? $params['app_version'] : '',
            'platform'   =>  isset($params['platform']) ? $params['platform'] : '',
            'serial'   =>  isset($params['serial']) ? $params['serial'] : '',
            'version'   =>  isset($params['version']) ? $params['version'] : '',
            'token'   =>  isset($params['token']) ? $params['token'] : '',
            'status'   =>  'A',
            'create_date'   =>  date('Y-m-d H:i:s'),
        );
        if(!empty($deviceInfo['serial'])){
            $storedDevice = $this->db->select('*')->from('user_devices')->where('serial',$deviceInfo['serial'])->get();
            if($storedDevice AND $storedDevice->num_rows() > 0){
                $this->db->where('serial',$deviceInfo['serial'])->update('user_devices',$deviceInfo);
            }else{
                $this->db->insert('user_devices',$deviceInfo);
            }
        }else{
            return;
        }
    }
    public function check_phone_number($params){
        $userType = $params['user_type'];
        $userInfo = $this->get_user($userType,'',$params['phone']);
        if(!$userInfo){
            //not registered user. register user with the given phone number
            $inputData = array(
                'phone'  =>  $params['phone'],
                'user_type'  =>  $params['user_type'],
                'login_with'  =>  'phone',
                'create_date'  =>  date('Y-m-d H:i:s')
            );
            $isInserted = $this->db->insert('user',$inputData);
            if(!$isInserted){
                return false;
            }else{
                $userInfo = $this->get_user($userType,'',$params['phone']);
            }
            
        }
        $params['user_id'] = $userInfo->id;
        $this->insert_device($params); // save device on successful login
        return $this->send_response(1,'Confirm your phone to continue','result',
                array('user'=>$userInfo,'ads' => $this->get_ads()));
    }
    public function get_user($userType,$userId = '',$phoneNumber = ''){
        $phoneStr = substr($phoneNumber,-10);
        $condition = "(id = '$userId' OR substring(phone,-10) = '$phoneStr') AND user_type = '$userType'";
        
        $userInfo = $this->db->select($this->SELECT_USER_DETAIL)->from('user')->where($condition)->get();
        if(!$userInfo){
            return null;
        }else{
            if($userInfo->row()){
                $userInfo->row()->addresses = $this->get_user_addresses($userInfo->row()->id);
                $tempRating = $this->get_user_avg_rating($userInfo->row()->id);
                $userInfo->row()->rating =  $tempRating ? $tempRating : 0;
                $userInfo->row()->total_reviews = count($this->get_user_total_rating($userInfo->row()->id)); 
                
                return $userInfo->row();
            }else{
                return null;
            }
        }
    }
    public function check_login($params){

        $password = md5($params['password']);
        $userType = $params['user_type'];
        $phoneStr = substr($params['phone'],-10);

        $condition = "substring(phone,-10) = '$phoneStr' AND user_type = '$userType' AND password = '$password'";

        $userInfo = $this->db->select($this->SELECT_USER_DETAIL)->from('user')->where($condition)->get();
        if(!$userInfo){
            return false;
        }else{
            if($userInfo->row()){
                $params['user_id'] = $userInfo->row()->id;
                $this->insert_device($params); // save device on successful login
                return $this->send_response(1,'Login Successful','user',$userInfo->row());
            }else{
                return $this->send_response(0,'Invalid phone or password');
            }
        }
    }
    public function update_password($params){
        $updateData = array(
            'password'  =>  md5($params['password'])
        );
        $phoneStr = substr($params['phone'],-10);
        $userType = $params['user_type'];
        $condition = "substring(phone,-10) = '$phoneStr' AND user_type = '$userType'";
        $isUpdated = $this->db->where($condition)->update('user',$updateData);
        
        if(!$isUpdated){
            return false;
        }else{
            if($this->db->affected_rows() > 0){
                return $this->send_response(1,'Your password updated successfully');
            }else{
                return $this->send_response(0,'You have already used this password. Please try different one!');
            }
        }
    }

    public function update_profile($params){
        $updateData = array(
            'name'  =>  $params['name'],
            'phone'  =>  $params['phone'],
            'email'  =>  $params['email'],
            'cnic'  =>  $params['cnic'],
            'description'  =>  $params['description'],
            'user_type'  =>  $params['user_type'],
            'is_completed'  =>  'Y'
        );
        if(isset($params['image']) AND !empty($params['image'])){
            $updateData['image'] = $params['image'];
        }
        $phoneStr = substr($params['phone'],-10);
        $userType = $params['user_type'];
        $condition = "substring(phone,-10) = '$phoneStr' AND user_type = '$userType'";

        $userInfo = $this->get_user($userType,'',$phoneStr);
        if(!$userInfo){
            return $this->send_response(0,'Your account is not exist. Please contact admin if persistly seeing this error');
        }

        $isUpdated = $this->db->where($condition)->update('user',$updateData);
        
        if(!$isUpdated){
            return false;
        }else{
            if($this->db->affected_rows() > 0){
                //$this->insert_device($params); // save device on successful login
                return $this->send_response(1,'Your profile updated successfully','user',$this->get_user($userType,'',$phoneStr));
            }else{
                return $this->send_response(0,'Information is already update to date');
            }
        }
    }
    public function save_address($params){
        //address_title
        $params['address_title'] = isset($params['address_title']) ? $params['address_title'] : '';
        if(isset($params['address_id']) AND !empty($params['address_id'])){
            $addressId = $params['address_id'];
            unset($params['address_id']);
            $isUpdated = $this->db->update('user_address',$params,array('id' => $addressId));
            if(!$isUpdated){
                return false;
            }
        }else{
            unset($params['address_id']);
            $params['create_date'] = date('Y-m-d H:i:s');

            $isInserted = $this->db->insert('user_address',$params);
            if(!$isInserted){
                return false;
            }
        }

        return $this->send_response(1,'Address Saved','addresses',$this->get_user_addresses($params['user_id']));
    }
    public function delete_address($params){
        $isDeleted = $this->db->where('id',$params['address_id'])->delete('user_address');
        if(!$isDeleted){
            return false;
        }else{
            return $this->send_response(1,"Your address is removd");
        }
    }
    public function get_user_addresses($userId){
        $address = $this->db->select('*')->from('user_address')->where('user_id',$userId)->get();
        if(!$address){
            return array();
        }else{
            return $address->result();
        }
    }
    public function get_user_total_rating($userId){
        $query = "SELECT * FROM `rating` where user_id = $userId";
        $result = $this->db->query($query);
        if(!$result){
            return 0;
        }else{
            return $result->result();
        }
    }
    public function get_user_avg_rating($userId){
        $query = "SELECT AVG(star) as star FROM `rating` where user_id = $userId";
        $result = $this->db->query($query);
        if(!$result){
            return 0;
        }else{
            if($result->row()){
                return (float)$result->row()->star;
            }else{
                return 0;
            }
        }
    }
    public function get_address_info($addressId){
        $address = $this->db->select('*')->from('user_address')->where('id',$addressId)->get();
        if(!$address){
            return array();
        }else{
            return $address->row();
        }
    }
    public function get_ads(){
        $result = $this->db->select('id,image,added_by')->from('ads')->where('is_active','Y')->get();
        if(!$result){
            return array();
        }else{
            return $result->result();
        }
    }
    public function get_faq($params){
        $result = $this->db->select('*')->from('faq')->where('language_id',$params['language_id'])->get();
        if(!$result){
            return array();
        }else{
            return $this->send_response(1,'FAQs loaded','faq',$result->result());
        }
    }
    
}

