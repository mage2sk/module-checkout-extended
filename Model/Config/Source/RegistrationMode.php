<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RegistrationMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'disabled', 'label' => __('Disabled')],
            ['value' => 'optional', 'label' => __('Optional (Checkbox)')],
            ['value' => 'required', 'label' => __('Required')],
        ];
    }
}
