<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;
use Panth\CheckoutExtended\Model\Config\Source\CardStyle;
use Panth\CheckoutExtended\Model\Config\Source\ColumnLayout;
use Panth\CheckoutExtended\Model\Config\Source\FieldMode;
use Panth\CheckoutExtended\Model\Config\Source\RegistrationMode;
use Panth\CheckoutExtended\Model\Config\Source\SidebarPosition;
use PHPUnit\Framework\TestCase;

class SourceModelsTest extends TestCase
{
    public function testToOptionArrayReturnsExpectedOptions(string $className, array $expectedValues): void
    {
        $source = new $className();

        $this->assertInstanceOf(OptionSourceInterface::class, $source);

        $options = $source->toOptionArray();

        $this->assertIsArray($options);
        $this->assertCount(count($expectedValues), $options);

        foreach ($options as $option) {
            $this->assertIsArray($option);
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertNotSame('', $this->renderLabel($option['label']));
        }

        $this->assertSame($expectedValues, array_column($options, 'value'));
    }

    public function testToOptionArrayReturnsExpectedLabels(string $className, array $expectedLabels): void
    {
        $source = new $className();

        $labels = array_map(
            fn ($option) => $this->renderLabel($option['label']),
            $source->toOptionArray()
        );

        $this->assertSame($expectedLabels, $labels);
    }

    public function sourceModelsDataProvider(): array
    {
        return [
            'column layout 1/2/3' => [ColumnLayout::class, ['1', '2', '3']],
            'sidebar position right/left' => [SidebarPosition::class, ['right', 'left']],
            'card style elevated/bordered/flat/glass' => [
                CardStyle::class,
                ['elevated', 'bordered', 'flat', 'glass'],
            ],
            'field mode compact/full' => [FieldMode::class, ['compact', 'full']],
            'registration mode disabled/optional/required' => [
                RegistrationMode::class,
                ['disabled', 'optional', 'required'],
            ],
        ];
    }

    public function sourceLabelsDataProvider(): array
    {
        return [
            'column layout labels' => [
                ColumnLayout::class,
                [
                    '1 Column (Stacked)',
                    '2 Columns (Content + Sidebar)',
                    '3 Columns (Shipping | Payment | Summary)',
                ],
            ],
            'sidebar position labels' => [
                SidebarPosition::class,
                ['Right', 'Left'],
            ],
            'card style labels' => [
                CardStyle::class,
                ['Elevated (Shadow)', 'Bordered', 'Flat (No Border)', 'Glassmorphism'],
            ],
            'field mode labels' => [
                FieldMode::class,
                ['Compact (Multiple Fields Per Row)', 'Full Width (One Field Per Row)'],
            ],
            'registration mode labels' => [
                RegistrationMode::class,
                ['Disabled', 'Optional (Checkbox)', 'Required'],
            ],
        ];
    }

    private function renderLabel($label): string
    {
        return $label instanceof Phrase ? $label->getText() : (string) $label;
    }
}
