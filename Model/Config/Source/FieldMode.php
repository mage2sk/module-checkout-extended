<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FieldMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'compact', 'label' => __('Compact (Multiple Fields Per Row)')],
            ['value' => 'full', 'label' => __('Full Width (One Field Per Row)')],
        ];
    }
}
