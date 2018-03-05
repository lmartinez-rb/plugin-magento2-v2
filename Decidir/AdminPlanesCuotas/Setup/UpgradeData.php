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

          if ( version_compare( $context->getVersion(), '2.1.11' ) < 0 ) {
              //Nuevas tarjetas
               $datosTarjetas = [
                 ['sps_tarjeta_id'=>56,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'TARJETA CLUB DÍA','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>61,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'TARJETA LA ANÓNIMA','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>99,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'MAESTRO','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>54,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'GRUPAR','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>59,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'TUYA','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>103,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'FAVACARD','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>50,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'BBPS','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>52,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'QIDA','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>55,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'PATAGONIA 365 ','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>60,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'DISTRIBUTION','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>62,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'CREDIGUIA','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>64,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'TARJETA SOL','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>25,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'PAGOFACIL','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>26,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'RAPIPAGO','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>48,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'CAJA DE PAGOS','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>51,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'COBRO EXPRESS','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>31,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'VISA DÉBITO','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>66,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'MASTERCARD DEBIT','activo'=>0,'prioridad_orden'=>99],
                 ['sps_tarjeta_id'=>67,'nps_tarjeta_id'=>999,'logo_src'=>null,'nombre'=>'CABAL DÉBITO','activo'=>0,'prioridad_orden'=>99]
             ];
             $setup->getConnection()
                 ->insertArray($setup->getTable('decidir_tarjeta'),
                     ['sps_tarjeta_id', 'nps_tarjeta_id','logo_src','nombre','activo','prioridad_orden'], $datosTarjetas);


              //Nuevos bancos
             $datosBancos = [
                 ['nombre'=>'NARANJA'                              ,'activo'=>1,'logo_src'=>null,'prioridad_orden'=>18],
                 ['nombre'=>'OTROS'                                ,'activo'=>1,'logo_src'=>null,'prioridad_orden'=>19]
             ];
             $setup->getConnection()
                 ->insertArray($setup->getTable('decidir_banco'),
                     ['nombre','activo','logo_src','prioridad_orden'], $datosBancos);


          }

          $setup->endSetup();
          
     }
}