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

class Notification_model extends CI_Model {
	public function __construct()
    {
       // Call the CI_Model constructor
        parent::__construct();
    }
    
    private $TYPE_PROVIDER = 'provider';
    private $TYPE_USER = 'user';
    private $SELECT_NOTIFICATION = "";

    private function send_response($successCode,$message,$resultKey = null,$resutlt = null){
        $response['success'] = $successCode;
        $response['message'] = $message;
        if($resultKey){
            $response[$resultKey] = $resutlt;
        }
        return $response;
    }
    public function load_all_notifications($params){
        $notifications = $this->db->select($this->SELECT_NOTIFICATION)->from('notification')->where(array( 'user_id'=>$params['user_id'], 'sent_status'=>'Y'))->get();
        if(!$notifications){
            return false;
        }else{
            return $this->send_response(1,'Notifications Loaded','notifications',$notifications->result());
        }
    }
    private function get_notification($notificationId){
       $notification = $this->db->select('*')->from('notification')->where(array('id'=> $notificationId))->get();
       if(!$notification){
           return false;
       }else{
           return $notification->row();
       }
    }
    public function read_notification($params){
        $userId = $params['user_id']; $notificationId = $params['notification_id'];

        $this->db->set('read_status', 'Y');
        $this->db->set('read_date', date('Y-m-d'));
        $this->db->where(array('id' => $notificationId,'user_id'=>$userId, 'sent_status'=>'Y'));
        $isUpdated = $this->db->update('notification');
        if(!$isUpdated){
            return false;
        }else{
            return $this->send_response(1,'Notification marked as read');
        }
    }
    public function read_all_notifications($params){
        $userId = $params['user_id']; $userType = $params['user_type'];

        $this->db->set('read_status', 'Y');
        $this->db->set('read_date', date('Y-m-d'));
        $this->db->where(array('user_type' => $userType,'user_id'=>$userId));
        $isUpdated = $this->db->update('notification');
        if(!$isUpdated){
            return false;
        }else{
            return $this->send_response(1,'All notifications marked as read');
        }
    }
    private function get_user_devices($userId,$userType){
        $devices = $this->db->select('*')->from('user_devices')
                ->where(array('user_id'=>$userId,'status'=>'A'))->get();
        if(!$devices){
            return array();
        }else{
            return $devices->result();
        }
    }
    public function get_providers(){
        $result = $this->db->select('*')->from('user')->where('user_type','provider')->get();
        if(!$result){
            return array();
        }else{
            return $result->result();
        }
    }
    private function get_unread_notifications(){
        $notifactions = $this->db->get_where('notification',array('for_admin'=>'N', 'sent_status'=>'N'));
        if(!$notifactions){
            return array();
        }else{
            return $notifactions->result();
        }
    }
    public function insert_new_request_found_notification($userId,$requestId){
        $inputData = array(
            'user_id' => $userId,
            'user_type' => 'user',
            'title' => 'Request Recieved',
            'description' => 'Thank you for using our service. Our provider will respond to your request soon',
            'notification_type' => 'job',
            'content_id' => $requestId,
        );
        $this->insert_notification($inputData);

        foreach ($this->get_providers() as $key => $user) {
            $inputData = array(
                'user_id' => $user->id,
                'user_type' => 'provider',
                'title' => 'New Request Generated',
                'description' => 'New request has been received. Go and respond quickly to earn it',
                'notification_type' => 'job',
                'content_id' => $requestId,
            );
            $this->insert_notification($inputData);
    
        }
    }
    public function insert_request_change_notification($userId,$userType,$requestId,$status){
        $inputData = array(
            'user_id' => $userId,
            'user_type' => $userType,
            'title' => 'Request has been '.$status,
            'description' => 'Your request has been marked as '.$status,
            'notification_type' => 'job',
            'content_id' => $requestId,
        );
        $this->insert_notification($inputData);
    }
    public function insert_new_bid_received($requestId,$userId){
        $inputData = array(
            'user_id' => $userId,
            'user_type' => 'user',
            'title' => 'Request Receieved',
            'description' => 'Your request has been accepted by a new user',
            'notification_type' => 'job',
            'content_id' => $requestId,
        );
        $this->insert_notification($inputData);
    }
    public function insert_notification($params){

        $inputData = array(
            'user_id' => $params['user_id'],
            'user_type' => $params['user_type'],
            'title' => $params['title'],
            'description' => $params['description'],
            'notification_type' => $params['notification_type'],
            'job_id' => $params['content_id'],
            'entry_date' => date('Y-m-d H:i:s'),
            'schedule_date' => date('Y-m-d H:i:s'),
        );

        $this->db->insert('notification',$inputData);
    }
    public function send_imediate_notification($params){
        $devices = $this->get_user_devices($params['user_id'],$params['user_type']);
        foreach ($devices as $key => $device) {
            # code...
            $deviceToken = $device->token;
            $isSent = $this->send_notification($deviceToken,$params['title'],
                    $params['description'],$params['notification_type']);
            if($isSent){
                return $this->send_response(1,'Notification Sent','notification',$params);
            }else{
                //insert notification
                $this->insert_notification($params);
            }
        }
    }
    public function send_notifications(){
        $notifications = $this->get_unread_notifications();
        foreach ($notifications as $key => $notification) {
            # code...

            $devices = $this->get_user_devices($notification->user_id,$notification->user_type);
            //print_r($device);
            foreach ($devices as $key => $device) {
                # code...
                $deviceToken = $device->token;
                $isSent = $this->send_notification($deviceToken,$notification->title,
                        $notification->description,$notification->notification_type);
                if($isSent){
                    $this->db->set('sent_status', 'Y');
                    $this->db->where(array('id' => $notification->id));
                    $isUpdated = $this->db->update('notification');
                }
            }
        }
       // return $this->send_response(1,'Notification Sent');
    }
    private function send_notification($deviceToken,$title,$description,$type){

        $path_to_fcm = 'https://fcm.googleapis.com/fcm/send';
        $server_key = "key";
        
        $headers = array(
            'Authorization:key=' .$server_key,
            'Content-Type:application/json'
        );
        $fields = array(
            'to'=>$deviceToken,
            'notification'=>array('title'=>$title,'body'=>$description,'sound'=>1,'vibrate'=>1),
            'data'=>array('type'=>$type,'title'=>$title,'body'=>$description)
        );
                
        $payload = json_encode($fields);

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $path_to_fcm);
        curl_setopt($curl_session, CURLOPT_POST, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_session, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, $payload);

        $curlResult = curl_exec($curl_session);
        return $curlResult;
    }
		
}

