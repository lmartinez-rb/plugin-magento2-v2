<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Decidir\AdminPlanesCuotas\Model\Config\Source;

/**
 * Order Status source model
 */

class Vertical implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'retail', 'label' => __('Retail')],
            ['value' => 'ticketing', 'label' => __('Ticketing')],          
            ['value' => 'services', 'label' => __('Services')],
            ['value' => 'digitalgoods', 'label' => __('Digital Goods')],
            ['value' => 'travel', 'label' => __('Travel')]
        ];
    }
}
