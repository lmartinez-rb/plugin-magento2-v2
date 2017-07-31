<?php

namespace Decidir\SpsDecidir\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\TestFramework\Inspection\Exception;
use Zend\Crypt\BlockCipher;
use Decidir\SpsDecidir\Helper\Data as SpsHelper;
use Decidir\SpsDecidir\Model\Webservice;
use Decidir\SpsDecidir\Model\DecidirTokenFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;


/**
 * Class AuthorizeAnswer
 *
 * @description Action que se encarga de generar el segundo y ultimo paso de integracion con SPS. Se conecta con el WS,
 *              y envia los datos de la transaccion para saber si esta fue correcta o no.
 */
class AuthorizeAnswer extends Action
{
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var Webservice
     */
    protected $_webservice;

    /**
     * @var SpsHelper
     */
    protected $_spsHelper;

    /**
     * @var DecidirTokenFactory
     */
    protected $_decidirTokenFactory;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * AuthorizeAnswer constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param JsonFactory $resultJsonFactory
     * @param SpsHelper $spsHelper
     * @param Webservice $webservice
     * @param DecidirTokenFactory $decidirToken
     * @param CustomerSession $customerSession
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param CheckoutSession $checkoutSession
     * @param CollectionFactory $collectionFactory     
     */
    public function __construct
    (
        Context $context,
        PageFactory $resultPageFactory,
        OrderRepositoryInterface $orderRepositoryInterface,
        JsonFactory $resultJsonFactory,
        SpsHelper $spsHelper,
        Webservice $webservice,
        DecidirTokenFactory $decidirToken,
        CustomerSession $customerSession,
        ScopeConfigInterface $scopeConfigInterface,
        CheckoutSession $checkoutSession,
        CollectionFactory $collectionFactory
    )
    {
        $this->_resultPageFactory   = $resultPageFactory;
        $this->_orderRepository     = $orderRepositoryInterface;
        $this->_resultJsonFactory   = $resultJsonFactory;
        $this->_spsHelper           = $spsHelper;
        $this->_webservice          = $webservice;
        $this->_decidirTokenFactory = $decidirToken;
        $this->_customerSession     = $customerSession;
        $this->_scopeConfig         = $scopeConfigInterface;
        $this->_checkoutSession     = $checkoutSession;
        $this->_collectionFactory     = $collectionFactory;


        parent::__construct($context);
    }


