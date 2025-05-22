<?php
namespace adigital\cookieconsentbanner\migrations;

use adigital\cookieconsentbanner\CookieConsentBanner;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\MigrationHelper;
use craft\services\Plugins;

class m240522_000000_add_multisite_support extends Migration
{
    // Public Methods
    // =========================================================================
    
    public function safeUp() : void
    {
        $plugin = CookieConsentBanner::$plugin;
        $settings = CookieConsentBanner::$plugin->getSettings();
        
        // Get all sites
        $sites = Craft::$app->getSites()->getAllSites();
        
        // Initialize site settings array if it doesn't exist
        if (!isset($settings->siteSettings) || !is_array($settings->siteSettings)) {
            $settings->siteSettings = [];
        }
        
        // Get current global settings to use as defaults
        $globalSettings = [
            'message' => $settings->message ?? '',
            'dismiss' => $settings->dismiss ?? '',
            'learn' => $settings->learn ?? '',
            'learn_more_link' => $settings->learn_more_link ?? '',
            'allow' => $settings->allow ?? '',
            'decline' => $settings->decline ?? '',
            'palette_banner' => $settings->palette_banner ?? '',
            'palette_banner_text' => $settings->palette_banner_text ?? '',
            'palette_button' => $settings->palette_button ?? '',
            'palette_button_text' => $settings->palette_button_text ?? '',
            'palette_link' => $settings->palette_link ?? '',
            'palette_left_button_bg' => $settings->palette_left_button_bg ?? '',
            'palette_left_button_text' => $settings->palette_left_button_text ?? '',
        ];
        
        // Create default site settings for each site
        foreach ($sites as $site) {
            if (!isset($settings->siteSettings[$site->id])) {
                // Initialize with empty array - sites will inherit global settings by default
                $settings->siteSettings[$site->id] = [];
                
                // Example: Set up default translations for common languages
                if ($site->language === 'fr' || $site->language === 'fr-CA') {
                    $settings->siteSettings[$site->id] = [
                        'message' => 'Ce site utilise des cookies pour améliorer votre expérience.',
                        'dismiss' => 'J\'accepte',
                        'learn' => 'En savoir plus',
                        'allow' => 'Autoriser',
                        'decline' => 'Refuser',
                    ];
                }
            }
        }
        
        // Update the plugin's settings in the project config
        Craft::$app->getProjectConfig()->set(
            Plugins::CONFIG_PLUGINS_KEY . '.' . $plugin->handle . '.settings', 
            $settings->toArray()
        );
        
        // Migrate existing cookies to site-specific names if needed
        $this->migrateCookies();
    }
    
    public function safeDown() : bool
    {
        $plugin = CookieConsentBanner::$plugin;
        $settings = CookieConsentBanner::$plugin->getSettings();
        
        // Remove site settings
        if (isset($settings->siteSettings)) {
            unset($settings->siteSettings);
        }
        
        // Update the plugin's settings in the project config
        Craft::$app->getProjectConfig()->set(
            Plugins::CONFIG_PLUGINS_KEY . '.' . $plugin->handle . '.settings', 
            $settings->toArray()
        );
        
        return true;
    }
    
    /**
     * Migrate existing cookies to site-specific format
     */
    private function migrateCookies()
    {
        // This would typically be done via JavaScript on the frontend
        // Adding a note here for documentation purposes
        
        $script = '
        // Cookie migration script to be added to frontend
        (function() {
            var oldCookie = document.cookie.match(/cookieconsent_status=([^;]+)/);
            if (oldCookie && oldCookie[1]) {
                var sites = ' . json_encode(array_map(function($site) {
                    return $site->handle;
                }, Craft::$app->getSites()->getAllSites())) . ';
                
                sites.forEach(function(siteHandle) {
                    var newCookieName = "cookieconsent_status_" + siteHandle;
                    if (!document.cookie.match(new RegExp(newCookieName + "="))) {
                        document.cookie = newCookieName + "=" + oldCookie[1] + "; path=/; max-age=" + (365 * 24 * 60 * 60);
                    }
                });
            }
        })();
        ';
        
        // Store this script for later use if needed
        Craft::info('Cookie migration script prepared for frontend implementation', __METHOD__);
    }
}
