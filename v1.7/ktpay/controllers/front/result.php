<?php
use PrestaShop\PrestaShop\Adapter\Entity\Order;

class KTPayResultModuleFrontController extends ModuleFrontController {
    public function initContent() {
        parent::initContent();

        $module_action = Tools::getValue('module_action');
        $action_list = array('result' => 'payment', 'payment' => 'callback');

        if (isset($action_list[$module_action])) {
            $this->{$action_list[$module_action]}();
        }
    }

    public function payment(){
        $context = Context::getContext();
        $cart = $context->cart;
        try {
            $card_holder_name = Tools::getValue('card-holder');
            $card_number = Tools::getValue('card-number');
            $expiry = Tools::getValue('card-expire-date');
            $card_cvv = Tools::getValue('card-cvv');
            $installment = isset($_POST['installment']) ? $_POST['installment'] : 1;

            $expiry = explode("/", $expiry);
            $expiryMM = $expiry[0];
            $expiryYY = $expiry[1];
            $card_expire_month = KTPayConfig::replaceSpace($expiryMM);
            $card_expire_year = KTPayConfig::replaceSpace($expiryYY);
            $card_number = KTPayConfig::replaceSpace($card_number);

            $merchant_id = Configuration::get('ktpay_merchant_id');
            $customer_id =  Configuration::get('ktpay_customer_id');
            $api_username =  Configuration::get('ktpay_api_username');
            $api_password = Configuration::get('ktpay_api_password');
            $td_mode =  Configuration::get('ktpay_td_mode');
            $td_overamount = (float) Configuration::get('ktpay_td_overamount');
            $environment = Configuration::get('ktpay_environment');
            $orderId = 'PS-' . $this->context->cookie->id_cart;

            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

            $ip=Tools::getRemoteAddr();
            $currency = new Currency((int) $cart->id_currency);
            $iso_code = $currency->iso_code ? $currency->iso_code : 'TRY';
            $currency = KTPayConfig::get_currency_code($iso_code);
            $is3dTransaction=($td_mode == 'AÇIK') || ($td_overamount!=null && $total>$td_overamount);

            $success_url = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'index.php?module_action=payment&fc=module&module=ktpay&controller=result&action=success';
            $fail_url = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'index.php?module_action=payment&fc=module&module=ktpay&controller=result&action=error';
            $customer = new Customer($cart->id_customer);
                
            $ktpay = new KTVPos();
            $params = array(
                'environment'=> $environment,
                'merchant_id' => $merchant_id,
                'customer_id' => $customer_id,
                'api_user_name' => $api_username,
                'api_user_password' => $api_password,
                'success_url' => $is3dTransaction ? $success_url : '',
                'fail_url' => $is3dTransaction ? $fail_url : '',
                'merchant_order_id' =>$orderId,
                'amount' => $total,
                'installment_count' => $installment,
                'currency_code' => $currency,
                'customer_ip' => $ip,
                'customer_mail' => $customer->email,
                'card_holder_name' => $card_holder_name,
                'card_number' => $card_number,
                'card_expire_month' => $card_expire_month,
                'card_expire_year' => $card_expire_year,
                'card_cvv' => $card_cvv
            );
            $ktpay->set_payment_params($params);
            
            if($is3dTransaction){
                //3d akışı
                $parameters = array(
                    'data' => $ktpay->init_3d_request_body(),
                    'url' => $environment == 'TEST' ? $ktpay->td_payment_test_url : $ktpay->td_payment_prod_url,
                    'isJson' => $is3dTransaction,
                );
                $response=$ktpay->send_request($parameters);
                try {
                    if($response['success'])
                    {
                        $kt_error_response='<form name="responseForm"';
                        if(substr($response['data'],0,1)=='{')
                        {                           
                            $jsonResponse=json_decode($response['data']);
                            if(isset($jsonResponse->ResponseCode) && $jsonResponse->Success==false)
                            {
                                $message = (string)$jsonResponse->ResponseMessage;
                                $message = !empty($message) ? $message : "Invalid Request";     
                                                       
                                $this->context->smarty->assign(array(
                                'error' => $message
                                ));
                                $this->setTemplate('module:ktpay/views/templates/front/order_result.tpl');
                            }
                        } 
                        else if(substr($response['data'],0,strlen($kt_error_response))==$kt_error_response)
                        {
                            $document = new DOMDocument();
                            $document->loadHTML(mb_convert_encoding($response['data'], 'HTML-ENTITIES', "UTF-8"));
                            $xp = new DOMXpath($document);
                            $is_success =  strtolower($xp->query('//input[@name="Success"]')[0]->getAttribute('value')) == 'true';
                            $response_code= (string) $xp->query('//input[@name="ResponseCode"]')[0]->getAttribute('value');
                            $response_message= (string) $xp->query('//input[@name="ResponseMessage"]')[0]->getAttribute('value');
                            if(!$is_success)
                            {
                                $message = $response_code. " ". $response_message;
                                $message = !empty($message) ? $message : "Invalid Request";     
                        
                                $this->context->smarty->assign(array(
                                'error' => $message,
                                ));
                                $this->setTemplate('module:ktpay/views/templates/front/order_result.tpl');
                            }
                        }
                        else
                        {
                            echo $response['data'];
                        }
                    }
                    else
                    {
                        $message = (string)$response['message'];
                        $message = !empty($message) ? $message : "Invalid Request";     
                        
                        $this->context->smarty->assign(array(
                        'error' => $message,
                        ));
                        $this->setTemplate('module:ktpay/views/templates/front/order_result.tpl');
                    }
                } catch (\Throwable $th) {
                    $message = (string)$th->getMessage();
                    $message = !empty($message) ? $message : "Invalid Request";     
                        
                    $this->context->smarty->assign(array(
                    'error' => $message,
                    ));
                    $this->setTemplate('module:ktpay/views/templates/front/order_result.tpl');
                }               
            }
            else
            {
                //non3d akışı     
                $parameters = array(
                    'data' => $ktpay->init_non3d_request_body(),
                    'url' => $environment == 'TEST' ? $ktpay->ntd_payment_test_url : $ktpay->ntd_payment_prod_url,
                    'isJson' => $is3dTransaction,
                    'returnType' => 'xml',
                );
                $response = $ktpay->send_request($parameters);
                if($response['success'])
                {
                    $VPosTransactionResponseContract=new SimpleXMLElement($response['data']->asXML());
    
                    if($VPosTransactionResponseContract->ResponseCode=="00")
                    {
                        $this->module->validateOrder((int) $cart->id, Configuration::get('PS_OS_PAYMENT'), $total,$this->module->displayName, null, $total, (int) $currency->id, false, $cart->secure_key);                        
                        Configuration::updateValue('PS_INVOICE', $this->context->cookie->id_cart);

                        Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $this->context->cookie->id_cart . '&id_module=' . (int) $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
                    }
                    else
                    {
                        $message = (string)$VPosTransactionResponseContract->ResponseMessage;
                        $message = !empty($message) ? $message : "Invalid Request";     
                
                        $this->context->smarty->assign(array(
                        'error' => $message
                        ));
                        $this->setTemplate('module:ktpay/views/templates/front/order_result.tpl');
                    }
                }
                else
                {
                    $message = (string)$response['message'];
                    $this->context->smarty->assign(array(
                        'error' => $message
                        ));
                    $this->setTemplate('module:ktpay/views/templates/front/order_result.tpl');
                }
            }
        } catch (\Throwable $th) {
            $error_msg = $th->getMessage();

            $this->context->smarty->assign(array(
                'error' => $error_msg
            ));
            $this->setTemplate('module:ktpay/views/templates/front/order_result.tpl');
        }
    }

