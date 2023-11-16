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

        if($blog_id > 0)
        {
            $this->document->setTitle('blog title');
            $this->document->setDescription('blog desc');
            $this->document->setKeywords('blog keyword');

            $data['breadcrumbs'][] = array(
                'text' => 'blog title',
                'href' => 'blog link'
            );

            $data['blog_header'] = 'Blog Title for blog' . $blog_id;

            $data['blog_content'] = html_entity_decode('blog blah blah blah', ENT_QUOTES, 'UTF-8');

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('tsg/blog', $data));
        }
        else {
            $this->document->setTitle('blog title');
            $this->document->setDescription('blog desc');
            $this->document->setKeywords('blog keyword');

            $data['breadcrumbs'][] = array(
                'text' => 'blog title',
                'href' => 'blog link'
            );

            $data['blog_header'] = "List of blogs";
            $data['blog_desc'] = "Some quick example text to build on the card title and make up the bulk of the card's content.";
            $data['blog_image'] = "image/banner.jpg";
            $data['blog_title'] = "Blog Title";
            $data['blog_more'] = "index.php?route=blog/blog&blog_id=1";
            $data['blog_back'] = "index.php?route=blog/blog&blog_id=0";
        


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
