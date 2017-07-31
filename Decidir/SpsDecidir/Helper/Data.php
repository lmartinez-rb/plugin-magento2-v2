<?php

namespace Decidir\SpsDecidir\Helper;

use Decidir\SpsDecidir\Helper\EstadoTransaccion;
use Decidir\SpsDecidir\Model\DecidirTokenFactory;
use Magento\Customer\Model\Session as CustomerSession;


/**
 * Class Data
 *
 * @description Helper base para el metodo de pago
 *
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @const Palabra secreta para encriptar datos
     */
    const SECRET_WORD           = 'd3c51d1r$$_s3cr3tTsdMMM__';
    const TRANSACCION_OK        = 1;
    const TRANSACCION_ERRONEA   = 0;
    const STATUS_TRANSACCION_ERRONEA = 'error';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Decidir\SpsDecidir\Helper\EstadoTransaccion
     */
    protected $_helperEstadoTransaccion;

    /**
     * @var DecidirTokenFactory
     */
    protected $_decidirTokenFactory;

    /**
     * @var CustomerSession
     */
    protected $_customerSession;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Decidir\SpsDecidir\Helper\EstadoTransaccion $helperEstadoTransaccion
     * @param \Decidir\SpsDecidir\Model\DecidirTokenFactory $decidirToken
     * @param \Magento\Customer\Model\Session $customerSession     
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Decidir\SpsDecidir\Helper\EstadoTransaccion $helperEstadoTransaccion,
        \Decidir\SpsDecidir\Model\DecidirTokenFactory $decidirToken,
        \Magento\Customer\Model\Session $customerSession
        
    ) {
        $this->_coreRegistry    = $coreRegistry;
        $this->_checkoutSession = $checkoutSession;
        $this->_helperEstadoTransaccion = $helperEstadoTransaccion;
        $this->_decidirTokenFactory = $decidirToken;
        $this->_customerSession          = $customerSession;        
        $this->_scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    /**
     * @description Serializo y encripto la informacion resultante de la operacion para ser almacenada en una
     *              variable de sesion.
     * @param $data
     */
    public function setInfoTransaccionSPS($data)
    {
        $blockCipher = \Zend\Crypt\BlockCipher::factory('mcrypt', array('algo' => 'aes'));
        $blockCipher->setKey(self::SECRET_WORD);

        $transaccionSerializada = $blockCipher->encrypt(serialize($data));

        $this->_checkoutSession->setOperacionSps($transaccionSerializada);
    }

    /**
     * @description desencripto la informacion resultante de la ultima operacion y devuelve un array con su informacion
     *
     * @return array
     */
    public function getInfoTransaccionSPS()
    {
        $blockCipher = \Zend\Crypt\BlockCipher::factory('mcrypt', array('algo' => 'aes'));
        $blockCipher->setKey(self::SECRET_WORD);

        //print_r($this->_checkoutSession->getOperacionSps());
        return unserialize($blockCipher->decrypt($this->_checkoutSession->getOperacionSps()));
    }

    /**
     * @description Actualiza orden 
     *
     * @return bool
     */
    public function actualizarOrden($order)
    {
        $payment = $order->getPayment();

        if($payment->getMethod() == \Decidir\SpsDecidir\Model\Payment::CODE)
        {
            $helperTransaccion  = $this->_helperEstadoTransaccion;
            $infoOperacionSps =  $this->getInfoTransaccionSPS();


            if($infoOperacionSps['estado_transaccion'] != $helperTransaccion::TRANSACCION_OK)
            {
                $order->registerCancellation('Error en transaccion');
                $order->cancel();

                $payment->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
                $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_CANCELED));

                $order->save();
            }
            else
            {
                $orderStatus = $this->_scopeConfig->getValue('payment/decidir_spsdecidir/order_status');
                $order->setStatus($orderStatus);
                $order->save();
            }
        }

        \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Psr\Log\LoggerInterface::class)->debug('Data helper: ');
        //echo "traza fin mètodo actualizarOrden";
        return true;
    }


    public function guardarToken($respuestaGetToken, $respuesta){
        $decidirTokenTarjeta=0;
        $infoOperacionSps =  $this->getInfoTransaccionSPS();
        $customerSession        = $this->_customerSession;
        
        foreach($respuestaGetToken as $tokenList){
            /*\Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug(
                $tokenList['token']." | ".$respuesta->getToken()."\n".
                $tokenList['last_four_digits']."==".$infoOperacionSps['lastDigits']."\n".
                $tokenList['expiration_month']."==".$infoOperacionSps['expirationMonth']."\n".
                $tokenList['expiration_year']."==".$infoOperacionSps['expirationYear']."\n"
                );         */               
                
            if( $tokenList['last_four_digits']==$infoOperacionSps['lastDigits']
            AND (int)$tokenList['expiration_month']==$infoOperacionSps['expirationMonth']
            AND (int)$tokenList['expiration_year']==$infoOperacionSps['expirationYear'] ){
                $decidirTokenTarjeta=$tokenList['token'];
               /* \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug("Foreach tokens) igual [ ". $decidirTokenTarjeta ." ]");*/
                break;
            }
        }

        if($customerSession->isLoggedIn() AND !empty($decidirTokenTarjeta))
        {
            $tokenExists = $this->_decidirTokenFactory->create()
                ->getCollection()
                ->addFieldToFilter('token',['eq'=>$decidirTokenTarjeta]);

            /*\Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug( 'token factory: '.print_r($tokenExists->getData(), true) );*/

            if(count($tokenExists)<1){
                $decidirToken = $this->_decidirTokenFactory->create();
                $decidirToken->setToken( $decidirTokenTarjeta );
                $decidirToken->setCardHolderName($infoOperacionSps['holderName']);
                $decidirToken->setCardNumberLast4Digits($infoOperacionSps['lastDigits']);
                $decidirToken->setCardExpirationMonth($infoOperacionSps['expirationMonth']);
                $decidirToken->setCardExpirationYear($infoOperacionSps['expirationYear']);
                $decidirToken->setCardBin($respuesta->getBin());
                $decidirToken->setCardType($respuesta->getPaymentMethodId());
                $decidirToken->setCustomerId($customerSession->getCustomer()->getId());
                $decidirToken->setBancoId( $infoOperacionSps['bancoId'] );

                $decidirToken->save();
            }
        }
    }


    /**
     * @param $mensaje String
     * @param $archivo String
     */
    public static function log($mensaje,$archivo)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/'.$archivo);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($mensaje);
    }


}
