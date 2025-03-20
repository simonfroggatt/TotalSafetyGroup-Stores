<?php

class ControllerTsgNotifications extends Controller
{
    public function index()
    {
        $data = array();
        $this->load->model('tsg/notifications');
        $notifications = $this->model_tsg_notifications->getNotifications();
        foreach ($notifications as $notification){  //template_path, oc_tsg_extra_template.template_type, oc_tsg_product_extra_template.title
            $notification_html = $this->load->view('tsg/notifications', $notification);
            array_push($data, $notification_html);
        }
        return $data;
    }
}