<?php
class ModelExtensionPaymentAli extends Model {
    public function getMethod($address, $total) {
        $this->load->language('extension/payment/ali');

        if ($total <= 0.00) {
            $status = false;
        } else {
            $status = true;
        }

        $method_data = array();

        if ($status) {
            $method_data = array(
                'code'       => 'ali',
                'title'      => $this->language->get('text_title'),
                'terms'      => '',
                'sort_order' => $this->config->get('ali_sort_order')
            );
        }

        return $method_data;
    }
}