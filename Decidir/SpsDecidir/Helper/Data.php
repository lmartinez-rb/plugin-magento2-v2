<?php

namespace Decidir\SpsDecidir\Helper;

use Decidir\SpsDecidir\Helper\EstadoTransaccion;
use Decidir\SpsDecidir\Model\DecidirTokenFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

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
     * @var DateTime
     */
    protected $_dateTime;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;



    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Decidir\SpsDecidir\Helper\EstadoTransaccion $helperEstadoTransaccion
     * @param \Decidir\SpsDecidir\Model\DecidirTokenFactory $decidirToken
     * @param \Magento\Customer\Model\Session $customerSession     
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param CollectionFactory $collectionFactory          
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Decidir\SpsDecidir\Helper\EstadoTransaccion $helperEstadoTransaccion,
        \Decidir\SpsDecidir\Model\DecidirTokenFactory $decidirToken,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        CollectionFactory $collectionFactory
    ) {
        $this->_coreRegistry    = $coreRegistry;
        $this->_checkoutSession = $checkoutSession;
        $this->_helperEstadoTransaccion = $helperEstadoTransaccion;
        $this->_decidirTokenFactory = $decidirToken;
        $this->_customerSession          = $customerSession; 
        $this->_dateTime          = $dateTime;        
        $this->_collectionFactory     = $collectionFactory;        
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
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('helper - data - actualizarOrden: TRANSACCIÓN ERRÓNEA ' . \Magento\Sales\Model\Order::STATE_CANCELED );                   



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

                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('helper - data - actualizarOrden: TRANSACCIÓN OK ' . $orderStatus );                   

                $order->setStatus($orderStatus);
                $order->save();
            }
        }

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
     * @description Actualiza orden 
     *
     * @return array
     */
    public function getDataControlFraudeCommon($order, $customer, $ship_to=true){
        $shippingAdress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();
        $customerId = $order->getCustomerId();

        if($customerId == "" or $customerId == null){
            $customerId = "guest".date("ymdhs");
        }

        $email = $this->getField($billingAddress->getEmail());

        $fecha_1 = date('d-m-Y', $this->_dateTime->timestamp($customer->getCreatedAt()));
        $fecha_2 = date('d-m-Y', $this->_dateTime->timestamp($this->_dateTime->gmtDate()));

        $is_guest = true;
        $num_transactions = 1;
        $pass = "N";

        if(!$order->getCustomerIsGuest()) {
            $is_guest = false;
            $customer_id = $order->getCustomerId();
            $salesOrderCollection = $this->_collectionFactory->create();
            $salesOrderCollection->addFieldToFilter('customer_id', $customer_id);
            $num_transactions = $salesOrderCollection->count();
            $pass = $customer->getPasswordHash();
        }

        if(is_array($billingAddress->getRegion())){
            $region = implode(" ", $billingAddress->getRegion());
        }else{
            $region = $billingAddress->getRegion();
        }

        if(is_array($billingAddress->getStreet())){
            $street = implode(" ", $billingAddress->getStreet());
        }else{
            $street = $billingAddress->getStreet();
        }


        if(is_array($shippingAdress->getRegion())){
            $regionShipping = implode(" ", $shippingAdress->getRegion());
        }else{
            $regionShipping = $shippingAdress->getRegion();
        }

        if(is_array($shippingAdress->getStreet())){
            $streetShipping = implode(" ", $shippingAdress->getStreet());
        }else{
            $streetShipping = $shippingAdress->getStreet();
        }        

        $payDataOperacion = array(
                "send_to_cs" => true,
                "channel" => "Web",
                "bill_to" => array(
                    "city" => $this->getField($billingAddress->getCity()),
                    "country" => $billingAddress->getCountryId(),
                    "customer_id" => $customerId,
                    "email" => $email,
                    "first_name" => $this->getField($billingAddress->getFirstname()),
                    "last_name" => $this->getField($billingAddress->getLastname()),
                    "phone_number" => $this->getField($billingAddress->getTelephone()),
                    "postal_code" => $this->getField($billingAddress->getPostcode()),
                    "state" => strtoupper(substr($this->getField( $region ),0,1)),
                    "street1" => implode(" ",$billingAddress->getStreet()),
                    "street2" => ""       
                ),
           
                "currency" => "ARS",
                "amount" => (int)number_format($order->getGrandTotal(), 2, ".", ""),
                "days_in_site" => $this->diasTranscurridos($fecha_1, $fecha_2),
                "is_guest" => $is_guest,
                "password" => $pass,
                "num_of_transactions" => $num_transactions,
                "cellphone_number" => $this->getField($billingAddress->getTelephone()),
                "date_of_birth" => "129412",
                "street" => $street
        );

        if($ship_to){
            $payDataOperacion["ship_to"]=array(
                "city" => $this->getField($shippingAdress->getCity()),
                "country" => $this->getField($shippingAdress->getCountryId()),
                "customer_id" => $customerId,
                "email" => $email,
                "first_name" => $this->getField($shippingAdress->getFirstname()),
                "last_name" => $this->getField($shippingAdress->getLastname()),
                "phone_number" => $this->getField($shippingAdress->getTelephone()),
                "postal_code" => $this->getField($shippingAdress->getTelephone()),
                "state" => strtoupper(substr($this->getField($regionShipping), 0, 1)),
                "street1" => $this->getField($streetShipping),
                "street2" => "",
            );
        }

        return $payDataOperacion;
    }

    public function getMultipleProductsInfo($order){
        $products = $order->getAllItems();


        $i=0;
        foreach($products as $prod) {
            $payDataOperacion[$i]['csitproductcode'] =  "default";
            $payDataOperacion[$i]['csitproductdescription'] = $prod->getSku()."ss";
            $payDataOperacion[$i]['csitproductname'] = $prod->getName();
            $payDataOperacion[$i]['csitproductsku'] = $prod->getSku();
            $payDataOperacion[$i]['csittotalamount'] = (int)number_format($prod->getPrice() * $prod->getQtyOrdered(),2,".","");
            $payDataOperacion[$i]['csitquantity'] = (int)number_format($prod->getQtyOrdered(), 0, "", "");
            $payDataOperacion[$i]['csitunitprice'] = (int)number_format($prod->getPrice(),2,".","");

            $i++;            
        }
        return $payDataOperacion;        
    }

    public function getDataControlFraudeTicketing($order){
        $payDataOperacion = array(
            "days_to_event" => 10, // TODO GSHOKIDA: De dónde saco este valor?
            "delivery_type" => $this->getField($order->getShippingDescription())
        );

        return $payDataOperacion;
    }

    public function getDataControlTravel($order){
        $customer=$this->_customerSession->getCustomer();
        $shippingAdress = $order->getShippingAddress();

        $payDataOperacion = array(
             "reservation_code"=>"GJH784",
             "third_party_booking"=>false,
             "departure_city"=>"EZE",
             "final_destination_city"=>"HND",
             "international_flight"=>true,
             "frequent_flier_number"=>"00000123",
             "class_of_service"=>"class",
             "day_of_week_of_flight"=>2,
             "week_of_year_of_flight"=>5,
             "airline_code"=>"AA",
             "code_share"=>"SKYTEAM",
             "decision_manager_travel" => array(
                "complete_route"=>"EZE-LAX:LAX-HND",
                "journey_type"=>"one way",
                "departure_date" => array (
                   "departure_time"=>"2017-05-30T09:00Z",
                   "departure_zone"=>"GMT-0300"
                )
             ),
            "airline_number_of_passengers" => 1
            );

        return $payDataOperacion;
    }


    public function getDataControlTravelPassengers($order){
        $customer=$this->_customerSession->getCustomer();
        $shippingAdress = $order->getShippingAddress();

        $payDataOperacion =array(
            array(
                "email"=>$customer->getEmail(),  //$this->_customerSession
                "first_name"=>$this->getField($customer->getFirstname()),
                "last_name"=>$this->getField($customer->getLastname()),
                "passport_id"=>"412314851231",
                "phone"=>$this->getField($shippingAdress->getTelephone()),
                "passenger_status"=>"gol",
                "passenger_type"=>"ADT"
            )
        );

        return $payDataOperacion;
    }


    public function getDataControlFraudeServices($order){
        /****** SERVICES ******/
        //$items=$this->getMultipleProductsInfo($order);
        $payDataOperacion = array(array(
           "service_type" => "eltipodelservicio",
           "reference_payment_service1" => "reference1",
           "reference_payment_service2" => "reference2",
           "reference_payment_service3" => "reference3",
            "csmdd17" => "17",
           //"items"=>$items
        ),
         /*
          "csmdds" => array(
            array("code" => 17, "description" => "Campo MDD17"),
            array("code" => 18, "description" => "Campo MDD18"),
            array("code" => 19, "description" => "Campo MDD19"),
            array("code" => 20, "description" => "Campo MDD20")
           )
           */
        );
        return $payDataOperacion;
    }

    public function getDataControlFraudeDigitalgoods($order){
        $payDataOperacion = array(
            "delivery_type" => $this->getField($order->getShippingDescription())
        );

        return $payDataOperacion;
    }



    public function getDataControlFraudeRetail($order, $arrCommon){
        $shippingAdress = $order->getShippingAddress();
        $customerId = $order->getCustomerId();


        if($customerId == "" or $customerId == null){
            $customerId = "guest".date("ymdhs");
        }

        $email = $this->getField($shippingAdress->getEmail());


        if(is_array($shippingAdress->getRegion())){
            $region = implode(" ", $shippingAdress->getRegion());
        }else{
            $region = $shippingAdress->getRegion();
        }

        if(is_array($shippingAdress->getStreet())){
            $street = implode(" ", $shippingAdress->getStreet());
        }else{
            $street = $shippingAdress->getStreet();
        }

        $payDataOperacion = array(
            /*
                "send_to_cs" => $arrCommon["send_to_cs"],
                "channel" => $arrCommon["channel"],
                "bill_to" => array(
                    "city" => $arrCommon["bill_to"]["city"],
                    "country" => $arrCommon["bill_to"]["country"],
                    "customer_id" => $arrCommon["bill_to"]["customer_id"],
                    "email" => $arrCommon["bill_to"]["email"],
                    "first_name" => $arrCommon["bill_to"]["first_name"],
                    "last_name" => $arrCommon["bill_to"]["last_name"],
                    "phone_number" => $arrCommon["bill_to"]["phone_number"],
                    "postal_code" => $arrCommon["bill_to"]["postal_code"],
                    "state" => $arrCommon["bill_to"]["state"],
                    "street1" => $arrCommon["bill_to"]["street1"],
                    "street2" => $arrCommon["bill_to"]["street2"]       
                ),

            "ship_to" => array(
                "city" => $this->getField($shippingAdress->getCity()),
                "country" => $this->getField($shippingAdress->getCountryId()),
                "customer_id" => $customerId,
                "email" => $email,
                "first_name" => $this->getField($shippingAdress->getFirstname()),
                "last_name" => $this->getField($shippingAdress->getLastname()),
                "phone_number" => $this->getField($shippingAdress->getTelephone()),
                "postal_code" => $this->getField($shippingAdress->getTelephone()),
                "state" => strtoupper(substr($this->getField($region), 0, 1)),
                "street1" => $this->getField($street),
                "street2" => "",
            ),
                "currency" => $arrCommon["currency"],
                "amount" => $arrCommon["amount"],
                "days_in_site" => $arrCommon["days_in_site"],
                "is_guest" => $arrCommon["is_guest"],
                "password" => $arrCommon["password"],
                "num_of_transactions" => $arrCommon["num_of_transactions"],
                "cellphone_number" => $arrCommon["cellphone_number"],
                "date_of_birth" => $arrCommon["date_of_birth"],
                "street" => $arrCommon["street"],

*/

            "days_to_delivery" => $this->_scopeConfig->getValue('payment/decidir_spsdecidir/cybersourcedeadline'),
            "dispatch_method" => $this->getField($order->getShippingDescription()),
            "tax_voucher_required" => true,
            "customer_loyality_number" => "123232",
            "coupon_code" => $this->getField($order->getCuponCode()),
            "csmdd17" => "17"
        );

        return $payDataOperacion;
    }

    public function getField($datasources){
        $return = "";
        try{
            $return = $this->_sanitize_string($datasources);
        }catch(Exception $e){
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug("Modulo de pago - Decidir 2 ==> 
                no se pudo agregar el campo: Exception: $e");
        }

        return $return;
    }


    private function _sanitize_string($string){
        $string = htmlspecialchars_decode($string);

        $re = "/\\[(.*?)\\]|<(.*?)\\>/i";
        $subst = "";
        $string = preg_replace($re, $subst, $string);

        $replace = array("#", "[", "]", "{", "}", "<", ">", "¬", "^", ":", ";", "|", "~", "*","&", "_", "¿", "?", "¡","!","'","\'",
        "\"","  ","$","\\","\n","\r",'\n','\r','\t',"\t","\n\r",'\n\r','&nbsp;','&ntilde;',".,",",.","+", "%", "-", ")", "(", "°");
        $string = str_replace($replace, '', $string);

        $cods = array('\u00c1','\u00e1','\u00c9','\u00e9','\u00cd','\u00ed','\u00d3','\u00f3','\u00da','\u00fa','\u00dc','\u00fc','\u00d1','\u00f1');
        $susts = array('Á','á','É','é','Í','í','Ó','ó','Ú','ú','Ü','ü','Ñ','ñ');
        $string = str_replace($cods, $susts, $string);

        $no_permitidas= array ("À","Ã","Ì","Ò","Ù","Ã™","Ã ","Ã¨","Ã¬","Ã²","Ã¹","ç","Ç","Ã¢","ê","Ã®","Ã´","Ã»","Ã‚","ÃŠ","ÃŽ","Ã”","Ã›","ü","Ã¶","Ã–","Ã¯","Ã¤","«","Ò","Ã","Ã„","Ã‹");
        $permitidas= array    ("A","E","I","O","U","a","e","i","o","u","c","C","a","e","i","o","u","A","E","I","O","U","u","o","O","i","a","e","U","I","A","E");
        $string = str_replace($no_permitidas, $permitidas ,$string);

        return $string;      
    }


    public function diasTranscurridos($fecha_i, $fecha_f) {
        $dias = (strtotime ( $fecha_i ) - strtotime ( $fecha_f )) / 86400;
        $dias = abs ( $dias );
        $dias = floor ( $dias );
        return $dias;
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
