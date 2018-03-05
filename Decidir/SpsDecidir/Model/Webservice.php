<?php

namespace Decidir\SpsDecidir\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Checkout\Model\Cart;
use Psr\Log\LoggerInterface;
use Decidir;


require_once(__DIR__.'/../vendor/autoload.php');
//require_once(__DIR__.'/../Test/vendor/autoload.php');

/**
 * Class Webservice
 *
 * @description Modelo de conexiones con el ws de Decidir. Contiene cada metodo para operar con el servicio.

 */
class Webservice
{
    const MODE_DEV       = 'dev';
    const MODE_PROD      = 'prod';
    const ENCODINGMETHOD = 'XML';
    const CURRENCYCODE   = 032;
    const OPERACION_OK   = -1;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Decidir\Connector
     */
    protected $_connector;

    /**
     * @var mixed
     */
    protected $_merchant;

    /**
     * @var mixed
     */
    //protected $_security;

    /**
     * @var
     */
    protected $_requestKey;

    /**
     * @var
     */
    protected $_publicRequestKey;

    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Webservice constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Cart $cart
     * @param LoggerInterface $logger
     */
    public function __construct(ScopeConfigInterface $scopeConfig,Cart $cart,LoggerInterface $logger)
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_cart        = $cart;
        $this->_logger      = $logger;
        $this->_conector="";

        $this->_publicKey = $this->_scopeConfig->getValue('payment/decidir_spsdecidir/public_key');
        $this->_privateKey = $this->_scopeConfig->getValue('payment/decidir_spsdecidir/private_key');

