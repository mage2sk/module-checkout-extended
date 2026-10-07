<?php
declare(strict_types=1);

namespace Panth\CheckoutExtended\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Header\Logo;
use Psr\Log\LoggerInterface;

class CheckoutLogo implements ArgumentInterface
{
    private const XML_LOGO_SRC = 'design/header/logo_src';
    private const XML_LOGO_ALT = 'design/header/logo_alt';
    private const XML_THEME_ID = 'design/theme/theme_id';
    private const DEFAULT_LOGO_FILE = 'images/logo.svg';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly DesignInterface $design,
        private readonly ThemeProviderInterface $themeProvider,
        private readonly AssetRepository $assetRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getLogo($logoBlock = null): ?array
    {
        try {
            $store = $this->storeManager->getStore();
            $storeId = (int) $store->getId();
            $src = '';
            $width = 0;
            $height = 0;

            if ($logoBlock instanceof Logo) {
                $width = (int) $logoBlock->getLogoWidth();
                $height = (int) $logoBlock->getLogoHeight();
            }

            $configuredSrc = (string) $this->scopeConfig->getValue(self::XML_LOGO_SRC, ScopeInterface::SCOPE_STORE, $storeId);
            if ($configuredSrc === '' || $configuredSrc === '0') {
                $src = $this->getStorefrontThemeLogoUrl($storeId);
                if ($src !== '') {
                    $width = 0;
                    $height = 0;
                }
            }

            if ($src === '' && $logoBlock instanceof Logo) {
                $src = (string) $logoBlock->getLogoSrc();
            }

            if ($src === '') {
                return null;
            }

            $alt = $logoBlock instanceof Logo ? trim((string) $logoBlock->getLogoAlt()) : '';
            if ($alt === '') {
                $alt = trim((string) $this->scopeConfig->getValue(self::XML_LOGO_ALT, ScopeInterface::SCOPE_STORE, $storeId));
            }
            if ($alt === '') {
                $alt = trim((string) $store->getFrontendName());
            }

            return ['src' => $src, 'alt' => $alt, 'width' => $width, 'height' => $height];
        } catch (\Throwable $e) {
            $this->logger->warning('Panth_CheckoutExtended: checkout logo fallback: ' . $e->getMessage());
            return null;
        }
    }

    public function getHomeUrl(): string
    {
        try {
            return (string) $this->storeManager->getStore()->getBaseUrl();
        } catch (\Throwable $e) {
            return '/';
        }
    }

    private function getStorefrontThemeLogoUrl(int $storeId): string
    {
        $themeId = (int) $this->scopeConfig->getValue(self::XML_THEME_ID, ScopeInterface::SCOPE_STORE, $storeId);
        if ($themeId <= 0) {
            return '';
        }

        $current = $this->design->getDesignTheme();
        if ($current && (int) $current->getId() === $themeId) {
            return '';
        }

        $theme = $this->themeProvider->getThemeById($themeId);
        if (!$theme || !$theme->getId() || $theme->getArea() !== 'frontend') {
            return '';
        }

        return $this->assetRepository->getUrlWithParams(
            self::DEFAULT_LOGO_FILE,
            ['area' => 'frontend', 'theme' => $theme->getThemePath()]
        );
    }
}
