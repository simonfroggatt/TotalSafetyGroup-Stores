<?php

class ControllerTsgNotifications extends Controller
{
    public function index()
    {
        //reset test
        $this->session->data['dismissed_notifications'] = array();


        $data = array();
        $this->load->model('tsg/notifications');
        $notifications = $this->model_tsg_notifications->getNotifications();
        
        // Initialize dismissed notifications in session if not exists
        if (!isset($this->session->data['dismissed_notifications'])) {
            $this->session->data['dismissed_notifications'] = array();
        }
        
        foreach ($notifications as $notification) {
            // Skip if notification has been dismissed in this session
            if ($notification['dismissible'] && in_array($notification['id'], $this->session->data['dismissed_notifications'])) {
                continue;
            }
            $notification_html = $this->load->view('tsg/notifications', $notification);
            array_push($data, $notification_html);
        }
        return $data;
    }

    public function dismiss() {
        $json = array();
        
        if (isset($this->request->post['notification_id'])) {
            if (!isset($this->session->data['dismissed_notifications'])) {
                $this->session->data['dismissed_notifications'] = array();
            }
            
            $notification_id = (int)$this->request->post['notification_id'];
            if (!in_array($notification_id, $this->session->data['dismissed_notifications'])) {
                $this->session->data['dismissed_notifications'][] = $notification_id;
            }
            $json['success'] = true;
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}