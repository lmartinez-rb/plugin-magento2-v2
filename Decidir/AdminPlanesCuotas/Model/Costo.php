<?php 
namespace Decidir\AdminPlanesCuotas\Model;

use Decidir\AdminPlanesCuotas\Api\CostoInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class Descuento
 *
 * @description Modelo dedicado a la ejecucion de la API REST de magento. Este se encarga de aplicar o descartar un
 *              descuento al total de una compra.
 *
 */
class Costo implements CostoInterface
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var CuotaFactory
     */
    protected $_cuotaFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    protected $_cresource;

    /**
     * Descuento constructor.
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param CuotaFactory $cuotaFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Decidir\AdminPlanesCuotas\Model\CuotaFactory $cuotaFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\ResourceConnection $cresource
    ) {
        $this->_cresource = $cresource;
        $this->quoteRepository  = $quoteRepository;
        $this->_cuotaFactory    = $cuotaFactory;
        $this->_checkoutSession = $checkoutSession;

        $this->_checkoutSession->setAplicarCosto(false);
    }

    /**
     * {@inheritdoc}
     */
    public function set($planPagoId,$cuota)
    {
         
        $cartId = $this->_checkoutSession->getQuoteId();

        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        $quote->getShippingAddress()->setCollectShippingRates(true);

        try
        {
			
			$detallesCuota = $this->_cuotaFactory->create()->getDetalles($planPagoId,$cuota);
            $this->print_debub("getInteres:".print_r($detallesCuota->getInteres(),true));
			if(count($detallesCuota->getData()) && $detallesCuota->getInteres() > 1){			
                
                $discountTotalCupon = 0;
                foreach ($quote->getAllItems() as $item){
                    $discountTotalCupon += $item->getDiscountAmount();
                }
                $this->print_debub("discountTotalCupon:".$discountTotalCupon);

                $tax_shipping = $quote->collectTotals()->getTotals()["shipping"]->getValue();
                $subtotal = $quote->collectTotals()->getTotals()["subtotal"]->getValue();
                $grandTotal = $subtotal;
                $totalCompra = $grandTotal + $tax_shipping - $discountTotalCupon;
                $descuento = $detallesCuota->getData()["descuento"];
                
                //se fija en 0 antes de ser calculado
                $quote->setDescuentoCuota(0);
                if($descuento != 0){
                    $compraDescuento = $totalCompra  * ($descuento/100);
                    $this->_checkoutSession->setAplicarDescuento(true);
                    $totalCompra  = $totalCompra  - $compraDescuento;
                    $quote->setDescuentoCuota(round($compraDescuento,2));
                } 
                $total = ($totalCompra) * $detallesCuota->getInteres();
                $restaCFT = $total - $totalCompra;
               
/*
        	    $quote->setGrandTotal( $quote->getGrandTotal());
                $quote->setBaseGrandTotal( $quote->getBaseGrandTotal());
                
*/              $oldCosto = floatval($quote->getCosto());
                $this->print_debub("oldCosto:".$oldCosto);

                $this->_checkoutSession->setAplicarCosto(true);
                $quote->setCosto(round($restaCFT,2));
                $quote->setBaseCosto(round($restaCFT,2));
                $this->print_debub("restaCFT:".$restaCFT);
                
               // $quote->collectTotals();
               // $this->quoteRepository->save($quote);	
                $this->print_debub(" Costo.php total :".$total);
                if(!($oldCosto > 0)) $this->print_debub(" oldCosto > 0 ");
                //llamamos de nuevo el totalsCollector para que tome
                //el nuevo CFT con descuento 0

               // if(($descuento == 0) || (!($oldCosto > 0)))
               //$this->_checkoutSession->getQuote()->collectTotals()->save();


               $this->quoteRepository->save($quote);
               $this->_checkoutSession->getQuote()->collectTotals()->save();
			}else{
				$quote->setCosto( 0 );
			}
			
			

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('No se pudo agregar el costo financiero de la cuota'));
        }
        
		if($detallesCuota->getInteres() > 1){
            /*
            $quote->setGrandTotal( $baseGrandFinal + $grandTotal );
            $quote->setBaseGrandTotal( $baseGrandFinal + $baseGrandTotal  );


			$this->quoteRepository->save($quote->collectTotals());
*/

			return 'Costo Financiero ';
		}
    }
    public function print_debub($cad){
        \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug($cad); 
    }
    /**
     * @description Resetea cualquier descuento aplicado al carrito por una cuota
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function reset()
    {
        $cartId = $this->_checkoutSession->getQuoteId();

        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        if (!$quote->getItemsCount()) {
            throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
        }
        $quote->getShippingAddress()->setCollectShippingRates(true);

        try
        {
            /**
             * Verifico si el gran total ya tiene aplicado un descuento por cuota anterior, y en caso de ser afirmativo
             * lo elimino para que se pueda calcular nuevamente.
             */
            if($quote->getCosto() > 0)
            {
                $quote->setGrandTotal($quote->getGrandTotal());
                $quote->setBaseGrandTotal($quote->getBaseGrandTotal());
                $quote->setCosto(0);

            }

            $quote = $quote->collectTotals();

            $this->quoteRepository->save($quote);

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('No se pudo guardar el descuento'));
        }

        return true;
    }
}
