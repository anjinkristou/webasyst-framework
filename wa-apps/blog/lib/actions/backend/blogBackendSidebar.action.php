<?php
/**
 * @author WebAsyst Team
 *
 */
class blogBackendSidebarAction extends waViewAction
{
    /**
     * @var waAuthUser
     */
    protected $user;

    public function execute()
    {
        $this->user = $this->getUser();
        $blog_id = waRequest::get('blog', null, 'int');
        $post_id = waRequest::get('id', null, 'int');
        $request = waRequest::get();

        $module = waRequest::get('module');
        $action = waRequest::get('action');

        if (!$action) {
            $action = waRequest::get('module');
        }

        $view_all_posts = waRequest::get('all', null) !== null || empty($request);

        $blog_model = new blogBlogModel();

        $blogs = $blog_model->getAvailable($this->user,array(),null, array('new'=>true, 'expire'=>1,'link' => false));
        $blog_ids = array_keys($blogs);

        $comment_model = new blogCommentModel();

        $comment_count = $comment_model->getCount($blog_ids);

        $activity_datetime = blogActivity::getUserActivity();
        $comment_new_count = $comment_model->getCount($blog_ids, null, $activity_datetime, 1);

        $post_count = 0;

        $new_post_count = 0;

        $writable_blogs = false;
        foreach ($blogs as $blog) {
            $post_count += $blog['qty'];

            if ($blog['rights'] >= blogRightConfig::RIGHT_READ_WRITE) {
                $writable_blogs = true;
            }

            if (isset($blog['new_post']) && $blog['new_post'] > 0) {
                $new_post_count += $blog['new_post'];
            }
        }

        if ($writable_blogs) {
            $post_model = new blogPostModel();
            $search_options = array('status' =>array(blogPostModel::STATUS_DRAFT, blogPostModel::STATUS_DEADLINE, blogPostModel::STATUS_SCHEDULED));
            if (!$this->user->isAdmin($this->getApp()) ) {
                $search_options['contact_id'] = $this->user->getId();
            }
            $search_options['sort'] = 'overdue';
            $drafts = $post_model->search($search_options, array(
                	'status' => true,
                	'link'=>false,
                    'plugin'=>false,
                    'comments'=>false,
            ), array('blog' => $blogs))->fetchSearchAll(false);

            $where = "status = '".blogPostModel::STATUS_DEADLINE."' AND datetime <= '".waDateTime::date("Y-m-d")."'";
            if (!$this->getUser()->isAdmin($this->getApp())) {
                $where .= " AND contact_id = {$this->getUser()->getId()}";
                $where .= " AND blog_id IN (".implode(', ', array_keys($blogs)).")";
            }
            $count_overdue = $post_model->select("count(id)")->where($where)->fetchField();
            $count_overdue = ($count_overdue) ? $count_overdue : 0;
        } else {
            $drafts = false;
            $count_overdue = false;
        }

        /**
         * Extend backend sidebar
         * Add extra sidebar items (menu items, additional sections, system output)
         * @event backend_sidebar
         * @example #event handler example
         * public function sidebarAction()
         * {
         *     $output = array();
         *
         *     #add external link into sidebar menu
         *     $output['menu']='<li>
         *         <a href="http://www.webasyst.com">
         *             http://www.webasyst.com
         *         </a>
         *     </li>';
         *
         *     #add section into sidebar menu
         *     $output['section']='';
         *
         *     #add system link into sidebar menu
         *     $output['system']='';
         *
         *     return $output;
         * }
         * @return array[string][string]string $return[%plugin_id%]['menu'] Single menu items
         * @return array[string][string]string $return[%plugin_id%]['section'] Sections menu items
         * @return array[string][string]string $return[%plugin_id%]['system'] Extra menu items
         */
        $this->view->assign('backend_sidebar', wa()->event('backend_sidebar'));

        $this->view->assign('blog_id', $blog_id);
        $this->view->assign('blogs', $blogs);
        $this->view->assign('view_all_posts', $view_all_posts);

        $this->view->assign('action', $action);
        $this->view->assign('module', $module);

        $this->view->assign('post_id', $post_id);
        $this->view->assign('new_post', waRequest::get('action') == 'edit' && waRequest::get('id') == '');
        $this->view->assign('drafts', $drafts);

        $this->view->assign('comment_count', $comment_count);
        $this->view->assign('comment_new_count', $comment_new_count);
        $this->view->assign('post_count', $post_count);
        $this->view->assign('new_post_count', $new_post_count);
        $this->view->assign('count_draft_overdue', $count_overdue);
        $this->view->assign('writable_blogs', $writable_blogs);
    }
}