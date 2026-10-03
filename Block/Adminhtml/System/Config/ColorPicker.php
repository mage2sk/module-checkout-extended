<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ColorPicker extends Field
{
    protected function _getElementHtml(AbstractElement $element): string
    {
        $html = $element->getElementHtml();
        $value = $element->getEscapedValue();
        $html .= '<script type="text/javascript">
            require(["jquery"], function($) {
                var el = $("#' . $element->getHtmlId() . '");
                el.attr("type", "color");
                el.css({"width": "60px", "height": "36px", "padding": "2px", "cursor": "pointer"});
            });
        </script>';
        return $html;
    }
}
