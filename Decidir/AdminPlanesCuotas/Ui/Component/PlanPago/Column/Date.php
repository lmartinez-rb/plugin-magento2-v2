<?php

namespace Decidir\AdminPlanesCuotas\Ui\Component\PlanPago\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\Component\Listing\Columns\Column;


/**
 * Class Dias
 *
 * @description Renderizacion de las fechas en formato amigable al usuario, sin la correccion horaria del sistema.
 *
 */
class Date extends Column
{
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TimezoneInterface $timezone
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TimezoneInterface $timezone,
        array $components = [],
        array $data = []
    ) {
        $this->timezone = $timezone;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {


        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])) {
                    $fechaGtm=$this->converToTz(
                        $item[$this->getData('name')], 
                        
                        // get Config Timezone of current user 
                        $this->timezone->getConfigTimezone(),

                        // get default timezone of system (UTC)
                        $this->timezone->getDefaultTimezone()

                    );
                    $item[$this->getData('name')] = $fechaGtm;

                }
            }
        }

        return $dataSource;
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
