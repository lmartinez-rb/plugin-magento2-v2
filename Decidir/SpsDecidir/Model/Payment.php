<?php

namespace Decidir\SpsDecidir\Model;

use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Decidir\SpsDecidir\Model\Webservice;
use Magento\Customer\Model\Session as CustomerSession;
use Decidir\SpsDecidir\Model\DecidirTokenFactory;
use Decidir\AdminPlanesCuotas\Model\CuotaFactory;
use Decidir\AdminPlanesCuotas\Model\PlanPagoFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Sales\Model\Order;


/**
 * Class Payment
 *
 * @description Modelo representativo del metodo de pago SpsDecidir
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'decidir_spsdecidir';
    const MODULE_NAME = 'Decidir_SpsDecidir';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * @var bool
     */
    protected $_canOrder = true;

    /**
     * @var Transaction\BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isInitializeNeeded = false;

    /**
     * @var \Decidir\SpsDecidir\Helper\Data
     */
    protected $_spsHelper;

    /**
     * @var \Decidir\SpsDecidir\Helper\EstadoTransaccion
     */
    protected $_spsTransaccionesHelper;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var Webservice
     */
    protected $_webservice;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var DecidirTokenFactory
     */
    protected $_decidirTokenFactory;

    /**
     * @var CuotaFactory
     */
    protected   $_cuotaFactory;

    /**
     * @var BancoFactory
     */
    protected   $_bancoFactory;

    /**
     * @var PlanPagoFactory
     */
    protected   $_planPagoFactory;

    /**
     * @var PaymentHelper
     */
    protected $_paymentHelper;

    protected $_remote;
    protected $scopeConfig;
    /**
     * Payment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Sales\Api\TransactionRepositoryInterface transactionRepository
     * @param \Decidir\SpsDecidir\Helper\Data $spsHelper
     * @param \Decidir\SpsDecidir\Helper\EstadoTransaccion $spsTransaccionesHelper
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Decidir\SpsDecidir\Model\Webservice
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Decidir\SpsDecidir\Model\DecidirTokenFactory $decidirToken
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param \Decidir\AdminPlanesCuotas\Model\CuotaFactory
     * @param \Decidir\AdminPlanesCuotas\Model\PlanPagoFactory
     * @param PaymentHelper $paymentHelper     
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remote
     * @param array $data
     */

    
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,        
        \Decidir\SpsDecidir\Helper\Data $spsHelper,
        \Decidir\SpsDecidir\Helper\EstadoTransaccion $spsTransaccionesHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Decidir\SpsDecidir\Model\Webservice $webservice,
        \Magento\Customer\Model\Session $customerSession,
        \Decidir\SpsDecidir\Model\DecidirTokenFactory $decidirToken,
        \Decidir\AdminPlanesCuotas\Model\CuotaFactory $cuotaFactory,
        \Decidir\AdminPlanesCuotas\Model\PlanPagoFactory $planPagoFactory,
        PaymentHelper $paymentHelper,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remote,

        /**
         * Cuando se agregan dependencias en el metodo de pago, siempre tienen que estar antes del AbstractResource y
         * AbstractDb, ya que sino tira un fatal error...
         */
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $this->_checkoutSession         = $checkoutSession;
        $this->transactionBuilder       = $transactionBuilder;
        $this->transactionRepository    = $transactionRepository;
        $this->_spsHelper               = $spsHelper;
        $this->_spsTransaccionesHelper  = $spsTransaccionesHelper;
        $this->_messageManager          = $messageManager;
        $this->_webservice          = $webservice;
        $this->_customerSession          = $customerSession;
        $this->_decidirTokenFactory = $decidirToken;
        $this->_cuotaFactory        = $cuotaFactory;
        $this->_planPagoFactory        = $planPagoFactory;
        $this->_moduleList             = $moduleList;
        $this->_paymentHelper = $paymentHelper;
        $this->_remote        = $remote;
        
        
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->scopeConfig = $scopeConfig;
       
        

    }

    /**
     * @description Captura los datos del metodo de la orden, luego de ser finalizada desde el checkout y verifica
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface|Payment $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        
        $order                  = $payment->getOrder();
        
        $helper                 = $this->_spsHelper;
        $spsTransaccionesHelper = $this->_spsTransaccionesHelper;
        $customerSession        = $this->_customerSession;

        $infoOperacionSps = $this->_spsHelper->getInfoTransaccionSPS();
        if(!$infoOperacionSps){
            return $this;
        }

        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR2 - MODEL PAYMENT - InfoOperacion: '.print_r($infoOperacionSps, true));        

        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR2 - MODEL PAYMENT - Version plugin: '.$this->_moduleList
            ->getOne(self::MODULE_NAME)['setup_version']);        
        /*\Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug( 'infoOperacionSps payment model : '. print_r($infoOperacionSps, true) );
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug( 'infoOperacionSps payment model : '. $order->getIncrementId() );
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug( 'infoOperacionSps payment model : '. $order->getCustomerId() );
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug( 'getCustomer()->getId : '. $order->getCustomerId() );*/



        $cuotaCollection = $this->_cuotaFactory->create()
            ->getCollection()
            ->addFieldToFilter('plan_pago_id',['eq'=>$infoOperacionSps['planPago']])
            ->addFieldToFilter('cuota',['eq'=>$infoOperacionSps['cuota']]);
        $cuotaData=$cuotaCollection->getData();
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug( 'getCcuotaData: '. print_r($cuotaData, true) );
        if( empty($cuotaData[0]['cuota_gateway']) ){
            $cantidad_cuotas = $cuotaData[0]['cuota'];
        }else{
            $cantidad_cuotas = $cuotaData[0]['cuota_gateway'];
        }



        $merchant="";
        $planPagoDataCollection = $this->_planPagoFactory->create()
            ->getCollection()
            ->addFieldToFilter('plan_pago_id',['eq'=>$infoOperacionSps['planPago']]);
        $planPagoData=$planPagoDataCollection->getData();
        if( !empty($planPagoData[0]['merchant']) ){
            $merchant = $planPagoData[0]['merchant'];
        }


        if(!$order->getCustomerId()){ //Usuario guest
            $customerId='guest';
        }else{
            $customerId=$order->getCustomerId();
        }



            $ws = $this->_webservice;
            $data = array(
                "site_transaction_id" => $order->getIncrementId(),
                "token" => $infoOperacionSps['tokenPago'],
                "payment_method_id" => (int)$infoOperacionSps['tarjeta_id'],
                "amount" => number_format($amount, 2, ".", ""),
                "bin" => $infoOperacionSps['bin'],
                "currency" => "ARS",
                "installments" => (int)$cantidad_cuotas,
                "description" => "Orden ".$order->getIncrementId(),
                "payment_type" => "single",
                "sub_payments" => array(),
                "fraud_detection" => array()
            );

            if($order->getCustomerId()){ //No es Usuario guest
                $data["customer"] = array(
                    "id" => "$customerId", 
                    "email" => $customerSession->getCustomer()->getEmail(),
                    "ip_address" => $this->_remote->getRemoteAddress(),

                );
            }

            if(!empty($planPagoData[0]['merchant'])){
                $data["site_id"]=$planPagoData[0]['merchant'];
            }


        if($this->_scopeConfig->getValue('payment/decidir_spsdecidir/cybersource')==1){
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR - PAYMENT MODEL - Cybersource habilitado');     

            if($this->_scopeConfig->getValue('payment/decidir_spsdecidir/cybersourcevertical')=='retail'):
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR - PAYMENT MODEL - Cybersource vertical: RETAIL');     
                                       
                $csData=$this->_spsHelper->getDataControlFraudeCommon($order, $this->_customerSession->getCustomer());
                $csRetailData=$this->_spsHelper->getDataControlFraudeRetail($order, $this->_customerSession->getCustomer(), $csData);
                $csProducts=$this->_spsHelper->getMultipleProductsInfo($order);

                \Magento\Framework\App\ObjectManager::getInstance()
                 ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Cybersource data enviada: '.print_r($csRetailData, true) );
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Cybersource data products enviada: '.print_r($csRetailData, true) );
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - DEBUG SETEANDO RETAIL: array_merge($csData, $csRetailData) = '.print_r(array_merge($csData, $csRetailData), true) );
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - DEBUG SETEANDO RETAIL: csProducts = '.print_r(array_merge($csData, $csProducts), true) );
                


                $wsDataRetail = $ws->cybersourceRetail(array_merge($csData, $csRetailData), $csProducts);   
                $ws->setCybersource( $wsDataRetail );     

                try{       
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Pago realizado con Cybersource - Data a enviar: '.print_r($data, true) );                    
                    $respuesta = $ws->pagarCs($data);

                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Pago realizado con Cybersource - Respuesta recibida: '.print_r($respuesta, true) );                        
                }catch(\Exception $e){
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Error en Pago realizado con Cybersource: '.$e );     
                }
            elseif($this->_scopeConfig->getValue('payment/decidir_spsdecidir/cybersourcevertical')=='ticketing'):
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR - PAYMENT MODEL - Cybersource vertical: Ticketing');     
                                       
                $csData=$this->_spsHelper->getDataControlFraudeCommon($order, $this->_customerSession->getCustomer());
                $csTicketingData=$this->_spsHelper->getDataControlFraudeTicketing($order);
                $csAll=array_merge($csData, $csTicketingData);
                $csProducts=$this->_spsHelper->getMultipleProductsInfo($order);                    

                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - DEBUG: '.print_r($csAll, true) );  

                $wsDataTicketing = $ws->cybersourceTicketing($csTicketingData, $csProducts);                           
                $ws->setCybersource( array_merge($csTicketingData, $csData) );      

                try{      
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Pago realizado con Cybersource - Data a enviar: '.print_r($data, true) );                       
                    $respuesta = $ws->pagarCs($data);
                
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Pago realizado con Cybersource - Respuesta recibida: '.print_r($respuesta, true) );                        
                }catch(\Exception $e){
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Error en Pago realizado con Cybersource: '.$e );     
                }

            elseif($this->_scopeConfig->getValue('payment/decidir_spsdecidir/cybersourcevertical')=='digitalgoods'):
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR - PAYMENT MODEL - Cybersource vertical: Digitalgoods');     
                                       
                $csData=$this->_spsHelper->getDataControlFraudeCommon($order, $this->_customerSession->getCustomer());
                $csDigitalgoodsData=$this->_spsHelper->getDataControlFraudeDigitalgoods($order);
                $csAll=array_merge($csData, $csDigitalgoodsData);
                $csProducts=$this->_spsHelper->getMultipleProductsInfo($order);                    

                $wsDataDigitalgoods = $ws->cybersourceTicketing($csDigitalgoodsData, $csProducts);                           
                $ws->setCybersource( array_merge($csDigitalgoodsData, $csData) );

                try{
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Pago realizado con Cybersource - Data a enviar: '.print_r($data, true) );                                   
                    $respuesta = $ws->pagarCs($data);
                

                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Pago realizado con Cybersource - Respuesta recibida: '.print_r($respuesta, true) );                        
                }catch(\Exception $e){
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Error en Pago realizado con Cybersource: '.$e );     
                }

            elseif($this->_scopeConfig->getValue('payment/decidir_spsdecidir/cybersourcevertical')=='travel'):      \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR - PAYMENT MODEL - Cybersource vertical: Travel');     
                                       
                $csData=$this->_spsHelper->getDataControlFraudeCommon($order, $this->_customerSession->getCustomer(), false);
                $csTravelData=$this->_spsHelper->getDataControlTravel($order);
                $csTravelPassengersData=$this->_spsHelper->getDataControlTravelPassengers($order);
                $csAll=array_merge($csData, $csTravelData);

                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Cybersource - Travel Data a enviar: '.print_r($csAll, true) );      

                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Cybersource - Travel Data a enviar pasajeros: '.print_r($csTravelPassengersData, true) );      


                try{
                    $wsDataTravel = $ws->cybersourceTravel($csAll, $csTravelPassengersData);                       
                    $ws->setCybersource( $wsDataTravel );
                }catch(\Exception $e){
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Error en seteo de Cybersource: '.$e );     
                }



                try{
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Pago realizado con Cybersource - Data a enviar: '.print_r($csData, true) );                                   
                    $respuesta = $ws->pagarCs($data);
                

                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Pago realizado con Cybersource - Respuesta recibida: '.print_r($respuesta, true) );                        
                }catch(\Exception $e){
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Error en Pago realizado con Cybersource: '.$e );     
                }


            elseif($this->_scopeConfig->getValue('payment/decidir_spsdecidir/cybersourcevertical')=='services'):
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR - PAYMENT MODEL - Cybersource vertical: Services');     
                     
                $csData=$this->_spsHelper->getDataControlFraudeCommon($order, $this->_customerSession->getCustomer(), false);
                $csServicesData=$this->_spsHelper->getDataControlFraudeServices($order);
                $csAll=array_merge($csData, $csServicesData);
                $csProducts=$this->_spsHelper->getMultipleProductsInfo($order);                    

                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Cybersource vertical Service - Data Service: '.print_r($csServicesData, true) );    

                try{
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Seteo Cybersource - Vertical Service correcto '); 

                    //$wsDataRetail = $ws->cybersourceRetail(array_merge($csData, $csRetailData), $csProducts);   
                    $wsDataServices = $ws->cybersourceServices(array_merge($csData, $csServicesData), $csProducts);
                    $ws->setCybersource( $wsDataServices );                           
                    //$ws->setCybersource( array_merge($csServicesData, $csData) );

                }catch(\Exception $e){
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Seteo Cybersource - Vertical Service. Error: '.$e );     
                }

                try{
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Pago realizado con Cybersource - Data a enviar: '.print_r($data, true) );                                   
                    $respuesta = $ws->pagarCs($data);
                
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Pago realizado con Cybersource - Respuesta recibida: '.print_r($respuesta, true) );                        
                }catch(\Exception $e){
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Error en Pago realizado con Cybersource: '.$e );     
                }

            endif;
     
        }else{
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug('Cybersource deshabilitado');  
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Paga sin CS - Data a enviar: '. print_r($data, true) );               

            try{
                $respuesta = $ws->pagar($data);     
                
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('respuesta pago Sin CS: '.print_r($respuesta, true) );                   
            }catch(\Exception $e){
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR - PAYMENT MODEL - Error en Pago realizado sin Cybersource');     
                
                $helper->cancelaOrden($order);
                return $this;
            }

        }

        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug( 'DEBUG INICIAL ANTES:::::: ');
        $state = $helper->getConfig('payment/decidir_spsdecidir/estado/inicial');
        
        $order->setState($state, true);
        $order->setStatus($state);
        $order->addStatusToHistory($order->getStatus(), 'Order processed successfully with reference');
        $order->save();

        $estado_transaccion = $respuesta->getStatus();
        $decidir_id_transaccion         = $respuesta->getId();
        $infoOperacionSps['estado_transaccion']=$respuesta->getStatus();
        $helper->setInfoTransaccionSPS($infoOperacionSps);
        $this->_checkoutSession->setFinalizacionCompra(true);


        \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR - PAYMENT MODEL - getstatus: '.$respuesta->getStatus());        

        $helper->actualizarOrden( $order );      


        if($this->_checkoutSession->getFinalizacionCompra()==true){
            $infoOperacionSps = $this->_spsHelper->getInfoTransaccionSPS();
            /*sif($this->_scopeConfig->getValue('payment/decidir_spsdecidir/mode') == \Decidir\SpsDecidir\Model\Webservice::MODE_DEV)
            {
                $helper->log(print_r($infoOperacionSps,true),'info_operacion_sps_post_order.log');
            }*/

            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR2 - Tokenizacion habilitado:'. $this->_scopeConfig->getValue('payment/decidir_spsdecidir/tokenizacion') );  
            if(is_array($infoOperacionSps))
            {
                $orderTransactionIdSps="";

                if($estado_transaccion == $spsTransaccionesHelper::TRANSACCION_OK){
                    
                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR2 - MODEL PAYMENT - Pago aceptado' );                   
                    //$this->_messageManager->addSuccessMessage("error testing");

                    //Si el usuario está registrado y esta habilitado opcion de tokenizar
                    if(!empty($order->getCustomerId())&&($this->_scopeConfig->getValue('payment/decidir_spsdecidir/tokenizacion')==1)){
                        \Magento\Framework\App\ObjectManager::getInstance()
                            ->get(\Psr\Log\LoggerInterface::class)->debug( ' if linea 540' );                   

                        try{
                            $respuestaGetToken = $ws->getTarjetasTokenizadas(array(), $order->getCustomerId());

                            \Magento\Framework\App\ObjectManager::getInstance()
                            ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR2 - MODEL PAYMENT - getTarjetasTokenizadas - $order->getCustomerId(): '.$order->getCustomerId().' - Respuesta: '. print_r($respuestaGetToken, true) );                        
                        }catch(\Exception $e){
                            \Magento\Framework\App\ObjectManager::getInstance()
                            ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR2 - MODEL PAYMENT - getTarjetasTokenizadas ERROR: '.$e );
                        }

                        \Magento\Framework\App\ObjectManager::getInstance()
                            ->get(\Psr\Log\LoggerInterface::class)->debug( 'Antes de guardarToken ' );                   

                            
                        $helper->guardarToken( $respuestaGetToken->getTokens(), $respuesta );

                        \Magento\Framework\App\ObjectManager::getInstance()
                            ->get(\Psr\Log\LoggerInterface::class)->debug( 'Después de guardarToken ' );                   


                    }

                    
                    $orderTransactionIdSps =  $decidir_id_transaccion;
                    $infoOperacionSps['detalles_pago']=$infoOperacionSps['detalles_pago'];
                    
                    $payment->setTransactionId($orderTransactionIdSps);
                    $payment->setAdditionalInformation('detalles_pago',$infoOperacionSps['detalles_pago']);
                    $payment->setAdditionalInformation('bin',$infoOperacionSps['bin']);
                    $payment->setAdditionalInformation('cuota',$infoOperacionSps['cuota']);
                    $payment->setAdditionalInformation('card',$infoOperacionSps['holderName']);
                    $payment->setAdditionalInformation('card_id',$infoOperacionSps['tarjeta_id']);
                    $payment->setAdditionalInformation('interes',$cuotaData[0]['interes']);
                    $payment->setAdditionalInformation('last_digits',$infoOperacionSps['lastDigits']);
                    $payment->setAdditionalInformation('expiration_month',$infoOperacionSps['expirationMonth']);
                    $payment->setAdditionalInformation('expiration_year',$infoOperacionSps['expirationYear']);
                    if( !empty($planPagoData[0]['nombre']) ){
                        $payment->setAdditionalInformation('payment_plan',$planPagoData[0]['nombre']);
                        $payment->setAdditionalInformation('payment_plan_id',$planPagoData[0]['plan_pago_id']);
                        \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug( 'Inovoice-planPagoData : ' . print_r($planPagoData, true) );
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                            $collectionBanco = $objectManager->get('Decidir\AdminPlanesCuotas\Model\Banco')
                            ->getCollection()
                            ->addFieldToFilter('banco_id',['eq'=>$planPagoData[0]['banco_id']]);
                            $dataBanco = $collectionBanco->getData();

                            $payment->setAdditionalInformation('bank',$dataBanco[0]['nombre']);
                            $payment->setAdditionalInformation('bank_id',$dataBanco[0]['banco_id']);
                            \Magento\Framework\App\ObjectManager::getInstance()
                            ->get(\Psr\Log\LoggerInterface::class)->debug( 'dataBanco : ' . print_r($dataBanco, true) );
                    }
                    $payment->setAdditionalInformation('status',$infoOperacionSps['estado_transaccion']);
                    
                    $state = $helper->getConfig('payment/decidir_spsdecidir/estado/aprobado');

                    $order->setState($state, true);
                    $order->setStatus($state);
                    $order->addStatusToHistory($order->getStatus(), 'Order aprobado');
                    $order->save();

                    $transaction = $this->transactionBuilder
                        ->setPayment($payment)
                        ->setOrder($order)
                        ->setTransactionId($payment->getTransactionId())
                        ->build(Transaction::TYPE_AUTH);

                    $mensajeEstado = sprintf(__('La transacción con número %s para el pedido %s ha sido exitosa.'),
                        $orderTransactionIdSps,$order->getIncrementId());

                    $payment->addTransactionCommentsToOrder($transaction, $mensajeEstado);

                    $this->invoice($payment,$infoOperacionSps['detalles_pago']);

                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug( 'Inovoice-infoOperacionSps : ' . print_r($infoOperacionSps, true) );

                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug( 'Inovoice-mensajeEstado : ' . $mensajeEstado );

                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug( 'Inovoice-amount : ' . $amount );


                    $infoOperacionSps['transaccion_mensaje']=$mensajeEstado;
                    $helper->setInfoTransaccionSPS($infoOperacionSps);

                    //$this->_messageManager->addSuccessMessage($mensajeEstado);
                    /*
                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug( ' getSubtotal : ' . $order->getSubtotal()
                        ." getBaseSubtotal: ".$order->getBaseSubtotal()
                        ." getGrandTotal: ".$order->getGrandTotal()
                        ." getBaseGrandTotal: ".$order->getBaseGrandTotal()
                     );

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');
                    $quote = $quoteFactory->create()->load($order->getQuoteId());

                    $order->setBaseGrandTotal(10);
                    $order->setGrandTotal(10);
                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug( ' quote getSubtotal : ' . $quote->getSubtotal()
                        ." quote getBaseSubtotal: ".$quote->getBaseSubtotal()
                        ." quote getGrandTotal: ".$quote->getGrandTotal()
                        ." quote getBaseGrandTotal: ".$quote->getBaseGrandTotal()
                        ." getQuoteId: ".$order->getQuoteId()
                     );
                     /*
                        $total->setTotalAmount('subtotal', 999);
                        $total->setBaseTotalAmount('subtotal', 999);

                        \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug("TOTAL getTotalAmount: " . $total->getTotalAmount() );
                        \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug("TOTAL getBaseTotalAmount: " . $total->getBaseTotalAmount() );
                    
                    $order->setSubtotal($quote->getBaseGrandTotal()+5)
                            ->setBaseSubtotal($quote->getBaseGrandTotal()+5)
                            ->setGrandTotal($quote->getBaseGrandTotal()+5)
                            ->setBaseGrandTotal($quote->getBaseGrandTotal()+5);
                    $quote->setSubtotal($quote->getSubTotal()+5)
                            ->setBaseSubtotal($quote->getBaseGrandTotal()+5)
                            ->setGrandTotal($quote->getBaseGrandTotal()+5)
                            ->setBaseGrandTotal($quote->getBaseGrandTotal()+5);
                    
                    $this->_checkoutSession->getQuote()->setSubtotal(333);
                    $this->_checkoutSession->getQuote()->setBaseSubtotal(333);
                    $this->_checkoutSession->getQuote()->setGrandTotal(333);
                    $this->_checkoutSession->getQuote()->setBaseGrandTotal(333);
                    $quote->save($quote->collectTotals());
                    $this->_checkoutSession->getQuote()->collectTotals()->save();
                    $quote->save();
                    $quote->collectTotals()->save(); 
                    // $order->setBaseGrandTotal(10);
                    //$this->_messageManager->addSuccessMessage($mensajeEstado);
                    //aqui ya guarda el amount_paid
                    */
                    $order->save();


                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug("Debug: " . '$payment->getTransactionId() = ' . $payment->getTransactionId() );

                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Psr\Log\LoggerInterface::class)->debug("Debug: " . '$order->getIncrementId() = ' . $order->getIncrementId() );

                }else{
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR2 - MODEL PAYMENT - Error');

                    $errorMsg=$respuesta->getStatus_details();
                        $orderTransactionIdSps =  $decidir_id_transaccion;
                        if(empty($orderTransactionIdSps)){
                            $orderTransactionIdSps=$order->getIncrementId();
                        }
                        $infoOperacionSps['detalles_pago']=$infoOperacionSps['detalles_pago'];

                        \Magento\Framework\App\ObjectManager::getInstance()
                                ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR2 - MODEL PAYMENT - Pago rechazado. Estado: ' . $infoOperacionSps['estado_transaccion'] );
                        \Magento\Framework\App\ObjectManager::getInstance()
                                ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR2 - MODEL PAYMENT - Pago rechazado. Razón: ' . $errorMsg->error['reason']['description'] );

                        $infoPagoRechazado = sprintf(__('Pago rechazado. Nro Orden: %s. Nro. Operacion Decidir: %s Motivo: %s .'),
                            $order->getIncrementId(), $errorMsg->error['reason']['description'],
                            'Error en transaccion');

                        if($this->_scopeConfig->getValue('payment/decidir_spsdecidir/mode') == \Decidir\SpsDecidir\Model\Webservice::MODE_DEV)
                        {
                            $helper->log($infoPagoRechazado, 'pagos_rechazados_sps_' . date('Y_m_d') . '.log');
                        }

                        $state = $helper->getConfig('payment/decidir_spsdecidir/estado/rechazado');
                        //$state = \Magento\Sales\Model\Order::STATE_CANCELED;
                        $order->setState($state, true);
                        $order->setStatus($state);
                        $order->addStatusToHistory($order->getStatus(), 'Orden rechazada');
                        $order->save();
                        
                        $mensajeEstado = 'Su pago ID '. $orderTransactionIdSps .', de la orden '. $order->getIncrementId() .', con tarjeta de crédito fue rechazado. Mensaje devuelto por DECIDIR: '. $errorMsg->error['reason']['description'];
                        //$this->_messageManager->addErrorMessage($mensajeEstado);


                        $infoOperacionSps['transaccion_mensaje']=$mensajeEstado;
                        $helper->setInfoTransaccionSPS($infoOperacionSps);


                        \Magento\Framework\App\ObjectManager::getInstance()
                                ->get(\Psr\Log\LoggerInterface::class)->debug('Mensaje de error añadido: '.$mensajeEstado);

                            $method = $payment->getMethod();
                            $methodInstance = $this->_paymentHelper->getMethodInstance($method);

                            $payment->setTransactionId($orderTransactionIdSps);
                            
                            if(isset($infoOperacionSps['detalles_pago'])) $payment->setAdditionalInformation('detalles_pago',$infoOperacionSps['detalles_pago']);
                            if(isset($infoOperacionSps['bin'])) $payment->setAdditionalInformation('bin',$infoOperacionSps['bin']);
                            if(isset($infoOperacionSps['cuota'])) $payment->setAdditionalInformation('cuota',$infoOperacionSps['cuota']);
                            if(isset($infoOperacionSps['card'])) $payment->setAdditionalInformation('card',$infoOperacionSps['holderName']);
                            if(isset($infoOperacionSps['tarjeta_id'])) $payment->setAdditionalInformation('card_id',$infoOperacionSps['tarjeta_id']);
                            if(isset($cuotaData[0]['interes'])) $payment->setAdditionalInformation('interes',$cuotaData[0]['interes']);
                            if(isset($infoOperacionSps['last_digits'])) $payment->setAdditionalInformation('last_digits',$infoOperacionSps['lastDigits']);
                            if(isset($infoOperacionSps['expiration_month'])) $payment->setAdditionalInformation('expiration_month',$infoOperacionSps['expirationMonth']);
                            if(isset($infoOperacionSps['expiration_year'])) $payment->setAdditionalInformation('expiration_year',$infoOperacionSps['expirationYear']);
                            if( !empty($planPagoData[0]['nombre']) ){
                                $payment->setAdditionalInformation('payment_plan',$planPagoData[0]['nombre']);
                                $payment->setAdditionalInformation('payment_plan_id',$planPagoData[0]['plan_pago_id']);
                                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                                    if(isset($planPagoData[0]['banco_id'])){
                                        $collectionBanco = $objectManager->get('Decidir\AdminPlanesCuotas\Model\Banco')
                                        ->getCollection()
                                        ->addFieldToFilter('banco_id',['eq'=>$planPagoData[0]['banco_id']]);
                                        $dataBanco = $collectionBanco->getData();

                                        $payment->setAdditionalInformation('bank',$dataBanco[0]['nombre']);
                                        $payment->setAdditionalInformation('bank_id',$dataBanco[0]['banco_id']);

                                        \Magento\Framework\App\ObjectManager::getInstance()
                                        ->get(\Psr\Log\LoggerInterface::class)->debug( 'dataBanco : ' . print_r($dataBanco, true) );
                                    }
                                    
                            }
                            if(isset($infoOperacionSps['estado_transaccion'])) $payment->setAdditionalInformation('status',$infoOperacionSps['estado_transaccion']);
                            
                            $payment->setIsTransactionClosed(1);
 


                            $transaction = $this->transactionRepository->getByTransactionType(
                                Transaction::TYPE_ORDER,
                                $payment->getId(),
                                $payment->getOrder()->getId()
                            );

                            if($transaction == null) {
                                $orderTransactionId = $order->getId();
                                $transaction = $this->transactionBuilder->setPayment($payment)
                                    ->setOrder($order)
                                    ->setTransactionId($payment->getTransactionId())
                                    //->setTransactionId($order->getId())
                                    ->build(Transaction::TYPE_CAPTURE);
                            }
                            $payment->addTransactionCommentsToOrder($transaction, $mensajeEstado); 

                            $order->setState($state)->setStatus($state);

                            $payment->setSkipOrderProcessing(true);
                            $payment->setIsTransactionDenied(true);


                            /*
                            try{
                                $transaction->close();
                            }catch(\Exception $e){
                                \Magento\Framework\App\ObjectManager::getInstance()
                                ->get(\Psr\Log\LoggerInterface::class)->debug("Error al cerrar transacción: $e");                                  
                            }
                            */

                            $order->cancel()->save();
                            //$this->_messageManager->addException($e, $e->getMessage());
                            //$this->_messageManager->addErrorMessage("error testing");
                }

                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('model - payment - cambio de estado. ESTADO: '.$state);                   
                
                
                //$this->_checkoutSession->restoreQuote();

                $history = $order->addStatusHistoryComment($mensajeEstado, $state);
                $history->setIsVisibleOnFront(true);
                $history->setIsCustomerNotified(true);
                $history->save();
            }
        } 

     
        return $this;
    }

    /**
     * @param OrderPaymentInterface $payment
     * @param String $comment
     * @return \Magento\Sales\Model\Order\Invoice
     */
    protected function invoice(OrderPaymentInterface $payment, $comment)
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $payment->getOrder()->prepareInvoice();

        $invoice->register();
        if ($payment->getMethodInstance()->canCapture()) {
            $invoice->capture();
        }

        $payment->getOrder()->addRelatedObject($invoice);

        $invoice->addComment(
            $comment,
            true,
            true
        );

        return $invoice;
    }

    /**
     * Determine method availability based on quote amount and config data
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return true;
    }
}

