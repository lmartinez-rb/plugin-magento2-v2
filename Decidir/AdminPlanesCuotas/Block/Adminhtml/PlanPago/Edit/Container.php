<?php

namespace Decidir\AdminPlanesCuotas\Block\Adminhtml\PlanPago\Edit;

use Magento\Backend\Block\Template;

/**
 * Class Container
 *
 * @description Bloque para editar un plan de pago.
 */
class Container extends \Decidir\AdminPlanesCuotas\Block\Adminhtml\PlanPago\Container
{
    /**
     * @var \Decidir\AdminPlanesCuotas\Model\Session
     */
    protected $_session;

    /**
     * @var \Decidir\AdminPlanesCuotas\Model\CuotaFactory
     */
    protected $_cuotasFactory;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleFactory;

    /**
     * @var
     */
    private   $_planPagoId;

    /**
     * Container constructor.
     * @param Template\Context $context
     * @param array $data
     * @param \Decidir\AdminPlanesCuotas\Model\TarjetaFactory $medioPagoFactory
     * @param \Decidir\AdminPlanesCuotas\Model\BancoFactory $bancoFactory
     * @param \Decidir\AdminPlanesCuotas\Model\CuotaFactory $cuotasFactory
     * @param \Decidir\AdminPlanesCuotas\Helper\Data $helper
     * @param \Decidir\AdminPlanesCuotas\Model\Session $adminplanescuotasSession
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     */
    public function __construct(
        Template\Context $context,
        array $data = [],
        \Decidir\AdminPlanesCuotas\Model\TarjetaFactory $medioPagoFactory,
        \Decidir\AdminPlanesCuotas\Model\BancoFactory $bancoFactory,
        \Decidir\AdminPlanesCuotas\Model\CuotaFactory $cuotasFactory,
	\Decidir\AdminPlanesCuotas\Model\PlanPagoFactory $planPagoFactory,
        \Decidir\AdminPlanesCuotas\Helper\Data $helper,
        \Decidir\AdminPlanesCuotas\Model\Session $adminplanescuotasSession,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory
    )
    {
        $this->_session       = $adminplanescuotasSession;
        $this->_cuotasFactory = $cuotasFactory;
        $this->_ruleFactory         = $ruleFactory;

	   $this->_planPagoFactory = $planPagoFactory;
       $this->timezoneInterface = $context->getLocaleDate();
       

        parent::__construct($context,$data,$medioPagoFactory,$bancoFactory,$helper,$ruleFactory);
    }


    /**
     * @return mixed
     */
    public function getPlanPago()
    {
    	$rowId = (int) $this->_session->getPlanPago();
    	$rowData = $this->_planPagoFactory->create();

    	$planPago = $rowData->load($rowId);
        $this->_planPagoId = $planPago->getPlanPagoId();


        $vigenteDesdeGtm=$this->converToTz(
            $planPago->getVigenteDesde(), 
            // get Config Timezone of current user 
            $this->timezoneInterface->getConfigTimezone(),

            // get default timezone of system (UTC)
            $this->timezoneInterface->getDefaultTimezone()

        );
        $planPago->setVigenteDesde( $vigenteDesdeGtm );

        //Guarda con TIME ZONE CORRECTO
        $vigenteHastaGtm=$this->converToTz(
            $planPago->getVigenteHasta(), 
            // get Config Timezone of current user 
            $this->timezoneInterface->getConfigTimezone(),

            // get default timezone of system (UTC)
            $this->timezoneInterface->getDefaultTimezone() 

        );        
        $planPago->setVigenteHasta( $vigenteHastaGtm );

        
        return $planPago->getData();
    }




    /**
     * @return mixed
     */
    public function getCuotas()
    {
        $cuotas = $this->_cuotasFactory->create()
            ->getCollection()
            ->addFieldToFilter('plan_pago_id',['eq'=>$this->_planPagoId]);

        return $cuotas->getData();
    }


    /**
     * converToTz convert Datetime from one zone to another
     * @param string $dateTime which we want to convert
     * @param string $toTz timezone in which we want to convert
     * @param string $fromTz timezone from which we want to convert
    */
    protected function converToTz($dateTime="", $toTz='', $fromTz='')
    {   
        //always is in arg
        $toTz = $fromTz;
        // timezone by php friendly values
        $date = new \DateTime($dateTime, new \DateTimeZone($fromTz));
        $date->setTimezone(new \DateTimeZone($toTz));
        //$dateTime = $date->format('d/m/Y H:i:s');
        
        
        $dateTime = $date->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);

        return $dateTime;
    }
}
