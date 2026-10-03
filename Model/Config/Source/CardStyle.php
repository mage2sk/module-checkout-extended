<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CardStyle implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'elevated', 'label' => __('Elevated (Shadow)')],
            ['value' => 'bordered', 'label' => __('Bordered')],
            ['value' => 'flat', 'label' => __('Flat (No Border)')],
            ['value' => 'glass', 'label' => __('Glassmorphism')],
        ];
    }
}
