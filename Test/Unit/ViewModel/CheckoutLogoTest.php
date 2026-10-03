<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\Test\Unit\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Header\Logo;
use Panth\CheckoutExtended\ViewModel\CheckoutLogo;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CheckoutLogoTest extends TestCase
{
    private const STORE_ID = 2;
    private const THEME_ID = 5;

    private $scopeConfig;
    private $storeManager;
    private $design;
    private $themeProvider;
    private $assetRepository;
    private $logger;
    private $store;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $this->storeManager = $this->createStub(StoreManagerInterface::class);
        $this->design = $this->createStub(DesignInterface::class);
        $this->themeProvider = $this->createStub(ThemeProviderInterface::class);
        $this->assetRepository = $this->createStub(AssetRepository::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->store = $this->createStub(Store::class);
        $this->store->method('getId')->willReturn(self::STORE_ID);
        $this->store->method('getFrontendName')->willReturn('  Demo Store  ');
        $this->store->method('getBaseUrl')->willReturn('https://shop.test/');
    }

    private function viewModel(): CheckoutLogo
    {
        return new CheckoutLogo(
            $this->scopeConfig,
            $this->storeManager,
            $this->design,
            $this->themeProvider,
            $this->assetRepository,
            $this->logger
        );
    }

    private function config(array $values): void
    {
        $map = [];
        foreach ($values as $path => $value) {
            $map[] = [$path, ScopeInterface::SCOPE_STORE, self::STORE_ID, $value];
        }
        $this->scopeConfig->method('getValue')->willReturnMap($map);
    }

    private function logoBlock(string $src, string $alt, int $width, int $height): Logo
    {
        $logo = $this->createStub(Logo::class);
        $logo->method('getLogoSrc')->willReturn($src);
        $logo->method('getLogoAlt')->willReturn($alt);
        $logo->method('getLogoWidth')->willReturn($width);
        $logo->method('getLogoHeight')->willReturn($height);

        return $logo;
    }

    private function theme(?int $id, string $area = 'frontend', string $path = 'Vendor/storefront'): ThemeInterface
    {
        $theme = $this->createStub(ThemeInterface::class);
        $theme->method('getId')->willReturn($id);
        $theme->method('getArea')->willReturn($area);
        $theme->method('getThemePath')->willReturn($path);

        return $theme;
    }

    public function testConfiguredLogoUsesBlockSourceAndDimensions(): void
    {
        $this->themeProvider = $this->createMock(ThemeProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->config([
            'design/header/logo_src' => 'stores/2/logo.png',
        ]);
        $this->themeProvider->expects($this->never())->method('getThemeById');
        $this->logger->expects($this->never())->method('warning');

        $result = $this->viewModel()->getLogo($this->logoBlock('https://shop.test/media/logo.png', ' Brand ', 120, 40));

        $this->assertSame(
            ['src' => 'https://shop.test/media/logo.png', 'alt' => 'Brand', 'width' => 120, 'height' => 40],
            $result
        );
    }

    public function testUnconfiguredLogoUsesStorefrontThemeLogoAndResetsDimensions(): void
    {
        $this->themeProvider = $this->createMock(ThemeProviderInterface::class);
        $this->assetRepository = $this->createMock(AssetRepository::class);
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->config([
            'design/header/logo_src' => '',
            'design/theme/theme_id' => (string) self::THEME_ID,
        ]);
        $this->design->method('getDesignTheme')->willReturn($this->theme(1));
        $this->themeProvider->expects($this->once())
            ->method('getThemeById')
            ->with(self::THEME_ID)
            ->willReturn($this->theme(self::THEME_ID));
        $this->assetRepository->expects($this->once())
            ->method('getUrlWithParams')
            ->with('images/logo.svg', ['area' => 'frontend', 'theme' => 'Vendor/storefront'])
            ->willReturn('https://shop.test/static/frontend/Vendor/storefront/en_US/images/logo.svg');

        $result = $this->viewModel()->getLogo($this->logoBlock('ignored.png', 'Alt', 200, 80));

        $this->assertSame(
            [
                'src' => 'https://shop.test/static/frontend/Vendor/storefront/en_US/images/logo.svg',
                'alt' => 'Alt',
                'width' => 0,
                'height' => 0,
            ],
            $result
        );
    }

    public function testZeroLogoSourceTriggersThemeLookupWithoutBlock(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->config([
            'design/header/logo_src' => '0',
            'design/theme/theme_id' => (string) self::THEME_ID,
            'design/header/logo_alt' => ' Config Alt ',
        ]);
        $this->design->method('getDesignTheme')->willReturn(null);
        $this->themeProvider->method('getThemeById')->willReturn($this->theme(self::THEME_ID));
        $this->assetRepository->method('getUrlWithParams')->willReturn('/logo.svg');

        $result = $this->viewModel()->getLogo();

        $this->assertSame(['src' => '/logo.svg', 'alt' => 'Config Alt', 'width' => 0, 'height' => 0], $result);
    }

    public function testFallsBackToBlockWhenThemeIdMissing(): void
    {
        $this->themeProvider = $this->createMock(ThemeProviderInterface::class);
        $this->assetRepository = $this->createMock(AssetRepository::class);
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->config([
            'design/header/logo_src' => null,
            'design/theme/theme_id' => '0',
        ]);
        $this->themeProvider->expects($this->never())->method('getThemeById');
        $this->assetRepository->expects($this->never())->method('getUrlWithParams');

        $result = $this->viewModel()->getLogo($this->logoBlock('block.svg', '', 50, 20));

        $this->assertSame(['src' => 'block.svg', 'alt' => 'Demo Store', 'width' => 50, 'height' => 20], $result);
    }

    public function testFallsBackToBlockWhenCurrentThemeIsTheStorefrontTheme(): void
    {
        $this->themeProvider = $this->createMock(ThemeProviderInterface::class);
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->config([
            'design/header/logo_src' => '',
            'design/theme/theme_id' => (string) self::THEME_ID,
        ]);
        $this->design->method('getDesignTheme')->willReturn($this->theme(self::THEME_ID));
        $this->themeProvider->expects($this->never())->method('getThemeById');

        $result = $this->viewModel()->getLogo($this->logoBlock('own.svg', 'Own', 10, 5));

        $this->assertSame(['src' => 'own.svg', 'alt' => 'Own', 'width' => 10, 'height' => 5], $result);
    }

    public function testFallsBackToBlockWhenThemeIsNotFrontend(): void
    {
        $this->assetRepository = $this->createMock(AssetRepository::class);
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->config([
            'design/header/logo_src' => '',
            'design/theme/theme_id' => (string) self::THEME_ID,
        ]);
        $this->design->method('getDesignTheme')->willReturn($this->theme(1));
        $this->themeProvider->method('getThemeById')->willReturn($this->theme(self::THEME_ID, 'adminhtml'));
        $this->assetRepository->expects($this->never())->method('getUrlWithParams');

        $result = $this->viewModel()->getLogo($this->logoBlock('own.svg', 'Own', 10, 5));

        $this->assertSame('own.svg', $result['src']);
        $this->assertSame(10, $result['width']);
    }

    public function testFallsBackToBlockWhenThemeIsMissing(): void
    {
        $this->assetRepository = $this->createMock(AssetRepository::class);
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->config([
            'design/header/logo_src' => '',
            'design/theme/theme_id' => (string) self::THEME_ID,
        ]);
        $this->design->method('getDesignTheme')->willReturn($this->theme(1));
        $this->themeProvider->method('getThemeById')->willReturn($this->theme(null));
        $this->assetRepository->expects($this->never())->method('getUrlWithParams');

        $result = $this->viewModel()->getLogo($this->logoBlock('own.svg', 'Own', 10, 5));

        $this->assertSame('own.svg', $result['src']);
    }

    public function testReturnsNullWhenNoSourceCanBeResolved(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->storeManager->method('getStore')->willReturn($this->store);
        $this->config([
            'design/header/logo_src' => 'configured.png',
        ]);
        $this->logger->expects($this->never())->method('warning');

        $this->assertNull($this->viewModel()->getLogo());
        $this->assertNull($this->viewModel()->getLogo(new \stdClass()));
    }

    public function testReturnsNullAndLogsWhenStoreLookupFails(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->storeManager->method('getStore')->willThrowException(new \RuntimeException('no store'));
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Panth_CheckoutExtended: checkout logo fallback: no store');

        $this->assertNull($this->viewModel()->getLogo());
    }

    public function testGetHomeUrlReturnsStoreBaseUrl(): void
    {
        $this->storeManager->method('getStore')->willReturn($this->store);

        $this->assertSame('https://shop.test/', $this->viewModel()->getHomeUrl());
    }

    public function testGetHomeUrlFallsBackToSlash(): void
    {
        $this->storeManager->method('getStore')->willThrowException(new \RuntimeException('no store'));

        $this->assertSame('/', $this->viewModel()->getHomeUrl());
    }
}