    /**
     * @description Guarda respuesta de Decidir en variable de sesión. MOVER ESTA LÓGICA A MÉTODO PAYMENT
     * @return $this
     */
    public function execute()
    {
        $this->_checkoutSession->setFinalizacionCompra(true);
        $helper             = $this->_spsHelper;
        $result = $this->_resultJsonFactory->create();
        $customerSession    = $this->_customerSession;

        $request = $this->getRequest();
        $quote_id = $request->getParam('quote_id');
        $bin = $request->getParam('bin');
        $tarjeta_sps = $request->getParam('tarjeta_sps');
        $cuota = $request->getParam('cuota');
        $decidir_id_transaccion = $request->getParam('decidir_id_transaccion');
        $holderName = $request->getParam('holderName');
        $lastDigits = $request->getParam('lastDigits');
        $expirationMonth = $request->getParam('expirationMonth');
        $expirationYear = $request->getParam('expirationYear');
        $detalles_pago = $request->getParam('detalles_pago'); ///////////
        $tokenPago = $request->getParam('tokenPago');
        $bancoId = $request->getParam('bancoId');
        $planPago = $request->getParam('planPago');

        $salesOrderCollection = $this->_collectionFactory->create();
        $salesOrderCollection->addFieldToFilter('quote_id', $quote_id);
        $salesData=$salesOrderCollection->getData();


        $respuestaTransaccion['detalles_pago']         = $detalles_pago;
        $respuestaTransaccion['quote_id']         = $quote_id;
        $respuestaTransaccion['bin']         = $bin;
        $respuestaTransaccion['tarjeta_sps']         = $tarjeta_sps;
        $respuestaTransaccion['cuota']         = $cuota;
        $respuestaTransaccion['decidir_id_transaccion']         = $decidir_id_transaccion;
        $respuestaTransaccion['holderName']         = $holderName;
        $respuestaTransaccion['lastDigits']         = $lastDigits;
        $respuestaTransaccion['expirationMonth']         = $expirationMonth;
        $respuestaTransaccion['expirationYear']         = $expirationYear;
        $respuestaTransaccion['tokenPago']         = $tokenPago;
        $respuestaTransaccion['tarjeta_id']         = $tarjeta_sps;
        $respuestaTransaccion['bancoId']         = $bancoId;
        $respuestaTransaccion['planPago']         = $planPago;

        $helper->setInfoTransaccionSPS($respuestaTransaccion);



        return $result->setData(['estado_transaccion'=>\Decidir\SpsDecidir\Helper\Data::STATUS_TRANSACCION_ERRONEA]);

















/*
        $order=$this->_orderRepository->get($salesData[0]['entity_id']);
        //die;
        //echo "incremental: ".$order->getIncrementId();
        //return $result->setData(['estado_transaccion'=>\Decidir\SpsDecidir\Helper\Data::STATUS_TRANSACCION_ERRONEA]);
        //die;

        $this->_checkoutSession->setFinalizacionCompra(true);
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('AuthorizeAnswer: '.print_r($salesOrderCollection->getData(), true));
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('Token obtenido de orden: '.$salesData[0]['token']);
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('base_grand_total obtenido de orden: '.$salesData[0]['base_grand_total']);
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('increment_id obtenido de orden: '.$salesData[0]['increment_id']);
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('customer_id obtenido de orden: '.$salesData[0]['customer_id']);
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('bin obtenido de orden: '.$bin);
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('tarjeta id: '.$tarjeta_sps);
        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('cuotas: '.$cuota);

        try{
            $ws = $this->_webservice;
            $data = array(
                        "site_transaction_id" => $salesData[0]['increment_id'],
                        //"token" => $salesData[0]['token'],
                        "token" => $tokenPago,
                        "user_id" => $salesData[0]['customer_id'],
                        "payment_method_id" => (int)$tarjeta_sps,
                        "amount" => number_format($salesData[0]['base_grand_total'], 2),
                        "bin" => $bin,
                        "currency" => "ARS",
                        "installments" => (int)$cuota,
                        "description" => "Orden ".$salesData[0]['increment_id'],
                        "payment_type" => "single",
                        "sub_payments" => array(),
                        "fraud_detection" => array()
            );
            $respuesta = $ws->pagar($data);
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug('decidir_id_transaccion: '.$respuesta->getId());

        
            if($customerSession->isLoggedIn())
            {
                $decidirToken = $this->_decidirTokenFactory->create();

                $decidirToken->setToken($respuesta->getToken());
                $decidirToken->setCardHolderName($holderName);
                $decidirToken->setCardNumberLast4Digits($lastDigits);
                $decidirToken->setCardExpirationMonth($expirationMonth);
                $decidirToken->setCardExpirationYear($expirationYear);
                $decidirToken->setCardBin($respuesta->getBin());
                $decidirToken->setCardType($respuesta->getPaymentMethodId());
                $decidirToken->setCustomerId($customerSession->getCustomer()->getId());
                $decidirToken->setBancoId(1);

                $decidirToken->save();
            }


            $respuestaTransaccion['estado_transaccion'] = $respuesta->getStatus();
            $respuestaTransaccion['tarjeta_id']         = $tarjeta_sps;
            $respuestaTransaccion['decidir_id_transaccion']         = $respuesta->getId();
            $respuestaTransaccion['detalles_pago']         = $detalles_pago;

            $helper->actualizarOrden($order);


            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug( 'setInfoTransaccionSPS: '.print_r($respuestaTransaccion, true) );
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug( 'setInfoTransaccionSPS: '.print_r($helper->getInfoTransaccionSPS($respuestaTransaccion), true) );


            $this->_checkoutSession->setFinalizacionCompra(true);
            return $result->setData(['estado_transaccion'=>$respuesta->getStatus()]);
        }catch(\Exception $e){
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug( 'Error en pago: '.$e );

            $respuestaTransaccion['estado_transaccion'] = \Decidir\SpsDecidir\Helper\Data::STATUS_TRANSACCION_ERRONEA;
            $helper->setInfoTransaccionSPS($respuestaTransaccion);

            $this->_checkoutSession->setFinalizacionCompra(true);
            return $result->setData(['estado_transaccion'=>\Decidir\SpsDecidir\Helper\Data::STATUS_TRANSACCION_ERRONEA]);
        }
        */
    }
}