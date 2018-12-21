<?php
namespace Decidir\AdminPlanesCuotas\Model;

use Decidir\AdminPlanesCuotas\Api\DescuentoInterface;
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
class Descuento implements DescuentoInterface
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

        $this->_checkoutSession->setAplicarDescuento(false);
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

            if(count($detallesCuota->getData()) && $detallesCuota->getDescuento() > 0)
            {
                $descuentoFinal = 0;
                \Magento\Framework\App\ObjectManager::getInstance()
                 ->get(\Psr\Log\LoggerInterface::class)->debug("detallesCuota->getDescuento():".print_r($detallesCuota->getData(),true));

                 $this->_checkoutSession->setAplicarDescuento(true);
                /**
                 * Verifico si el gran total ya tiene aplicado un descuento por cuota anterior, y en caso de ser afirmativo
                 * lo elimino para que se pueda calcular nuevamente.
                 */
                if($quote->getDescuentoCuota() > 0)
                {
                    \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Psr\Log\LoggerInterface::class)->debug('Model/Descuento.php - Aplica descuento - Plan tiene descuento:'.$quote->getDescuentoCuota());  

                    $quote->setSubtotal($quote->getSubtotal() + $quote->getDescuentoCuota());
                    $quote->setBaseSubtotal($quote->getBaseSubtotal() + $quote->getDescuentoCuota());
                    $quote->setDescuentoCuota($quote->getDescuentoCuota());
                    $quote->setDescuentoCuotaDescripcion('');
                }

                $s = $detallesCuota->getCuota() == 1 ? '' : 's';

                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug('vec_getTotals' );  

                $tax_shipping = $quote->collectTotals()->getTotals()["shipping"]->getValue();
                $subtotal = $quote->collectTotals()->getTotals()["subtotal"]->getValue();
                $grandTotal = $subtotal;
                $discountTotalCupon = 0;
                foreach ($quote->getAllItems() as $item){
                    $discountTotalCupon += $item->getDiscountAmount();
                }
                $totalCompra = $grandTotal + $tax_shipping - $discountTotalCupon;
                $descuento = $detallesCuota->getData()["descuento"];
                $quote->setDescuentoCuota(0);
                if($descuento != 0){
                    $compraDescuento = $totalCompra  * ($descuento/100);
                    $this->_checkoutSession->setAplicarDescuento(true);
                    
                    $totalCompra  = $totalCompra  - $compraDescuento;
                    

                    $quote->setDescuentoCuota(round($compraDescuento,2));
                } 

                \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug(' Descuento.php quote->getDescuentoCuota(): '.$quote->getDescuentoCuota());  

                $quote->setDescuentoCuotaDescripcion("Descuento por pago en {$detallesCuota->getCuota()} cuota$s con {$detallesCuota->getTarjetaNombre()} y {$detallesCuota->getBancoNombre()}");
            }
            else
            {
                \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug("setDescuentoCuota 0");
               
                $quote->setDescuentoCuota(0);
                $quote->setDescuentoCuotaDescripcion('');
            }

            $this->quoteRepository->save($quote->collectTotals());
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Psr\Log\LoggerInterface::class)->debug("final step"); 

        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->debug('Model/Descuento.php - Exception - Error: ' . $e);  

            throw new CouldNotSaveException(__('No se pudo agregar el descuento de cuota - ERROR: ' . $e));
        }

		return $quote->getDescuentoCuotaDescripcion();


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
            if($quote->getDescuentoCuota() > 0)
            {
                \Magento\Framework\App\ObjectManager::getInstance()
                   ->get(\Psr\Log\LoggerInterface::class)->debug( 'RESET descuentoCuota ');  

                $quote->setSubtotal($quote->getSubtotal() + $quote->getDescuentoCuota());
                $quote->setBaseSubtotal($quote->getBaseSubtotal() + $quote->getDescuentoCuota());
                $quote->setDescuentoCuota(0);
                $quote->setDescuentoCuotaDescripcion('');

            }

            $quote = $quote->collectTotals();

            $this->quoteRepository->save($quote);

        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('No se pudo guardar el descuento'));
        }

        return true;
    }
}
