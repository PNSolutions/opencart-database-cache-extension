<?php
require_once(DIR_SYSTEM . 'library/pnsols/db_cache.php');    

class ControllerModuleDbCache extends Controller {
	private $error = array(); 

	public function index() {   
		$this->language->load('module/db_cache');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('db_cache', $this->request->post);		

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_content_top'] = $this->language->get('text_content_top');
		$this->data['text_content_bottom'] = $this->language->get('text_content_bottom');		
		$this->data['text_column_left'] = $this->language->get('text_column_left');
		$this->data['text_column_right'] = $this->language->get('text_column_right');

		$this->data['entry_cacheTimeoutSeconds'] = $this->language->get('entry_cacheTimeoutSeconds');
		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['text_homepage'] = $this->language->get('text_homepage');
		$this->data['text_tab_general'] = $this->language->get('text_tab_general');

		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');
		$this->data['button_add_module'] = $this->language->get('button_add_module');
		$this->data['button_remove'] = $this->language->get('button_remove');

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/db_cache', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['action'] = $this->url->link('module/db_cache', 'token=' . $this->session->data['token'], 'SSL');

		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		$this->data['modules'] = array();

		$this->load->model('design/layout');

		$this->data['layouts'] = $this->model_design_layout->getLayouts();

		if (isset($this->request->post['db_cache_status'])) {
			$this->data['db_cache_status'] = $this->request->post['db_cache_status'];
		} else if ($this->config->get('db_cache_status')) {
			$this->data['db_cache_status'] = $this->config->get('db_cache_status');
		}
        
		if (isset($this->request->post['db_cache_cacheTimeoutSeconds'])) {
			$this->data['db_cache_cacheTimeoutSeconds'] = $this->request->post['db_cache_cacheTimeoutSeconds'];
		} else if ($this->config->get('db_cache_cacheTimeoutSeconds')) {
			$this->data['db_cache_cacheTimeoutSeconds'] = $this->config->get('db_cache_cacheTimeoutSeconds');
		}

		$this->template = 'module/db_cache.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/db_cache')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['db_cache_cacheTimeoutSeconds'] || $this->request->post['db_cache_cacheTimeoutSeconds'] <= 0) {
			$this->error['warning'] = $this->language->get('error_cacheTimeoutSeconds');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}

	public function install() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('db_cache', array('db_cache_cacheTimeoutSeconds' => DbCache::DEFAULT_CACHE_TIMEOUT_SECONDS, 'db_cache_status' => 1));
	}

	public function uninstall() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('db_cache');
        $this->getDbCache()->clear();
	}

    public function clear() {
        $dbCache = $this->getDbCache();
        $dbCache->clear();
        
        $redirectRoute = 'module/db_cache';
        if (isset($this->request->get['redirectRoute'])) {
            $redirectRoute = $this->request->get['redirectRoute'];   
        }

		$this->redirect($this->url->link($redirectRoute, 'token=' . $this->session->data['token'], 'SSL'));
    }

    private function getDbCache() {
        return DbCache::getInstance();
    }
}
?>