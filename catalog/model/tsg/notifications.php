<?php

class ModelTsgNotifications extends Model{

    public function getNotifications()
    {
        $sql = "SELECT ";
	    $sql .= "oc_tsg_notifications.title, ";
	    $sql .= "oc_tsg_notifications.notification, ";
	    $sql .= "oc_tsg_notifications.dismissible,  ";
	    $sql .= "oc_tsg_notifications.order_by,  ";
	    $sql .= "oc_tsg_notification_types.`value` ";
        $sql .= "FROM ";
	    $sql .= "oc_tsg_notifications ";
	    $sql .= "INNER JOIN oc_tsg_notification_types ON  ";
        $sql .= "oc_tsg_notifications.notification_type = oc_tsg_notification_types.id  ";
        $sql .= "WHERE  ";
        $sql .= "oc_tsg_notifications.store_id = '" . (int)$this->config->get('config_store_id') . "'";
	    $sql .= " AND oc_tsg_notifications.is_active = 1 ";
        $sql .= "ORDER BY ";
	    $sql .= "oc_tsg_notifications.order_by ASC;";
        $query = $this->db->query($sql);

        return $query->rows;
    }
}