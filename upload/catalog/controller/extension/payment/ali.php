<?php
class ControllerExtensionPaymentAli extends Controller {
    public function index() {
        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['text_loading'] = $this->language->get('text_loading');

        $data['continue'] = $this->url->link('checkout/success');

        return $this->load->view('extension/payment/ali', $data);
    }

    public function confirm() {
        if ($this->session->data['payment_method']['code'] == 'ali') {
            // 验证购物车中是否有商品并且商品库存是否充足
            if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
                $this->response->redirect($this->url->link('checkout/cart'));
            }

            //验证商品最小数量
            $products = $this->cart->getProducts();//商品信息
            foreach ($products as $product) {
                $product_total = 0;

                foreach ($products as $product_2) {
                    if ($product_2['product_id'] == $product['product_id']) {
                        $product_total += $product_2['quantity'];
                    }
                }

                if ($product['minimum'] > $product_total) {
                    $this->response->redirect($this->url->link('checkout/cart'));
                }
            }

            $this->load->model('checkout/order');
            $this->load->model('setting/setting');

            //系统订单号
            $orderId = $this->session->data['order_id'];

            //将订单状态改为Pending 状态值设置为1
            $this->db->query("UPDATE " . DB_PREFIX . "order SET order_status_id = 1 WHERE order_id = '" . (int)$orderId . "'");

            //支付宝配置信息
            $alipayInfo = $this->model_setting_setting->getSetting('payment_ali');

            //商品名称
            $productName = count($products) > 1 ? $products[0]['name'] . ' and others' : $products[0]['name'];

            //订单信息
            $orderInfo = $this->model_checkout_order->getOrder($orderId);

            //令牌
            $token = md5('merchant_id='.$alipayInfo['payment_ali_merchant_id'].'&authentication_code='.$alipayInfo['payment_ali_authentication_code'].'&merchant_trade_no='.$orderId.'&total_amount='.sprintf("%.2f", $orderInfo['total']));

            $params = array(
                "merchant_id" => $alipayInfo['payment_ali_merchant_id'],
                "authentication_code" => $alipayInfo['payment_ali_authentication_code'],
                "product_title" => $productName,
                "merchant_trade_no" => $orderId,
                "currency" => "AUD",
                "total_amount" => sprintf("%.2f", $orderInfo['total']),
                "create_time" => date('Y-m-d H:i:s'),
                // "notification_url" => $this->config->get('site_base').'?route=api/supay/aliCallback',
                "notification_url" => $alipayInfo['payment_ali_shop_url'].'?route=api/supay/aliCallback',
                "token" => $token,
                "return_url" => $alipayInfo['payment_ali_shop_url'].'?route=checkout/success',
//                "rmb_amount" => $orderInfo['currency_code'] == 'CNY' ? sprintf("%.2f", $orderInfo['total']) : ''
            );

            // print_r($params);

            $linkurl = 'https://api.superpayglobal.com/payment/bridge/merchant_request?'.http_build_query($params);

            $json = array('status' => true, 'url' => $linkurl);

            if (isset($this->request->server['HTTP_ORIGIN'])) {
                $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
                $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                $this->response->addHeader('Access-Control-Max-Age: 1000');
                $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        }
    }
}
