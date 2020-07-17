<?php 
require_once(DIR_SYSTEM . 'helper/http.php');
class ControllerApiSupay extends Controller {
    public function aliCallback() {
        try {
            //只允许GET方式
            if (!$this->request->server['REQUEST_METHOD'] == 'GET') {
                throw new Exception("Method Can Not Support");
            }

            //获取参数列表
            $params = $this->request->get;
            //验证参数完整性
            if (empty($params['notice_id']) || empty($params['merchant_trade_no']) || empty($params['token'])) {
                throw new Exception("Lack of necessary parameters");
            }

            //支付宝配置信息
            $this->load->model('setting/setting');
            $alipayInfo = $this->model_setting_setting->getSetting('payment_ali');
            if (empty($alipayInfo)) {
                throw new Exception("No payment configuration information was obtained");
            }

            //加密规则排序
            $verificationToken = md5('notice_id='.$params['notice_id'].'&merchant_trade_no='.$params['merchant_trade_no'].'&authentication_code='.$alipayInfo['payment_ali_authentication_code']);

            //验证合法性
            if ($verificationToken != trim($params['token'])) {
                throw new Exception("Illegal request");
            }

            //回调通知验证
            $curl = new Curl();
            $curl->url('https://api.superpayglobal.com/payment/bridge/notification_verification');
            $curl->data(array(
                'merchant_trade_no' => $params['merchant_trade_no'],
                'notice_id' => $params['notice_id'] 
            ));
            $result = $curl->get();

            if ($result['status'] && $result['data'] == 'SUCCESS') {
                //支付成功，此时应该更新订单状态为 processing
                //To Do...
                $this->load->model('checkout/order');
                $this->model_checkout_order->addOrderHistory($params['merchant_trade_no'], $this->config->get('payment_ali_order_status_id'));
            }
        } catch (Exception $e) {
            echo 'error message：'.$e->getMessage();
        }
    }

    public function wechatCallback() {
        try {
            //只允许GET方式
            if (!$this->request->server['REQUEST_METHOD'] == 'GET') {
                throw new Exception("Method Can Not Support");
            }

            //获取参数列表
            $params = $this->request->get;
            //验证参数完整性
            if (empty($params['notice_id']) || empty($params['merchant_trade_no']) || empty($params['token'])) {
                throw new Exception("Lack of necessary parameters");
            }

            //微信配置信息
            $this->load->model('setting/setting');
            $wechatInfo = $this->model_setting_setting->getSetting('payment_wechat');
            if (empty($wechatInfo)) {
                throw new Exception("No payment configuration information was obtained");
            }

            //加密规则排序
            $verificationToken = md5('notice_id='.$params['notice_id'].'&merchant_trade_no='.$params['merchant_trade_no'].'&authentication_code='.$wechatInfo['payment_wechat_authentication_code']);

            //验证合法性
            if ($verificationToken != trim($params['token'])) {
                throw new Exception("Illegal request");
            }

            //回调通知验证
            $curl = new Curl();
            $curl->url('https://api.superpayglobal.com/payment/bridge/notification_verification');
            $curl->data(array(
                'merchant_trade_no' => $params['merchant_trade_no'],
                'notice_id' => $params['notice_id'] 
            ));
            $result = $curl->get();
            // var_dump($result);exit;
            if ($result['status'] && $result['data'] == 'SUCCESS') {
                //支付成功，此时应该更新订单状态为 processing
                //To Do...
                $this->load->model('checkout/order');
                $this->model_checkout_order->addOrderHistory($params['merchant_trade_no'], $this->config->get('payment_wechat_order_status_id'));
                // $this->model_checkout_order->addOrderHistory($params['merchant_trade_no'], 2);
            } else {
                throw new Exception("http error");
            }
        } catch (Exception $e) {
            echo 'error message：'.$e->getMessage();
        }
    }
}
?>