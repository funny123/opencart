<?php
require_once(DIR_SYSTEM . 'helper/http.php');
class ControllerExtensionPaymentWechat extends Controller {
    public function index() {
        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['text_loading'] = $this->language->get('text_loading');

        $data['continue'] = $this->url->link('checkout/success');

        $data['return_url'] = $this->config->get('site_base').'?route=checkout/success';

        $data['return_index_url'] = $this->config->get('site_base');

        return $this->load->view('extension/payment/wechat', $data);
    }

    public function confirm() {
        if ($this->session->data['payment_method']['code'] == 'wechat') {
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

            //微信配置信息
            $wechatInfo = $this->model_setting_setting->getSetting('payment_wechat');

            //商品名称
            //$productName = count($products) > 1 ? $products[0]['name'] . ' and others' : $products[0]['name'];// removed by Alex 2018-01-31
            $productName = count($products) > 1 ? (current($products)['name'] . ' and others') : current($products)['name'];

            //订单信息
            $orderInfo = $this->model_checkout_order->getOrder($orderId);

            //令牌
            $token = md5('merchant_id='.$wechatInfo['payment_wechat_merchant_id'].'&authentication_code='.$wechatInfo['payment_wechat_authentication_code'].'&merchant_trade_no='.$orderId.'&total_amount='.sprintf("%.2f", $orderInfo['total']));
            /*// removed by Alex 2018-01-31
            $params = array(
                "merchant_id" => $wechatInfo['wechat_merchant_id'],
                "authentication_code" => $wechatInfo['wechat_authentication_code'],
                "product_title" => $productName,
                "merchant_trade_no" => $orderId,
                "currency" => "AUD",
                "total_amount" => sprintf("%.2f", $orderInfo['total']),
                "create_time" => date('Y-m-d H:i:s'),
                "notification_url" => $this->config->get('site_base').'?route=api/supay/wechatCallback',
                "token" => $token,
            );
            */
            $params = array(
                "merchant_id" => $wechatInfo['payment_wechat_merchant_id'],
                "authentication_code" => $wechatInfo['payment_wechat_authentication_code'],
                "product_title" => $productName,
                "merchant_trade_no" => $orderId,
                "currency" => "AUD",
                "total_amount" => sprintf("%.2f", $orderInfo['total']),
                "create_time" => date('Y-m-d H:i:s'),
                // "notification_url" => $this->config->get('site_base').'?route=api/supay/wechatCallback',
                "notification_url" => $wechatInfo['payment_wechat_shop_url'].'?route=api/supay/wechatCallback',
				"return_url" => $wechatInfo['payment_wechat_shop_url'].'?route=checkout/success',
                "token" => $token,
//                "rmb_amount" => $orderInfo['currency_code'] == 'CNY' ? sprintf("%.2f", $orderInfo['total']) : ''
            );

            $curl = new Curl();
            $curl->url('https://api.superpayglobal.com/payment/wxpayproxy/merchant_request');
            $curl->data($params);

            $result = $curl->get();
            $res = json_decode($result['data'], true);
            if ($result['status'] && $res['result'] == 'SUCCESS') {
                //$json = array('status' => true, 'url' => $res['QRCodeImgPath']);// removed by Alex 2018-01-31
                $json = array('status' => true, 'url' => $res['supayCashierURL']);
                
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

    public function checkWechat() {
        try {
            $this->load->model('checkout/order');
            $this->load->model('setting/setting');

            //系统订单号
            $orderId = $this->session->data['order_id'];
            if (empty($orderId)) {
                throw new Exception("缺少订单号...");
            }

            //微信配置信息
            $wechatInfo = $this->model_setting_setting->getSetting('payment_wechat');
            if (empty($wechatInfo)) {
                throw new Exception("缺少微信配置信息...");
            }

            //令牌
            $token = md5('merchant_id='.$wechatInfo['payment_wechat_merchant_id'].'&authentication_code='.$wechatInfo['payment_wechat_authentication_code'].'&merchant_trade_no='.$orderId);

            $params = array(
                "merchant_id" => $wechatInfo['payment_wechat_merchant_id'],
                "authentication_code" => $wechatInfo['payment_wechat_authentication_code'],
                "merchant_trade_no" => $orderId,
                "token" => $token
            );

            $curl = new Curl();
            $curl->url('https://api.superpayglobal.com/payment/bridge/get_payment_result');
            $curl->data($params);
            $result = $curl->get();

            if (isset($this->request->server['HTTP_ORIGIN'])) {
                $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
                $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
                $this->response->addHeader('Access-Control-Max-Age: 1000');
                $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($result));
            
        } catch (Exception $e) {
            echo 'error message：'.$e->getMessage();
        }
    }
}
