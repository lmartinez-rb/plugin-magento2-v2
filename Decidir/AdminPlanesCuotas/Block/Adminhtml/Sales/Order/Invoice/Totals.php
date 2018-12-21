<?php
    /**
     * Copyright © 2015 Magento. All rights reserved.
     * See COPYING.txt for license details.
     */

    /**
     * Tax totals modification block. Can be used just as subblock of \Magento\Sales\Block\Order\Totals
     */
    namespace Decidir\AdminPlanesCuotas\Block\Adminhtml\Sales\Order\Invoice;
//	    namespace Namespace\Module\Block\Adminhtml\Sales\Order\Invoice;


    class Totals extends \Magento\Framework\View\Element\Template
    {
        protected $_config;
        protected $_order;
        protected $_source;

        public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Tax\Model\Config $taxConfig,
            array $data = []
        ) {
            $this->_config = $taxConfig;
            parent::__construct($context, $data);
        }

        public function displayFullSummary()
        {
            return true;
        }

        public function getSource()
        {
            return $this->_source;
        } 
        public function getStore()
        {
            return $this->_order->getStore();
        }
        public function getOrder()
        {
            return $this->_order;
        }
        public function getLabelProperties()
        {
            return $this->getParentBlock()->getLabelProperties();
        }

        public function getValueProperties()
        {
            return $this->getParentBlock()->getValueProperties();
        }
         public function initTotals()
        {
            $parent = $this->getParentBlock();
            $this->_order = $parent->getOrder();
            $this->_source = $parent->getSource();

            $store = $this->getStore();
            $costo = 0;
            if($this->_order->getCosto() != 0 && $this->_order->getCosto() != "" && !empty($this->_order->getCosto()))
                $costo = $this->_order->getCosto();
                
            $fee = new \Magento\Framework\DataObject(
                [
                    'code' => 'costo',
                    'strong' => false,
                    'value' => $costo,
                    'base_value' => $costo,
                    'label' => 'Costo financiero',
                ]
            );
            $parent->addTotal($fee, 'costo');

            $valor_descuento = 0;
            $label_descuento = "Descuento";
            if( !empty($this->_order->getDescuentoCuotaDescripcion())){
                $valor_descuento = $this->_order->getDescuentoCuota();
                $label_descuento = $this->_order->getDescuentoCuotaDescripcion();
            }
                

            $descuento = new \Magento\Framework\DataObject(
                [
                    'code' => 'descuento',
                    'strong' => true,
                    'value' => $valor_descuento * (-1),
                    'base_value' => $valor_descuento * (-1),
                    'label' => $label_descuento,
                ]
            );
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug(" --- DESCUENTO --- ".print_r($descuento,true)); 
            $parent->addTotal($descuento, 'descuento');

            \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug( 'DATA_ORDER: '.print_r($this->getOrderId(), true) );

                    /*
                    $parent->getTotal('grand_total')->setValue($parent->getTotal('grand_total')->getValue());

            //if($parent->getTotal('paid'))
                $parent->getTotal('paid')->setValue($parent->getTotal('paid')->getValue());
*/

                    return $this;

        }

    }