    public function callback(){

        $postParams = $_POST;
        $context = Context::getContext();
        $cart = $context->cart;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $error_msg = '';

        $currency = new Currency((int) ($cart->id_currency));
        $iso_code = $currency->iso_code ? $currency->iso_code : 'TRY';
        $currency= KTPayConfig::get_currency_code($iso_code);

        $customer = new Customer($cart->id_customer);
        $merchant_id = Configuration::get('ktpay_merchant_id');
        $customer_id =  Configuration::get('ktpay_customer_id');
        $api_username =  Configuration::get('ktpay_api_username');
        $api_password = Configuration::get('ktpay_api_password');

        $bodyParams=array(
            'md'=>(isset($postParams['Result_MD'])) ? $postParams['Result_MD'] : "",
            'merchant_id'=>$merchant_id,
            'customer_id' =>$customer_id,
            'amount' =>$total,
            'order_id' =>isset( $postParams['Result_OrderId']) ? $postParams['Result_OrderId'] : "",
            'merchant_order_id' =>isset( $postParams['Result_MerchantOrderId']) ? $postParams['Result_MerchantOrderId'] : "",
            'api_user_name' =>$api_username,
            'api_user_password' =>$api_password,
            'response_message' => isset( $postParams['ResponseMessage']) ? $postParams['ResponseMessage'] : "",
        );

        $action = isset($_GET['action']) ? $_GET['action'] : "fail";
        $ktpay=new KTVPos();
        $response=$ktpay->callback($action, $bodyParams);
        try {
            if($response['status']=="success")
            {  
                $Ktpay = new Ktpay();
                $Ktpay->validateOrder((int) $cart->id, Configuration::get('PS_OS_PAYMENT'), $total, $Ktpay->displayName, null, $total, (int) $currency->id, false, $cart->secure_key);
                Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . (int) $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
            }
            else
            {
                $message = (string)$response['message'];
                $message = !empty($message) ? $message : 'İşlem sırasında beklenmedik bir hata oluştu!';
                $error_msg = $message;
            }
        } catch (\Throwable $th) {
            $message = (string)$th->getMessage();
            $message = !empty($message) ? $message : 'İşlem sırasında beklenmedik bir hata oluştu';
            $error_msg = $message;
        }

        $this->context->smarty->assign(array(
            'error' => $error_msg,
            'order_id' => $cart->id
        ));

        $this->setTemplate('module:ktpay/views/templates/front/order_result.tpl');
    }
}