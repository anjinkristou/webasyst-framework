<?php 

class siteFrontendAction extends waViewAction
{   
	public function execute()
	{
	    $page = $this->params;
		if ($page && is_array($page)) {
			$params = waRequest::param();
			foreach ($params as $k => $v) {
				if (in_array($k, array('url', 'module', 'action'))) {
					unset($params[$k]);
				}
			}			
			$this->view->getHelper()->globals($params);
			$this->view->assign('page', $page);
			$this->view->assign('wa_theme_url', $this->getThemeUrl());
			$page['content'] = $this->view->fetch('string:'.$page['content']);
			$this->view->assign('page', $page);
			// set response
			$this->getResponse()->setTitle($page['title']);
			$this->getResponse()->setMeta(array(
				'keywords' => isset($page['keywords']) ? $page['keywords'] : '',
				'description' => isset($page['description']) ? $page['description'] : ''
			));
			$this->setThemeTemplate('page.html');
		} else {
		    // show exception
		    if ($this->params instanceof Exception) {
		    	$e = $this->params;
		    	$code = $e->getCode();
		    	$this->getResponse()->setStatus($code ? $code : 500);
		    	$this->view->assign('error_message', $e->getMessage());
		    } else {
		    	$code = 404;
		    	$this->getResponse()->setStatus($code);
		    }
		    // 404 error
		    if ($code == 404) {
		    	$this->getResponse()->setTitle('404. '._ws("Page not found"));
		    	$this->view->assign('error_message', _ws("Page not found"));
		    }
		    $this->view->assign('error_code', $code);
		    $this->setThemeTemplate('error.html');
		    $this->view->assign('page', array());
		}
	}
	
	public function display()
	{
	    if (waSystemConfig::isDebug()) {
	        return parent::display(false);
	    }
		try {
			return parent::display(false);
		} catch (SmartyCompilerException $e) {
			$message = preg_replace('/(on\sline\s[0-9]+).*$/i', '$1', $e->getMessage());
			$message = str_replace(wa()->getConfig()->getRootPath(), '', $message);
			throw new SmartyCompilerException($message, $e->getCode());
		}
	}
	
}