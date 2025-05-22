<?php
/**
 * Cookie Consent Banner plugin for Craft CMS 4.x
 *
 * Add a configurable cookie consent banner to the website with multi-site support.
 *
 * @link      https://adigital.agency
 * @copyright Copyright (c) 2018 Mark @ A Digital
 */

namespace adigital\cookieconsentbanner\services;

use adigital\cookieconsentbanner\CookieConsentBanner;
use adigital\cookieconsentbanner\assetbundles\cookieconsentbanner\CookieConsentBannerAsset;

use Craft;
use craft\base\Component;

/**
 * CookieConsentBannerService Service
 *
 * All of your plugin's business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Mark @ A Digital
 * @package   CookieConsentBanner
 * @since     1.0.0
 */
class CookieConsentBannerService extends Component
{
    // Public Methods
    // =========================================================================
    
    /**
     * Get site-specific settings
     *
     * @param int|null $siteId
     * @return array
     */
    public function getSiteSettings($siteId = null)
    {
        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }
        
        $settings = CookieConsentBanner::$plugin->getSettings();
        
        // Check if we have site-specific settings
        if (isset($settings->siteSettings[$siteId])) {
            // Merge site-specific settings with global settings
            $siteSettings = array_merge(
                $settings->toArray(),
                $settings->siteSettings[$siteId]
            );
            return $siteSettings;
        }
        
