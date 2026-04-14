<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ColumnLayout implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('1 Column (Stacked)')],
            ['value' => '2', 'label' => __('2 Columns (Content + Sidebar)')],
            ['value' => '3', 'label' => __('3 Columns (Shipping | Payment | Summary)')],
        ];
    }
}
