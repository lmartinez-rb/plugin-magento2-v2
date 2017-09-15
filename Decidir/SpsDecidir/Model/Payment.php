<?php

namespace Decidir\SpsDecidir\Model;

use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Decidir\SpsDecidir\Model\Webservice;
use Magento\Customer\Model\Session as CustomerSession;
use Decidir\SpsDecidir\Model\DecidirTokenFactory;
use Decidir\AdminPlanesCuotas\Model\CuotaFactory;
use Decidir\AdminPlanesCuotas\Model\PlanPagoFactory;

/**
 * Class Payment
 *
 * @description Modelo representativo del metodo de pago SpsDecidir
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'decidir_spsdecidir';

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
     * @var PlanPagoFactory
     */
    protected   $_planPagoFactory;


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
        \Decidir\SpsDecidir\Helper\Data $spsHelper,
        \Decidir\SpsDecidir\Helper\EstadoTransaccion $spsTransaccionesHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Decidir\SpsDecidir\Model\Webservice $webservice,
        \Magento\Customer\Model\Session $customerSession,
        \Decidir\SpsDecidir\Model\DecidirTokenFactory $decidirToken,
        \Decidir\AdminPlanesCuotas\Model\CuotaFactory $cuotaFactory,
        \Decidir\AdminPlanesCuotas\Model\PlanPagoFactory $planPagoFactory,
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
        $this->_spsHelper               = $spsHelper;
        $this->_spsTransaccionesHelper  = $spsTransaccionesHelper;
        $this->_messageManager          = $messageManager;
        $this->_webservice          = $webservice;
        $this->_customerSession          = $customerSession;
        $this->_decidirTokenFactory = $decidirToken;
        $this->_cuotaFactory        = $cuotaFactory;
        $this->_planPagoFactory        = $planPagoFactory;

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
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR2 - MODEL PAYMENT - InfoOperacion: '.print_r($infoOperacionSps, true));        
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
        /*\Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug( 'getCcuotaData: '. print_r($cuotaData, true) );*/
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

        try{
            $ws = $this->_webservice;
            $data = array(
                "site_transaction_id" => $order->getIncrementId(),
                "token" => $infoOperacionSps['tokenPago'],
                "user_id" => $customerId,
                "payment_method_id" => (int)$infoOperacionSps['tarjeta_id'],
                "amount" => number_format($amount, 2),
                "bin" => $infoOperacionSps['bin'],
                "currency" => "ARS",
                "installments" => (int)$cantidad_cuotas,
                "description" => "Orden ".$order->getIncrementId(),
                "payment_type" => "single",
                "sub_payments" => array(),
                "fraud_detection" => array()
            );
            if(!empty($planPagoData[0]['merchant'])){
                $data["site_id"]=$planPagoData[0]['merchant'];
            }


            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR2 - MODEL PAYMENT - pagar - Data: '.print_r($data, true) );
            $respuesta = $ws->pagar($data);

            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug('DECIDIR2 - MODEL PAYMENT - pagar respuesta: '.print_r($respuesta, true) );            
        }catch(\Exception $e){
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR2 - MODEL PAYMENT - pagar ERROR: '.$e );
        }


        $estado_transaccion = $respuesta->getStatus();
        $decidir_id_transaccion         = $respuesta->getId();
        $infoOperacionSps['estado_transaccion']=$respuesta->getStatus();
        $helper->setInfoTransaccionSPS($infoOperacionSps);
        $this->_checkoutSession->setFinalizacionCompra(true);

        $helper->actualizarOrden( $order );      


        if($this->_checkoutSession->getFinalizacionCompra()==true){
            $infoOperacionSps = $this->_spsHelper->getInfoTransaccionSPS();
            /*sif($this->_scopeConfig->getValue('payment/decidir_spsdecidir/mode') == \Decidir\SpsDecidir\Model\Webservice::MODE_DEV)
            {
                $helper->log(print_r($infoOperacionSps,true),'info_operacion_sps_post_order.log');
            }*/

            if(is_array($infoOperacionSps))
            {
                if($estado_transaccion == $spsTransaccionesHelper::TRANSACCION_OK)
                {
                    //Si el usuario está registrado
                    if(!empty($order->getCustomerId())){                    
                        try{
                            $respuestaGetToken = $ws->getTarjetasTokenizadas(array(), $order->getCustomerId());

                            \Magento\Framework\App\ObjectManager::getInstance()
                            ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR2 - MODEL PAYMENT - getTarjetasTokenizadas - $order->getCustomerId(): '.$order->getCustomerId().' - Respuesta: '. print_r($respuestaGetToken, true) );                        
                        }catch(\Exception $e){
                            \Magento\Framework\App\ObjectManager::getInstance()
                            ->get(\Psr\Log\LoggerInterface::class)->debug( 'DECIDIR2 - MODEL PAYMENT - getTarjetasTokenizadas ERROR: '.$e );
                        }

                        $helper->guardarToken( $respuestaGetToken->getTokens(), $respuesta );
                    }


                    $orderTransactionIdSps =  $decidir_id_transaccion;
                    $infoOperacionSps['detalles_pago']=$infoOperacionSps['detalles_pago'];

                    $payment->setTransactionId($orderTransactionIdSps);
                    $payment->setAdditionalInformation('detalles_pago',$infoOperacionSps['detalles_pago']);

                    $status = $this->_scopeConfig->getValue('payment/decidir_spsdecidir/order_status');

                    $transaction = $this->transactionBuilder
                        ->setPayment($payment)
                        ->setOrder($order)
                        ->setTransactionId($payment->getTransactionId())
                        ->build(Transaction::TYPE_AUTH);

                    $mensajeEstado = sprintf(__('La transacción con número %s para el pedido %s ha sido exitosa.'),
                        $orderTransactionIdSps,$order->getIncrementId());

                    $payment->addTransactionCommentsToOrder($transaction, $mensajeEstado);

                    $this->invoice($payment,$infoOperacionSps['detalles_pago']);

                    $this->_messageManager->addSuccessMessage($mensajeEstado);
                }
                else
                {
                    $infoPagoRechazado = sprintf(__('Pago rechazado. Nro Orden: %s. Nro. Operacion Decidir: %s Motivo: %s .'),
                        $order->getIncrementId(), $orderTransactionIdSps,
                        'Error en transaccion');

                    if($this->_scopeConfig->getValue('payment/decidir_spsdecidir/mode') == \Decidir\SpsDecidir\Model\Webservice::MODE_DEV)
                    {
                        $helper->log($infoPagoRechazado, 'pagos_rechazados_sps_' . date('Y_m_d') . '.log');
                    }

                    $status = \Magento\Sales\Model\Order::STATE_CANCELED;

                    $mensajeEstado = 'Su pago con tarjeta de crédito fue rechazado. Mensaje devuelto por DECIDIR: %s.';

                    $this->_messageManager->addErrorMessage($mensajeEstado);

                    $order->registerCancellation($mensajeEstado);
                    $order->cancel();
                }

                $order->save();


                $history = $order->addStatusHistoryComment($mensajeEstado, $status);
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