        // Return global settings if no site-specific settings exist
        return $settings->toArray();
    }
    
    /**
     * Render the cookie consent banner with site-specific settings
     *
     * @return bool
     */
    public function renderCookieConsentBanner() : bool
    {
        $currentSite = Craft::$app->getSites()->getCurrentSite();
        $siteSettings = $this->getSiteSettings($currentSite->id);
        
        Craft::$app->getView()->registerAssetBundle(CookieConsentBannerAsset::class);
        
        // Use site-specific cookie name to allow different consents per site
        $cookieName = 'cookieconsent_status_' . $currentSite->handle;
        
        $script = '
            if ((navigator.doNotTrack != "1" && '. ($siteSettings['honour_do_not_track_header'] ? $siteSettings['honour_do_not_track_header'] : 0) .') || !'. ($siteSettings['honour_do_not_track_header'] ? $siteSettings['honour_do_not_track_header'] : 0) .') {
                window.addEventListener("load", function(){
                    window.cookieconsent.initialise({
                        "palette": {
                            "popup": {
                                "background": "'. $this->ensureHexColor($siteSettings['palette_banner']) .'",
                                "text": "'. $this->ensureHexColor($siteSettings['palette_banner_text']) .'",
                                "link": "'. $this->ensureHexColor($siteSettings['palette_link']) .'"
                            },
                            "button": {
                                "background":  "'. $siteSettings['layout'] .'" === "wire" ? "transparent" :  "'. $this->ensureHexColor($siteSettings['palette_button']) .'",
                                "text": "'. $siteSettings['layout'] .'" === "wire" ? "'. $this->ensureHexColor($siteSettings['palette_button']) .'" : "'. $this->ensureHexColor($siteSettings['palette_button_text']) .'",
                                "border":  "'. $siteSettings['layout'] .'" === "wire" ? "'. $this->ensureHexColor($siteSettings['palette_button']) .'" : undefined
                            },
                            "highlight": {
                                "background":  "'. $siteSettings['layout'] .'" === "wire" ? "transparent" :  "'. $this->ensureHexColor($siteSettings['palette_left_button_bg']) .'",
                                "text": "'. $siteSettings['layout'] .'" === "wire" ? "'. $this->ensureHexColor($siteSettings['palette_left_button_bg']) .'" : "'. $this->ensureHexColor($siteSettings['palette_left_button_text']) .'",
                                "border":  "'. $siteSettings['layout'] .'" === "wire" ? "'. $this->ensureHexColor($siteSettings['palette_left_button_bg']) .'" : undefined
                            }
                        },
                        "position": "'. $siteSettings['position'] .'" === "toppush" ? "top" : "'. $siteSettings['position'] .'",
                        "static": "'. $siteSettings['position'] .'" === "toppush",
                        "theme": "'. $siteSettings['layout'] .'",
                        "type": "'. $siteSettings['type'] .'",
                        "cookie": {
                            "name": "'. $cookieName .'",
                            "expiryDays":'. $siteSettings['expiry_days'] .' !== 0 ? '. $siteSettings['expiry_days'] .' : 365,
                            "secure":'. ($siteSettings['secure_only'] ? $siteSettings['secure_only'] : 0) . ' === 1 ? true : false
                        },
                        "content": {
                            "message": "'. $this->getTranslatedContent($siteSettings['message'], $currentSite) .'&nbsp;",
                            "dismiss": "'. $this->getTranslatedContent($siteSettings['dismiss'], $currentSite) .'",
                            "link": "'. $this->getTranslatedContent($siteSettings['learn'], $currentSite) .'",
                            "href": "'. $this->getTranslatedContent($siteSettings['learn_more_link'], $currentSite) .'",
                            "allow":"'. $this->getTranslatedContent($siteSettings['allow'], $currentSite) .'",
                            "deny":"'. $this->getTranslatedContent($siteSettings['decline'], $currentSite) .'",
                            "target":"'. $siteSettings['target'] .'"
                        },
                        "revokable":'. ($siteSettings['revokable'] ? $siteSettings['revokable'] : 0) .' === 1 ? true : false,
                        "dismissOnScroll":'. $siteSettings['dismiss_on_scroll'] .' > 0 ? '. $siteSettings['dismiss_on_scroll'] .' : false,
                        "dismissOnTimeout":'. $siteSettings['dismiss_on_timeout'] .' > 0 ? ('. $siteSettings['dismiss_on_timeout'] .' * 1000) : false,
                        onInitialise: function (status) {
                            var type = this.options.type;
                            var didConsent = this.hasConsented();
                            if (type == "opt-in" && didConsent) {
                                // enable cookies
                                if (typeof optInCookiesConsented === "function") {
                                    optInCookiesConsented();
                                    console.log("Opt in cookies consented");
                                } else {
                                    console.log("Opt in function not defined!");
                                }
                            }
                            if (type == "opt-out" && !didConsent) {
                                // disable cookies
                                if (typeof optOutCookiesNotConsented === "function") {
                                    optOutCookiesNotConsented();
                                    console.log("Opt out cookies not consented");
                                } else {
                                    console.log("Opt out function not defined!");
                                }
                            }
                        },
                        onStatusChange: function(status, chosenBefore) {
                            var type = this.options.type;
                            var didConsent = this.hasConsented();
                            if (type == "opt-in" && didConsent) {
                                // enable cookies
                                if (typeof optInCookiesConsented === "function") {
                                    optInCookiesConsented();
                                    console.log("Opt in cookies consented");
                                } else {
                                    console.log("Opt in function not defined!");
                                }
                            }
                            if (type == "opt-out" && !didConsent) {
                                // disable cookies
                                if (typeof optOutCookiesNotConsented === "function") {
                                    optOutCookiesNotConsented();
                                    console.log("Opt out cookies not consented");
                                } else {
                                    console.log("Opt out function not defined!");
                                }
                            }
                        },
                        onRevokeChoice: function() {
                            var type = this.options.type;
                            if (type == "opt-in") {
                                // disable cookies
                                if (typeof optInCookiesRevoked === "function") {
                                    optInCookiesRevoked();
                                    console.log("Opt in cookies revoked");
                                } else {
                                    console.log("Opt in revoked function not defined!");
                                }
                            }
                            if (type == "opt-out") {
                                // enable cookies
                                if (typeof optOutCookiesRevoked === "function") {
                                    optOutCookiesRevoked();
                                    console.log("Opt out cookies revoked");
                                } else {
                                    console.log("Opt out revoked function not defined!");
                                }
                            }
                        }
                    });
                });
            } else if ('. ($siteSettings['honour_do_not_track_header'] ? $siteSettings['honour_do_not_track_header'] : 0) .') {
                // disable cookies
                if (typeof optOutCookiesNotConsented === "function") {
                    optOutCookiesNotConsented();
                    console.log("Opt out cookies not consented");
                } else {
                    console.log("Opt out function not defined!");
                }
            }
        ';
        
        Craft::$app->getView()->registerScript($script, 1, array(), "cookie-consent-banner-" . $currentSite->handle);
        
        return true;
    }
    
    /**
     * Validate request type
     *
     * @return bool
     */
    public function validateRequestType() : bool
    {
        if(Craft::$app->request->getIsCpRequest() || 
           Craft::$app->request->getIsConsoleRequest() || 
           (Craft::$app->request->hasMethod("getIsAjax") && Craft::$app->request->getIsAjax()) || 
           (Craft::$app->request->hasMethod("getIsLivePreview") && 
            (Craft::$app->request->getIsLivePreview() && 
             CookieConsentBanner::$plugin->getSettings()->disable_in_live_preview))) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate if cookie consent is already set for current site
     *
     * @return bool
     */
    public function validateCookieConsentSet() : bool
    {
        $currentSite = Craft::$app->getSites()->getCurrentSite();
        $cookieName = 'cookieconsent_status_' . $currentSite->handle;
        
        // Check both the site-specific cookie and the legacy cookie
        return isset($_COOKIE[$cookieName]) || isset($_COOKIE['cookieconsent_status']);
    }
    
    /**
     * Validate response type
     *
     * @return bool
     */
    public function validateResponseType() : bool
    {
        if(strpos(Craft::$app->response->format, 'template') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Ensure color value has # prefix
     *
     * @param string $color
     * @return string
     */
    private function ensureHexColor($color)
    {
        if (empty($color)) {
            return '';
        }
        
        return (substr($color, 0, 1) != "#" ? "#" : "") . $color;
    }
    
    /**
     * Get translated content based on site
     *
     * @param string|array $content
     * @param \craft\models\Site $site
     * @return string
     */
    private function getTranslatedContent($content, $site)
    {
        // If content is an array with site handles as keys
        if (is_array($content)) {
            if (isset($content[$site->handle])) {
                $translatedContent = $content[$site->handle];
            } elseif (isset($content[$site->language])) {
                $translatedContent = $content[$site->language];
            } else {
                // Fallback to first value or empty string
                $translatedContent = reset($content) ?: '';
            }
        } else {
            // If content is a translation key, translate it
            $translatedContent = Craft::t('cookie-consent-banner', $content);
        }
        
        // Clean up the content
        return str_replace(array("\n", "\r"), "", nl2br($translatedContent));
    }
}
