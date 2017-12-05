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
//use Magento\Framework\Message\ManagerInterface as ManagerInterface;

/**
 * Class ValidarPago
 *
 * @description Action que se encarga de generar el segundo y ultimo paso de integracion con SPS. Se conecta con el WS,
 *              y envia los datos de la transaccion para saber si esta fue correcta o no.
 */
class ValidarPago extends Action
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
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

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
        //ManagerInterface $messageManager        
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
        //$this->_messageManager      = $messageManager;


        parent::__construct($context);
    }


    /**
     * @description Guarda respuesta de Decidir en variable de sesión. MOVER ESTA LÓGICA A MÉTODO PAYMENT
     * @return $this
     */
    public function execute()
    {
        $infoOperacionSps = $this->_spsHelper->getInfoTransaccionSPS();
        //$mensajeEstado=print_r($infoOperacionSps, true);

        if($infoOperacionSps["estado_transaccion"]=="approved"){
            $this->messageManager->addSuccessMessage($infoOperacionSps["transaccion_mensaje"]);
            $this->_redirect('checkout/onepage/success');
        }else{
            $this->messageManager->addErrorMessage($infoOperacionSps["transaccion_mensaje"]);
            $this->_redirect('checkout/onepage/failure');
        }
    }
}