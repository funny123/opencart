<?php
class ControllerExtensionPaymentWechat extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/wechat');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            //默认支付成功后的状态为Processing（order_status_id为2）
            $this->request->post['payment_wechat_order_status_id'] = 2;

            $this->model_setting_setting->editSetting('payment_wechat', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');

        $data['entry_order_status'] = $this->language->get('entry_order_status');

        $data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
        $data['entry_authentication_code'] = $this->language->get('entry_authentication_code');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/wechat', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/wechat', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);


        if (isset($this->request->post['payment_wechat_merchant_id'])) {
            $data['payment_wechat_merchant_id'] = $this->request->post['payment_wechat_merchant_id'];
        } else {
            $data['payment_wechat_merchant_id'] = $this->config->get('payment_wechat_merchant_id');
        }

        if (isset($this->request->post['payment_wechat_authentication_code'])) {
            $data['payment_wechat_authentication_code'] = $this->request->post['payment_wechat_authentication_code'];
        } else {
            $data['payment_wechat_authentication_code'] = $this->config->get('payment_wechat_authentication_code');
        }

        if (isset($this->request->post['payment_wechat_status'])) {
            $data['payment_wechat_status'] = $this->request->post['payment_wechat_status'];
        } else {
            $data['payment_wechat_status'] = $this->config->get('payment_wechat_status');
        }

        if (isset($this->request->post['payment_wechat_sort_order'])) {
            $data['payment_wechat_sort_order'] = $this->request->post['payment_wechat_sort_order'];
        } else {
            $data['payment_wechat_sort_order'] = $this->config->get('payment_wechat_sort_order');
        }

        if (isset($this->request->post['payment_wechat_shop_url'])) {
            $data['payment_wechat_shop_url'] = $this->request->post['payment_wechat_shop_url'];
        } else {
            $data['payment_wechat_shop_url'] = $this->config->get('payment_wechat_shop_url');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/wechat', $data));

    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/wechat')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}