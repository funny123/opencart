<?php
class ModelExtensionPaymentWechat extends Model {
    public function getMethod($address, $total) {
        $this->load->language('extension/payment/wechat');

        if ($total <= 0.00) {
            $status = false;
        } else {
            $status = true;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => 'wechat',
                'title'      => $this->language->get('text_title'),
                'terms'      => '',
                'sort_order' => $this->config->get('wechat_sort_order')
            );
        }

        return $method_data;
    }
}