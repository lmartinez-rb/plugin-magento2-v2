<?php

namespace Decidir\SpsDecidir\Observer;

use Decidir\SpsDecidir\Model\Webservice;
use Magento\Framework\Event\Observer;
use Magento\Framework\DataObject as Object;
use Magento\Framework\Event\ObserverInterface;
use Decidir\SpsDecidir\Helper\Data;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class Devolucion
 *
 * @description Observer que devuelve un monto de dinero al comprador si la orden fue realizada por el modulo de sps. Se
 *              conecta al webservice enviando los datos de la operacion, y DECIDIR devuelve su aprobacion o denegacion.
 *              En caso de ser negativa su respuesta, no se genera el creditmemo de magento.
 *
 */
class Devolucion implements ObserverInterface
{
    /**
     * @var \Decidir\SpsDecidir\Helper\Data
     */
    private $_helper;

    /**
     * @var ManagerInterface
     */
    private $_messageManager;

    /**
     * @var Webservice
     */
    private $_webservice;

    /**
     * @var UrlInterface
     */
    protected $_urlInterface;

    /**
     * @var ResponseFactory
     */
    protected $_responseFactory;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;


    /**
     * Devolucion constructor.
     * @param Data $helper
     * @param ManagerInterface $messageManager
     * @param Webservice $webservice
     * @param ResponseFactory $responseFactory
     * @param UrlInterface $url
     * @param RequestInterface $requestInterface
     * @param OrderRepositoryInterface $orderRepositoryInterface
     */
    public function __construct(
        Data $helper,
        ManagerInterface $messageManager,
        Webservice $webservice,
        ResponseFactory $responseFactory,
        UrlInterface $url,
        RequestInterface $requestInterface,
        OrderRepositoryInterface $orderRepositoryInterface

    )
    {
        $this->_helper          = $helper;
        $this->_messageManager  = $messageManager;
        $this->_webservice      = $webservice;
        $this->_urlInterface    = $url;
        $this->_responseFactory = $responseFactory;
        $this->_request         = $requestInterface;
        $this->_orderRepository     = $orderRepositoryInterface;

    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /**
         * @var \Magento\Sales\Model\Order\Creditmemo
         */
        $creditmemo = $observer->getCreditmemo();

        /**
         * @var \Magento\Sales\Model\Order\Invoice
         */
        $invoice    = $creditmemo->getInvoice();

        $order = $this->_orderRepository->get($creditmemo->getOrderId());


        if($order->getPayment()->getMethod() == \Decidir\SpsDecidir\Model\Payment::CODE)
        {
            $ws = $this->_webservice;

            $this->_helper->log('Debug getOrderId:'.
                print_r($creditmemo->getOrderId(),true)
                ,'devoluciones.log');

            $transaction_id="";

            $oInvoiceCollection = $order->getInvoiceCollection();
            foreach ($oInvoiceCollection as $oInvoice) {
                $transaction_id  = $oInvoice->getTransactionId();

                if(!empty($transaction_id))
                    continue;
            }

            try{
                if ($order->getGrandTotal() == $creditmemo->getGrandTotal())
                {
                    $data=array();
                    $response = $ws->devolverTotal($data, $transaction_id);
                    $this->_helper->log('Debug Refund:'.
                        print_r($response, true)
                        ,'devoluciones.log');
                }
                else
                {
                    $data=array(
                        "amount" => number_format($creditmemo->getGrandTotal(), 2)
                    );
                    $response = $ws->devolverParcial($data, $transaction_id);
                    $this->_helper->log('Debug Refund:'.
                        print_r($response, true)
                        ,'devoluciones.log');
                }



                $this->_helper->log('Debug getMethod:'.
                    $order->getPayment()->getMethod()
                    ,'devoluciones.log');

                $creditmemo->addComment(
                $response->getStatus(),
                true,
                true
                );

                //$this->_messageManager->addSuccessMessage($devolucion->getStatusMessage());

                $this->_helper->log('Debug transaction_id:'.
                    $transaction_id
                    ,'devoluciones.log');

            }
            catch(\Exception $e)
            {
                $this->_helper->log($e,'devoluciones.log');

                $this->_messageManager->addErrorMessage($e->getMessage());

                //$RedirectUrl = $this->_urlInterface->getUrl('sales/*/new',[
                  //  'order_id'=>$creditmemo->getOrderId(),
                    //'invoice_id'=>$invoice->getId() //???????????????????????
                //]);

                ////$this->_responseFactory->create()->setRedirect($RedirectUrl)->sendResponse();

                exit();
            }

        }

        return $this;
    }
}
