<?php
class ControllerBlogBlog extends Controller
{
    public function index()
    {

        $this->load->model('tsg/blog');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('Blogs'),
            'href' => $this->url->link('blog/blog')
        );

        if (isset($this->request->get['blog_id'])) {
            $blog_id = (int)$this->request->get['blog_id'];
        } else {
            $blog_id = 0;
        }

        if(USE_CDN){
            $data['image_path'] = TSG_CDN_URL;
        } else {
            $data['image_path'] = 'image/';
        }


        if($blog_id > 0)
        {
            $blog_data =  $this->model_tsg_blog->getBlog($blog_id);

            $this->document->setTitle($blog_data['meta_title']);
            $this->document->setDescription($blog_data['meta_description']);
            $this->document->setKeywords('meta_keywords');

            $data['breadcrumbs'][] = array(
                'text' => $blog_data['title'],
                'href' => $this->url->link('blog/blog', 'blog_id=' . $blog_id)
            );

            $data['blog_content'] = html_entity_decode($blog_data['blog_text'], ENT_QUOTES, 'UTF-8');


            $data['blog_image']= $data['image_path']. $blog_data['image'];
            $data['blog_title'] =$blog_data['title'];
            $data['blog_subtitle'] =$blog_data['sub_title'];
            $data['blog_more'] = "/index.php?route=blog/blog&blog_id=1";
            $data['blog_back'] = "/index.php?route=blog/blog";

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');


            $this->response->setOutput($this->load->view('tsg/blog', $data));
        }
        
        else {


            $this->document->setTitle('Current Blogs');
            $this->document->setDescription('Blogs for website');
            $this->document->setKeywords('blog keyword');

            $data['breadcrumbs'][] = array(
                'text' => 'Blogs',
                'href' => $this->url->link('blog/blog')
            );

            $blog_list = $this->model_tsg_blog->getBlogs();
            $data['blog_list'] = $blog_list;

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('tsg/blog_list', $data));
        }

    }
}
