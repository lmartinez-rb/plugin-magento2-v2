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


    class Costo extends \Magento\Framework\View\Element\Template
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

            $fee = new \Magento\Framework\DataObject(
                [
                    'code' => 'costo',
                    'strong' => false,
                    'value' => $this->_order->getCosto(),
                    'base_value' => $this->_order->getCosto(),
                    'label' => 'Costo financiero',
                ]
            );
            $parent->addTotal($fee, 'costo');

            $descuento = new \Magento\Framework\DataObject(
                [
                    'code' => 'descuento',
                    'strong' => true,
                    'value' => $this->_order->getDescuentoCuota() * (-1),
                    'base_value' => -$this->_order->getDescuentoCuota() * (-1),
                    'label' => $order->getDescuentoCuotaDescripcion(),
                ]
            );
            $parent->addTotal($descuento, 'descuento');



                    /*
                    $parent->getTotal('grand_total')->setValue($parent->getTotal('grand_total')->getValue());

            //if($parent->getTotal('paid'))
                $parent->getTotal('paid')->setValue($parent->getTotal('paid')->getValue());
*/

                    return $this;

        }

    }