<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

include(dirname(__FILE__) . '/class/ktconfig.php');
include_once(dirname(__FILE__) . '/class/KTVPos.php');

class Ktpay extends PaymentModule {
    public function __construct()
    {
        $this->name = 'ktpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.7.0';
        $this->author = 'Architecht';
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('KuveytTürk Sanal Pos');
        $this->description = $this->l('KuveytTürk Sanal Pos ile ödeme');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => '1.7.99.99');
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('displayAdminOrder') || !$this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn')) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            Configuration::deleteByName('ktpay_merchant_id');
            Configuration::deleteByName('ktpay_customer_id');
            Configuration::deleteByName('ktpay_environment');
            Configuration::deleteByName('ktpay_api_username');
            Configuration::deleteByName('ktpay_api_password');
            Configuration::deleteByName('ktpay_td_mode');
            Configuration::deleteByName('ktpay_td_overamount');
            Configuration::deleteByName('ktpay_installment_mode');
            Configuration::deleteByName('ktpay_rates');
			Configuration::deleteByName('ktpay_is_check_installment_options');
            Configuration::deleteByName('ktpay_installment_array');
			
            foreach ($this->hooks as $hook) {
                if (!$this->unregisterHook($hook))
                    return false;
            }
        }
        return true;
    }

    protected function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('ktpay_merchant_id') || !Tools::getValue('ktpay_customer_id') || !Tools::getValue('ktpay_api_username') || !Tools::getValue('ktpay_api_password')) {
                $this->_postErrors[] = $this->l('KuveytTürk Hesap bilgileri girilmelidir.');
            }
        }
    }

    protected function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('ktpay_merchant_id', Tools::getValue('ktpay_merchant_id'));
            Configuration::updateValue('ktpay_customer_id', Tools::getValue('ktpay_customer_id'));
            Configuration::updateValue('ktpay_environment', Tools::getValue('ktpay_environment'));
            Configuration::updateValue('ktpay_api_username', Tools::getValue('ktpay_api_username'));
            Configuration::updateValue('ktpay_api_password', Tools::getValue('ktpay_api_password'));
            Configuration::updateValue('ktpay_td_mode', Tools::getValue('ktpay_td_mode'));
            Configuration::updateValue('ktpay_td_overamount', Tools::getValue('ktpay_td_overamount'));
            Configuration::updateValue('ktpay_installment_mode', Tools::getValue('ktpay_installment_mode'));
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    public function getContent(){
        if (Tools::isSubmit('btnSubmit') == true) {
            $this->_postValidation();
            if (!count($this->_postErrors))
                $this->_postProcess();
            else
                foreach ($this->_postErrors as $err)
                    $this->_html .= $this->displayError($err);
        }

        if (((bool) Tools::isSubmit('checkInstallmentDefinition')) == true) {
            $result=$this->checkInstallmentDefinition();
            if($result['success']==false)
            {
                $this->_html .= $this->displayError($result['message']);
            }
        }

        if (((bool) Tools::isSubmit('saveInstallmentSettings')) == true) {
            $ktpay_rates=Tools::getValue('ktpay_rates');
            if($ktpay_rates)
            {
                Configuration::updateValue('ktpay_rates', serialize($ktpay_rates));
            }
        }

        $is_check_installment_options = Configuration::get('ktpay_is_check_installment_options');
        $merchant_id = Configuration::get('ktpay_merchant_id');

        if(($is_check_installment_options==null || $is_check_installment_options==false) && $merchant_id!=null)
        {
            $result=$this->checkInstallmentDefinition();

            if($result['success']==true)
            {
                Configuration::updateValue('ktpay_is_check_installment_options', true);
            }
        }
        
        $rates=unserialize(Configuration::get('ktpay_rates'));
        $installment_array=KTPayConfig::get_rates_array(Configuration::get('ktpay_installment_array'));
        $installment_settings='';

        
        if($rates!=null && $installment_array!=null)
        {
            $installment_settings=KTPayConfig::create_rates_update_form($rates, $installment_array);
        }

        $this->context->smarty->assign(array(
            'module_dir' => $this->_path,
            'err' => $this->displayError($err),
            'admin_settings' => $this->renderForm(),
            'installment_settings' => $installment_settings,
        ));
        return $this->context->smarty->fetch($this->local_path . 'views/templates/hook/admin_form.tpl');
    }

    public function hookPaymentOptions($params)
    {
        try {
            $currency_query = 'SELECT * FROM `' . _DB_PREFIX_ . 'currency` WHERE `id_currency`= "' . $params['cookie']->id_currency . '"';
            $currency = Db::getInstance()->ExecuteS($currency_query);
            $currency_iso = $currency[0]['iso_code'];
            $this->smarty->assign('currency_iso', $currency_iso);
            $ktpay_installment_rates = unserialize(Configuration::get('ktpay_rates'));
            $installment_count_array = KTPayConfig::get_rates_array(Configuration::get('ktpay_installment_array'));
            $installment_mode = Configuration::get('ktpay_installment_mode');
            $environment = Configuration::get('ktpay_environment');

            $total_cart = (float) number_format($params['cart']->getOrderTotal(true, Cart::BOTH), 2, '.', '');
            $has_installment =  'off';
            $installment_count = 1;
            $ktpay=new KTVPos();
            
            $check_onus_card_url = $ktpay->check_onus_card_test_url;
            if($environment == 'PROD') {
                $check_onus_card_url = $ktpay->check_onus_card_prod_url;
            }

            $formurl = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'index.php?module_action=result&fc=module&module=ktpay&controller=result';
            $this->smarty->assign('action', $formurl);
            $this->smarty->assign('module_dir', $this->_path);

            if($installment_mode=='AÇIK' && $ktpay_installment_rates!=null && $installment_count_array!=null && count($installment_count_array)>1)
            {
                $rates = KTPayConfig::calculate_price_with_installments($total_cart, $ktpay_installment_rates, $installment_count_array);
                $has_installment =  'on';
                $installment_count = count($installment_count_array);
                $this->smarty->assign('rates', $rates);
            }
            $this->smarty->assign('installment_count', $installment_count);
            $this->smarty->assign('has_installment', $has_installment);
            $this->smarty->assign('installment_mode', $installment_mode=='AÇIK' ? 'on' : 'false');
            $this->smarty->assign('check_onus_card_url', $check_onus_card_url);

            $newOption = new PaymentOption();
            $newOption->setCallToActionText($this->trans('Kredi Kartı İle Öde (KuveytTürk SanalPos)', array(), 'Modules.KTPay'));

            $newOption->setModuleName('ktpay', array(), 'Modules.KTPay')
                ->setAdditionalInformation($this->fetch('module:ktpay/views/templates/hook/payment_form.tpl'));
            $payment_options = [
                $newOption,
            ];

            return $payment_options;

        } catch (\Throwable $th) {
            echo 'Hata: ', $th->getMessage(), "\n";
        }
    }

    public function renderForm()
    {
        $environmentArray = array(
            array(
                'id' => $this->l('TEST')
            ),
            array(
                'id' => $this->l('PROD')
            )
        );

        $onOffArray = array(
            array(
                'id' => $this->l('KAPALI')
            ),
            array(
                'id' => $this->l('AÇIK')
            )
        );

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'label' => $this->l('İşlem Ortamı'),
                        'type' => 'select',
                        'name' => 'ktpay_environment',
                        'options' => array(
                            'id' => 'id',
                            'name' => 'id',
                            'query' => $environmentArray
                        ),
                    ),
                    array(
                        'label' => $this->l('Üye İşyeri Numarası'),
                        'type' => 'text',
                        'name' => 'ktpay_merchant_id',
                        'required' => true
                    ),
                    array(
                        'label' => $this->l('Müşteri Numarası'),
                        'type' => 'text',
                        'name' => 'ktpay_customer_id',
                        'required' => true
                    ),
                    array(
                        'label' => $this->l('Kullanıcı Adı'),
                        'type' => 'text',
                        'name' => 'ktpay_api_username',
                        'required' => true
                    ),
                    array(
                        'label' => $this->l('Şifre'),
                        'type' => 'text',
                        'name' => 'ktpay_api_password',
                        'required' => true
                    ),
                    array(
                        'label' => $this->l('3D Onayı Gereksin mi?'),
                        'type' => 'select',
                        'name' => 'ktpay_td_mode',
                        'options' => array(
                            'id' => 'id',
                            'name' => 'id',
                            'query' => $onOffArray
                        ),
                    ),
                    array(
                        'label' => $this->l('3d Secure Geçilecek Tutar'),
                        'type' => 'text',
                        'name' => 'ktpay_td_overamount',
                        'description' => $this->l('Belirli bir tutardan sonra 3D onayına düşsün isteniyorsa tutar girin'),
    
                    ),
                    array(
                        'label' => $this->l('Taksit Seçenekleri'),
                        'type' => 'select',
                        'name' => 'ktpay_installment_mode',
                        'description' => $this->l('Sadece Onus kartlar için taksit yapılabilmektedir'),
                        'options' => array(
                            'id' => 'id',
                            'name' => 'id',
                            'query' => $onOffArray
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            )
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'ktpay_merchant_id' => Tools::getValue('ktpay_merchant_id', Configuration::get('ktpay_merchant_id')),
            'ktpay_customer_id' => Tools::getValue('ktpay_customer_id', Configuration::get('ktpay_customer_id')),
            'ktpay_api_username' => Tools::getValue('ktpay_api_username', Configuration::get('ktpay_api_username')),
            'ktpay_api_password' => Tools::getValue('ktpay_api_password', Configuration::get('ktpay_api_password')),
            'ktpay_td_mode' => Tools::getValue('ktpay_td_mode', Configuration::get('ktpay_td_mode')),
            'ktpay_td_overamount' => Tools::getValue('ktpay_td_overamount', Configuration::get('ktpay_td_overamount')),
            'ktpay_installment_mode' => Tools::getValue('ktpay_installment_mode', Configuration::get('ktpay_installment_mode')),
            'ktpay_environment' => Tools::getValue('ktpay_environment', Configuration::get('ktpay_environment')),
            //'ktpay_enabled' => Tools::getValue('ktpay_enabled', Configuration::get('ktpay_enabled')),
            'ktpay_rates' => Tools::getValue('ktpay_rates', Configuration::get('ktpay_rates')),
            'ktpay_installment_array' => Tools::getValue('ktpay_installment_array', Configuration::get('ktpay_installment_array')),
        );
    }

    protected function checkInstallmentDefinition(){
        $merchant_id = Tools::getValue('ktpay_merchant_id');
        $environment = Tools::getValue('ktpay_environment');

        if($merchant_id==null || strlen(trim($merchant_id)) === 0){
            return array(
                'success'=>false,
                'message'=>'Üye işyeri numarası girilmelidir'
            );
        }
               
        $ktpay=new KTVPos();        
        $installmentResult = $ktpay->check_installment_definition($environment, $merchant_id);

        if($installmentResult['success'])
        {        
            Configuration::updateValue('ktpay_rates', serialize(json_encode(KTPayConfig::init_rates($installmentResult['data']))));
            Configuration::updateValue('ktpay_installment_array', json_encode($installmentResult['data']));

            return array(
                'success'=>true,                
            );
        }
        else
        {
			$data = [1];
            Configuration::updateValue('ktpay_rates', serialize(json_encode(KTPayConfig::init_rates($data))));
            Configuration::updateValue('ktpay_installment_array', json_encode($data));
			
            return array(
                'success'=>false,
                'message'=>'Taksit tanımı kontrol edilemedi'
            );
        }

    }
}