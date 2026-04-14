<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SidebarPosition implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'right', 'label' => __('Right')],
            ['value' => 'left', 'label' => __('Left')],
        ];
    }
}
