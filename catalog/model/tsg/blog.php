<?php

class ModelTsgBlog extends Model{

    public function getBlogs()
    {
        $sql = "SELECT ". DB_PREFIX . "tsg_blogs.id,";
        $sql .= " ". DB_PREFIX . "tsg_blogs.slug,";
        $sql .= " ". DB_PREFIX . "tsg_blogs.title,";
        $sql .= " ". DB_PREFIX . "tsg_blogs.sub_title,";
        $sql .= " ". DB_PREFIX . "tsg_blogs.image, ";
        $sql .= " DATE_FORMAT(". DB_PREFIX . "tsg_blogs.date_added, '%D %M %Y') as date_added";
        $sql .= " FROM";
        $sql .= " ". DB_PREFIX . "tsg_blogs ";
        $sql .= " WHERE ". DB_PREFIX . "tsg_blogs.`status` = 1 ";
        $sql .= " AND ". DB_PREFIX . "tsg_blogs.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= "  AND ". DB_PREFIX . "tsg_blogs.language_id = '" . (int)$this->config->get('config_language_id') . "'";
        $sql .= " ORDER BY ". DB_PREFIX . "tsg_blogs.date_added DESC";

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getBlog($blog_id)
    {
        $sql = "SELECT * FROM ". DB_PREFIX . "tsg_blogs WHERE ". DB_PREFIX . "tsg_blogs.id = ".(int)$blog_id;
        $query = $this->db->query($sql);
        return $query->row;
    }

}