        if($this->_scopeConfig->getValue('payment/decidir_spsdecidir/mode') == self::MODE_DEV)
        {
            $this->_ambient = 'test';
            $this->_endpoint = 'https://developers.decidir.com/api/v2';
        }
        elseif($this->_scopeConfig->getValue('payment/decidir_spsdecidir/mode') == self::MODE_PROD)
        {
            $this->_ambient = 'prod';
            $this->_endpoint = 'https://live.decidir.com/api/v2';
        }
        
/*
        $httpHeader = array(
            //'Authorization' => 'PRISMA '.$this->_security,
            'user_agent'    => 'PHPSoapClient'
        );

        if($this->_scopeConfig->getValue('payment/decidir_spsdecidir/mode') == self::MODE_DEV)
        {
            $this->_connector = new Decidir\Connector($httpHeader, $this->_scopeConfig->getValue('payment/decidir_spsdecidir/dev/endpoint_authorize'));
        }
        elseif($this->_scopeConfig->getValue('payment/decidir_spsdecidir/mode') == self::MODE_PROD)
        {
            $this->_connector = new Decidir\Connector($httpHeader, $this->_scopeConfig->getValue('payment/decidir_spsdecidir/prod/endpoint_authorize'));
        }
        */
    }


    /**
     * @return string
     */
    public function getAmbient()
    {
        return $this->_ambient;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->_endpoint;
    }

    /**
     * @return string
     */
    public function getPublicKey()
    {
        return $this->_publicKey;
    }

    /**
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->_privateKey;
    }

    /**
     * @return string
     */
    /*
    public function getSecurity()
    {
        return $this->_security;
    }
    */

    /**
     * @return mixed
     */
    public function getMerchant()
    {
        return $this->_merchant;
    }

    /**
     * @return mixed
     */
    public function getRequestKey()
    {
        return $this->_requestKey;
    }

    /**
     * @return mixed
     */
    public function getPublicRequestKey()
    {
        return $this->_publicRequestKey;
    }

    /**
     * @description Genera el numero de operacion unico para cada transaccion.
     * @return string
     */
    public function getOperationNumber()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVXYZ';

        return 'OP'.date('Ymdhis').$chars[rand(0,24)].$chars[rand(0,24)];
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->_cart->getQuote();
    }

    /**
     * @description Utiliza Cybersource
     */
    public function setCybersource($data)
    {
        $keys_data = array('public_key' => $this->getPublicKey(),
                   'private_key' => $this->getPrivateKey());
        
        $ambient = $this->getAmbient();
        $this->_conector = new \Decidir\Connector($keys_data, $ambient);

                
        $this->_conector->payment()->setCybersource($data);
    }

    /**
     * @description Vertical Retail de Cybersource
     */

    public function cybersourceRetail($cs_data, $cs_products)
    {
        $cybersource = new \Decidir\Cybersource\Retail($cs_data, $cs_products);

        return $cybersource->getData();
    }

    /**
     * @description Vertical Travel de Cybersource
     */

    public function cybersourceTravel($cs_data, $cs_passanger)
    {
        $cybersource = new \Decidir\Cybersource\Travel($cs_data, $cs_passanger);

        return $cybersource->getData();
    }

    /**
     * @description Vertical Services de Cybersource
     */

    public function cybersourceServices($cs_data, $cs_products)
    {
        $cybersource = new \Decidir\Cybersource\Service($cs_data, $cs_products);

        return $cybersource->getData();
    }


    /**
     * @description Vertical Ticketing de Cybersource
     */

    public function cybersourceTicketing($cs_data, $cs_products)
    {
        $cybersource = new \Decidir\Cybersource\Ticketing($cs_data, $cs_products);

        return $cybersource->getData();
    }

    /**
     * @description Vertical Digitalgoods de Cybersource
     */

    public function cybersourceDigitalgoods($cs_data, $cs_products)
    {
        $cybersource = new \Decidir\Cybersource\Ticketing($cs_data, $cs_products);

        return $cybersource->getData();
    }





    /**
     * @description Hace pago
     *
     * @param $data
     * @return \Decidir\Authorize\GetAuthorizeAnswer\Response
     */
    public function pagar($data)
    {
        $keys_data = array('public_key' => $this->getPublicKey(),
                   'private_key' => $this->getPrivateKey());

        $ambient = $this->getAmbient();
        $connector = new \Decidir\Connector($keys_data, $ambient);
        
        $response = $connector->payment()->ExecutePayment($data);
        return $response;       
    }


    /**
     * @description Hace pago cuando usa Cybersource
     *
     * @param $data
     * @return \Decidir\Authorize\GetAuthorizeAnswer\Response
     */
    public function pagarCs($data)
    {
        $response = $this->_conector->payment()->ExecutePayment($data);
        return $response;
    }



    /**
     * @description Hace Devolución total
     *
     * @param $data
     * @param $id     
     * @return \Decidir\Authorize\GetAuthorizeAnswer\Response
     */
    public function devolverTotal($data, $id)
    {
        $keys_data = array('public_key' => $this->getPublicKey(),
                   'private_key' => $this->getPrivateKey());

        $ambient = $this->getAmbient();
        $connector = new \Decidir\Connector($keys_data, $ambient);
        $response = $connector->payment()->Refund($data, $id);
        return $response;
    }

    /**
     * @description Hace devolución parcial
     *
     * @param $data
     * @param $id     
     * @return \Decidir\Authorize\GetAuthorizeAnswer\Response
     */
    public function devolverParcial($data, $id)
    {
        $keys_data = array('public_key' => $this->getPublicKey(),
                   'private_key' => $this->getPrivateKey());

        $ambient = $this->getAmbient();
        $connector = new \Decidir\Connector($keys_data, $ambient);
        $response = $connector->payment()->partialRefund($data, $id);
        return $response;
    }

    /**
     * @description Pide listado de tarjetas tokenizadas
     *
     * @param $data
     * @param $id     
     * @return \Decidir\Authorize\GetAuthorizeAnswer\Response
     */
    public function getTarjetasTokenizadas($data, $id_client)
    {
        $keys_data = array('public_key' => $this->getPublicKey(),
                   'private_key' => $this->getPrivateKey());
        $data = array();
        
        $ambient = $this->getAmbient();
        $connector = new \Decidir\Connector($keys_data, $ambient);
        $response = $connector->cardToken()->tokensList($data, $id_client);
        return $response;
    }




}
