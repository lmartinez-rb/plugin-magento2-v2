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

			if(count($detallesCuota->getData()) && $detallesCuota->getInteres() > 1){			
				$grandTotal=$quote->getGrandTotal();
                $baseGrandTotal=$quote->getBaseGrandTotal();

				$baseGrandFinal = $quote->getGrandTotal() * $detallesCuota->getInteres();
				$baseGrandFinal = $baseGrandFinal - $quote->getGrandTotal();
				$quote->setCosto( $baseGrandFinal );

        	    $quote->setGrandTotal( $baseGrandFinal + $grandTotal );
	            $quote->setBaseGrandTotal( $baseGrandFinal + $baseGrandTotal  );

				$this->_checkoutSession->setAplicarCosto(true);
				$this->quoteRepository->save($quote->collectTotals());		
			}else{
				$quote->setCosto( 0 );
			}
			
			

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('No se pudo agregar el costo financiero de la cuota'));
        }
        
		if($detallesCuota->getInteres() > 1){
            $quote->setGrandTotal( $baseGrandFinal + $grandTotal );
            $quote->setBaseGrandTotal( $baseGrandFinal + $baseGrandTotal  );


			$this->quoteRepository->save($quote->collectTotals());


			return 'Costo Financiero ';
		}
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
                $quote->setGrandTotal($quote->getGrandTotal() - $quote->getCosto());
                $quote->setBaseGrandTotal($quote->getBaseGrandTotal() - $quote->getCosto());
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
