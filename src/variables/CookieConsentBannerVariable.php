<?php
/**
 * Cookie Consent Banner plugin for Craft CMS 3.x
 *
 * Add a configurable cookie consent banner to the website.
 *
 * @link      https://adigital.agency
 * @copyright Copyright (c) 2018 Mark @ A Digital
 */

namespace adigital\cookieconsentbanner\variables;

use adigital\cookieconsentbanner\CookieConsentBanner;
use adigital\cookieconsentbanner\CookieConsentBannerService;

use Craft;

/**
 * Cookie Consent Banner Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.cookieconsentbanner }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Mark @ A Digital
 * @package   CookieConsentBanner
 * @since     1.1.7
 */
class CookieConsentBannerVariable
{
    // Public Methods
    // =========================================================================
    
    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:
     *
     *     {{ craft.cookieconsentbanner.exampleVariable }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     *     {{ craft.cookieconsentbanner.exampleVariable(twigValue) }}
     *
     * @param null $optional
     * @return string
     */
    public function addBanner(): void 
    {
        if (!CookieConsentBanner::$plugin->getSettings()->auto_inject && CookieConsentBanner::$plugin->cookieConsentBannerService->validateRequestType() && !CookieConsentBanner::$plugin->cookieConsentBannerService->validateCookieConsentSet() && CookieConsentBanner::$plugin->cookieConsentBannerService->validateResponseType()) {
            CookieConsentBanner::$plugin->cookieConsentBannerService->renderCookieConsentBanner();
        }
    }
    
    /**
     * Get settings for the current site or a specific site
     *
     * @param int|null $siteId
     * @return array
     */
    public function getSettings($siteId = null): array
    {
        return CookieConsentBanner::$plugin->cookieConsentBannerService->getSiteSettings($siteId);
    }
    
    /**
     * Get global settings
     *
     * @return array
     */
    public function getGlobalSettings(): array
    {
        return CookieConsentBanner::$plugin->getSettings()->getGlobalSettings();
    }
    
    /**
     * Check if consent has been given for the current site
     *
     * @return bool
     */
    public function hasConsent(): bool
    {
        return CookieConsentBanner::$plugin->cookieConsentBannerService->validateCookieConsentSet();
    }
    
    /**
     * Get the cookie name for the current site or a specific site
     *
     * @param int|null $siteId
     * @return string
     */
    public function getCookieName($siteId = null): string
    {
        if ($siteId === null) {
            $site = Craft::$app->getSites()->getCurrentSite();
        } else {
            $site = Craft::$app->getSites()->getSiteById($siteId);
        }
        
        return 'cookieconsent_status_' . $site->handle;
    }
    
    /**
     * Get all available sites
     *
     * @return array
     */
    public function getAllSites(): array
    {
        return Craft::$app->getSites()->getAllSites();
    }
    
    /**
     * Get translated text for a specific key
     *
     * @param string $key
     * @param int|null $siteId
     * @return string
     */
    public function getText(string $key, $siteId = null): string
    {
        $settings = $this->getSettings($siteId);
        
        if (isset($settings[$key])) {
            return Craft::t('cookie-consent-banner', $settings[$key]);
        }
        
        return '';
    }
    
    /**
     * Check if the current request should show the banner
     *
     * @return bool
     */
    public function shouldShowBanner(): bool
    {
        // Check if auto inject is enabled
        $settings = $this->getSettings();
        if (!$settings['auto_inject']) {
            return false;
        }
        
        // Check request type
        if (!CookieConsentBanner::$plugin->cookieConsentBannerService->validateRequestType()) {
            return false;
        }
        
        // Check response type
        if (!CookieConsentBanner::$plugin->cookieConsentBannerService->validateResponseType()) {
            return false;
        }
        
        // Check if consent already given
        if ($this->hasConsent() && !$settings['revokable']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get JavaScript configuration for the current site
     *
     * @param int|null $siteId
     * @return array
     */
    public function getJsConfig($siteId = null): array
    {
        $settings = $this->getSettings($siteId);
        $site = $siteId ? Craft::$app->getSites()->getSiteById($siteId) : Craft::$app->getSites()->getCurrentSite();
        
        return [
            'cookieName' => $this->getCookieName($siteId),
            'palette' => [
                'popup' => [
                    'background' => $settings['palette_banner'],
                    'text' => $settings['palette_banner_text'],
                    'link' => $settings['palette_link']
                ],
                'button' => [
                    'background' => $settings['layout'] === 'wire' ? 'transparent' : $settings['palette_button'],
                    'text' => $settings['layout'] === 'wire' ? $settings['palette_button'] : $settings['palette_button_text'],
                    'border' => $settings['layout'] === 'wire' ? $settings['palette_button'] : null
                ],
                'highlight' => [
                    'background' => $settings['layout'] === 'wire' ? 'transparent' : $settings['palette_left_button_bg'],
                    'text' => $settings['layout'] === 'wire' ? $settings['palette_left_button_bg'] : $settings['palette_left_button_text'],
                    'border' => $settings['layout'] === 'wire' ? $settings['palette_left_button_bg'] : null
                ]
            ],
            'position' => $settings['position'] === 'toppush' ? 'top' : $settings['position'],
            'static' => $settings['position'] === 'toppush',
            'theme' => $settings['layout'],
            'type' => $settings['type'],
            'content' => [
                'message' => Craft::t('cookie-consent-banner', $settings['message']),
                'dismiss' => Craft::t('cookie-consent-banner', $settings['dismiss']),
                'link' => Craft::t('cookie-consent-banner', $settings['learn']),
                'href' => Craft::t('cookie-consent-banner', $settings['learn_more_link']),
                'allow' => Craft::t('cookie-consent-banner', $settings['allow']),
                'deny' => Craft::t('cookie-consent-banner', $settings['decline']),
                'target' => $settings['target']
            ],
            'revokable' => (bool)$settings['revokable'],
            'dismissOnScroll' => $settings['dismiss_on_scroll'] > 0 ? (int)$settings['dismiss_on_scroll'] : false,
            'dismissOnTimeout' => $settings['dismiss_on_timeout'] > 0 ? (int)$settings['dismiss_on_timeout'] * 1000 : false,
            'cookie' => [
                'name' => $this->getCookieName($siteId),
                'expiryDays' => $settings['expiry_days'] !== 0 ? (int)$settings['expiry_days'] : 365,
                'secure' => (bool)$settings['secure_only']
            ]
        ];
    }
}
