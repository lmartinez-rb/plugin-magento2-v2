<?php

namespace Decidir\AdminPlanesCuotas\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallData
 *
 * @description Actializa datos
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * Upgrades DB for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */     
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
     
          $setup->startSetup();

          if ( version_compare( $context->getVersion(), '2.0.2' ) < 0 ) {
               if ( $setup->getTableRow( $setup->getTable( 'decidir_tarjeta' ), 'sps_tarjeta_id', 6 ) ) {
                    $setup->updateTableRow(
                    $setup->getTable( 'decidir_tarjeta' ),
                    'sps_tarjeta_id',
                    6,
                    'sps_tarjeta_id',
                    65
                    );
               }            
          }

          $setup->endSetup();
          
     }
}