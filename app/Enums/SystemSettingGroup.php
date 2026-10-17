<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;
use App\Enums\Concerns\HasEnumValues;

enum SystemSettingGroup: string
{
    use HasEnumOptions, HasEnumValues;

    case Information = 'information';
    case SocialMedia = 'social_media';
    case Mail = 'mail';
    case Seo = 'seo';
    case Security = 'security';
    case VisitorAnalytics = 'visitor_analytics';
    case IpInfo = 'ipinfo';
    case Monitoring = 'monitoring';
    case Storage = 'storage';

    public function label(): string
    {
        return match ($this) {
            self::Information => __('system-settings.group_label.Information'),
            self::SocialMedia => __('system-settings.group_label.SocialMedia'),
            self::Mail => __('system-settings.group_label.Mail'),
            self::Seo => __('system-settings.group_label.Seo'),
            self::Security => __('system-settings.group_label.Security'),
            self::VisitorAnalytics => __('system-settings.group_label.VisitorAnalytics'),
            self::IpInfo => __('system-settings.group_label.IpInfo'),
            self::Monitoring => __('system-settings.group_label.Monitoring'),
            self::Storage => __('system-settings.group_label.Storage'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Information => __('system-settings.group_description.Information'),
            self::SocialMedia => __('system-settings.group_description.SocialMedia'),
            self::Mail => __('system-settings.group_description.Mail'),
            self::Seo => __('system-settings.group_description.Seo'),
            self::Security => __('system-settings.group_description.Security'),
            self::VisitorAnalytics => __('system-settings.group_description.VisitorAnalytics'),
            self::IpInfo => __('system-settings.group_description.IpInfo'),
            self::Monitoring => __('system-settings.group_description.Monitoring'),
            self::Storage => __('system-settings.group_description.Storage'),
        };
    }

    /**
     * Setting keys whose values are write-only: never sent back to the browser.
     *
     * @return list<string>
     */
    public static function secretKeys(): array
    {
        $keys = [];

        foreach (self::cases() as $group) {
            foreach ($group->fields() as $field) {
                if ($field['type'] === 'secret') {
                    $keys[] = $field['key'];
                }
            }
        }

        return $keys;
    }

    /**
     * The fixed set of setting keys managed under this group. Input `type` is
     * one of text, email, tel, url, image (media library path), secret (masked text), gtag (Google tag ID), digits, toggle (0/1), integer or select (with `options`) and drives both validation and the form.
     *
     * @return list<array{key: string, label: string, type: string, placeholder: string, default?: bool, note?: string, min?: int, max?: int, options?: list<array{value: string, label: string}>}>
     */
    public function fields(): array
    {
        return match ($this) {
            self::Information => [
                ['key' => 'contact_address', 'label' => __('system-settings.contact_address.label'), 'type' => 'text', 'placeholder' => __('system-settings.contact_address.placeholder'), 'note' => __('system-settings.contact_address.note')],
                ['key' => 'contact_timezone', 'label' => __('system-settings.contact_timezone.label'), 'type' => 'text', 'placeholder' => __('system-settings.contact_timezone.placeholder')],
                ['key' => 'contact_email', 'label' => __('system-settings.contact_email.label'), 'type' => 'email', 'placeholder' => 'nama@contoh.com'],
                ['key' => 'contact_whatsapp', 'label' => __('system-settings.contact_whatsapp.label'), 'type' => 'tel', 'placeholder' => '08xxxxxxxxxx'],
                ['key' => 'contact_website', 'label' => 'Website', 'type' => 'url', 'placeholder' => 'https://'],
            ],
            self::SocialMedia => [
                ['key' => 'social_facebook', 'label' => 'Facebook', 'type' => 'url', 'placeholder' => 'https://www.facebook.com/...'],
                ['key' => 'social_instagram', 'label' => 'Instagram', 'type' => 'url', 'placeholder' => 'https://www.instagram.com/...'],
                ['key' => 'social_twitter', 'label' => 'Twitter', 'type' => 'url', 'placeholder' => 'https://x.com/...'],
                ['key' => 'social_youtube', 'label' => 'Youtube', 'type' => 'url', 'placeholder' => 'https://youtube.com/@...'],
                ['key' => 'social_linkedin', 'label' => 'LinkedIn', 'type' => 'url', 'placeholder' => 'https://linkedin.com/in/...'],
                ['key' => 'social_tiktok', 'label' => 'TikTok', 'type' => 'url', 'placeholder' => 'https://tiktok.com/@...'],
            ],
            self::Mail => [
                ['key' => 'contact_recipient_email', 'label' => __('system-settings.contact_recipient_email.label'), 'type' => 'email', 'placeholder' => 'nama@contoh.com', 'note' => __('system-settings.contact_recipient_email.note')],
            ],
            self::Seo => [
                ['key' => 'seo_image', 'label' => __('system-settings.seo_image.label'), 'type' => 'image', 'placeholder' => '', 'note' => __('system-settings.seo_image.note')],
                ['key' => 'seo_description', 'label' => __('system-settings.seo_description.label'), 'type' => 'text', 'placeholder' => __('system-settings.seo_description.placeholder'), 'note' => __('system-settings.seo_description.note')],
                ['key' => 'seo_keywords', 'label' => __('system-settings.seo_keywords.label'), 'type' => 'text', 'placeholder' => __('system-settings.seo_keywords.placeholder'), 'note' => __('system-settings.seo_keywords.note')],
                ['key' => 'seo_author', 'label' => __('system-settings.seo_author.label'), 'type' => 'text', 'placeholder' => __('system-settings.seo_author.placeholder')],
                ['key' => 'seo_twitter', 'label' => __('system-settings.seo_twitter.label'), 'type' => 'text', 'placeholder' => '@namaakun'],
                ['key' => 'google_analytics_id', 'label' => 'Google Analytics ID', 'type' => 'gtag', 'placeholder' => 'G-XXXXXXXXXX', 'note' => __('system-settings.google_analytics_id.note')],
                ['key' => 'facebook_app_id', 'label' => 'Facebook App ID', 'type' => 'digits', 'placeholder' => '1234567890'],
                ['key' => 'google_site_verification', 'label' => __('system-settings.google_site_verification.label'), 'type' => 'text', 'placeholder' => __('system-settings.google_site_verification.placeholder')],
                ['key' => 'bing_site_verification', 'label' => __('system-settings.bing_site_verification.label'), 'type' => 'text', 'placeholder' => __('system-settings.bing_site_verification.placeholder')],
                ['key' => 'yandex_site_verification', 'label' => __('system-settings.yandex_site_verification.label'), 'type' => 'text', 'placeholder' => __('system-settings.yandex_site_verification.placeholder')],
                ['key' => 'baidu_site_verification', 'label' => __('system-settings.baidu_site_verification.label'), 'type' => 'text', 'placeholder' => __('system-settings.baidu_site_verification.placeholder')],
                ['key' => 'facebook_domain_verification', 'label' => __('system-settings.facebook_domain_verification.label'), 'type' => 'text', 'placeholder' => __('system-settings.facebook_domain_verification.placeholder')],
                ['key' => 'pinterest_site_verification', 'label' => __('system-settings.pinterest_site_verification.label'), 'type' => 'text', 'placeholder' => __('system-settings.pinterest_site_verification.placeholder')],
                ['key' => 'tiktok_site_verification', 'label' => __('system-settings.tiktok_site_verification.label'), 'type' => 'text', 'placeholder' => __('system-settings.tiktok_site_verification.placeholder')],
                ['key' => 'naver_site_verification', 'label' => __('system-settings.naver_site_verification.label'), 'type' => 'text', 'placeholder' => __('system-settings.naver_site_verification.placeholder')],
            ],
            self::Security => [
                ['key' => 'turnstile_site_key', 'label' => 'Turnstile Site Key', 'type' => 'text', 'placeholder' => '0x4AAAAAAA...', 'note' => __('system-settings.turnstile_site_key.note')],
                ['key' => 'turnstile_secret_key', 'label' => 'Turnstile Secret Key', 'type' => 'secret', 'placeholder' => '0x4AAAAAAA...', 'note' => __('system-settings.turnstile_secret_key.note')],
            ],
            self::VisitorAnalytics => [
                ['key' => 'visitor_tracking_enabled', 'default' => true, 'label' => __('system-settings.visitor_tracking_enabled.label'), 'type' => 'toggle', 'placeholder' => '', 'note' => __('system-settings.visitor_tracking_enabled.note')],
                ['key' => 'visitor_bot_policy', 'label' => __('system-settings.visitor_bot_policy.label'), 'type' => 'select', 'placeholder' => '', 'options' => [['value' => 'separate', 'label' => __('system-settings.visitor_bot_policy.option_separate')], ['value' => 'exclude', 'label' => __('system-settings.visitor_bot_policy.option_exclude')]], 'note' => __('system-settings.visitor_bot_policy.note')],
                ['key' => 'visitor_store_ip', 'default' => true, 'label' => __('system-settings.visitor_store_ip.label'), 'type' => 'toggle', 'placeholder' => '', 'note' => __('system-settings.visitor_store_ip.note')],
                ['key' => 'visitor_excluded_paths', 'label' => __('system-settings.visitor_excluded_paths.label'), 'type' => 'text', 'placeholder' => __('system-settings.visitor_excluded_paths.placeholder'), 'note' => __('system-settings.visitor_excluded_paths.note')],
                ['key' => 'visitor_session_minutes', 'label' => __('system-settings.visitor_session_minutes.label'), 'type' => 'integer', 'placeholder' => '30', 'min' => 5, 'max' => 1440, 'note' => __('system-settings.visitor_session_minutes.note')],
                ['key' => 'visitor_retention_days', 'label' => __('system-settings.visitor_retention_days.label'), 'type' => 'integer', 'placeholder' => '180', 'min' => 7, 'max' => 3650, 'note' => __('system-settings.visitor_retention_days.note')],
            ],
            self::IpInfo => [
                ['key' => 'ipinfo_enabled', 'label' => __('system-settings.ipinfo_enabled.label'), 'type' => 'toggle', 'placeholder' => '', 'note' => __('system-settings.ipinfo_enabled.note')],
                ['key' => 'ipinfo_plan', 'label' => __('system-settings.ipinfo_plan.label'), 'type' => 'select', 'placeholder' => '', 'options' => [['value' => 'lite', 'label' => __('system-settings.ipinfo_plan.option_lite')], ['value' => 'core', 'label' => __('system-settings.ipinfo_plan.option_core')]], 'note' => __('system-settings.ipinfo_plan.note')],
                ['key' => 'ipinfo_token', 'label' => __('system-settings.ipinfo_token.label'), 'type' => 'secret', 'placeholder' => __('system-settings.ipinfo_token.placeholder'), 'note' => __('system-settings.ipinfo_token.note')],
                ['key' => 'ipinfo_cache_ttl_days', 'label' => __('system-settings.ipinfo_cache_ttl_days.label'), 'type' => 'integer', 'placeholder' => '30', 'min' => 1, 'max' => 90, 'note' => __('system-settings.ipinfo_cache_ttl_days.note')],
            ],
            self::Monitoring => [
                ['key' => 'sentry_enabled', 'label' => __('system-settings.sentry_enabled.label'), 'type' => 'toggle', 'placeholder' => '', 'note' => __('system-settings.sentry_enabled.note')],
                ['key' => 'sentry_backend_dsn', 'label' => __('system-settings.sentry_backend_dsn.label'), 'type' => 'secret', 'placeholder' => 'https://<key>@o0.ingest.us.sentry.io/0', 'note' => __('system-settings.sentry_backend_dsn.note')],
                ['key' => 'sentry_frontend_dsn', 'label' => __('system-settings.sentry_frontend_dsn.label'), 'type' => 'url', 'placeholder' => 'https://<key>@o0.ingest.us.sentry.io/0', 'note' => __('system-settings.sentry_frontend_dsn.note')],
                ['key' => 'sentry_environment', 'label' => 'Environment', 'type' => 'text', 'placeholder' => 'production', 'note' => __('system-settings.sentry_environment.note')],
                ['key' => 'sentry_release', 'label' => 'Release', 'type' => 'text', 'placeholder' => 'catatancakadi@1.0.0', 'note' => __('system-settings.sentry_release.note')],
                ['key' => 'sentry_traces_sample_percent', 'label' => __('system-settings.sentry_traces_sample_percent.label'), 'type' => 'integer', 'placeholder' => '10', 'min' => 0, 'max' => 100, 'note' => __('system-settings.sentry_traces_sample_percent.note')],
                ['key' => 'sentry_profiles_sample_percent', 'label' => __('system-settings.sentry_profiles_sample_percent.label'), 'type' => 'integer', 'placeholder' => '0', 'min' => 0, 'max' => 100, 'note' => __('system-settings.sentry_profiles_sample_percent.note')],
                ['key' => 'sentry_enable_logs', 'label' => __('system-settings.sentry_enable_logs.label'), 'type' => 'toggle', 'placeholder' => '', 'note' => __('system-settings.sentry_enable_logs.note')],
            ],
            self::Storage => [
                ['key' => 'storage_driver', 'label' => __('system-settings.storage_driver.label'), 'type' => 'select', 'placeholder' => '', 'options' => [['value' => 'local', 'label' => __('system-settings.storage_driver.option_local')], ['value' => 's3', 'label' => __('system-settings.storage_driver.option_s3')]], 'note' => __('system-settings.storage_driver.note')],
                ['key' => 'storage_s3_endpoint', 'label' => 'Endpoint', 'type' => 'url', 'placeholder' => 'https://<account>.r2.cloudflarestorage.com', 'note' => __('system-settings.storage_s3_endpoint.note')],
                ['key' => 'storage_s3_region', 'label' => 'Region', 'type' => 'text', 'placeholder' => 'us-east-1', 'note' => __('system-settings.storage_s3_region.note')],
                ['key' => 'storage_s3_bucket', 'label' => 'Bucket', 'type' => 'text', 'placeholder' => 'catatan-cakadi'],
                ['key' => 'storage_s3_access_key', 'label' => 'Access Key ID', 'type' => 'secret', 'placeholder' => 'AKIA...', 'note' => __('system-settings.storage_secret.note')],
                ['key' => 'storage_s3_secret_key', 'label' => 'Secret Access Key', 'type' => 'secret', 'placeholder' => '********', 'note' => __('system-settings.storage_secret.note')],
                ['key' => 'storage_s3_url', 'label' => __('system-settings.storage_s3_url.label'), 'type' => 'url', 'placeholder' => 'https://cdn.contoh.com', 'note' => __('system-settings.storage_s3_url.note')],
                ['key' => 'storage_s3_path_style', 'label' => __('system-settings.storage_s3_path_style.label'), 'type' => 'toggle', 'placeholder' => '', 'note' => __('system-settings.storage_s3_path_style.note')],
            ],
        };
    }
}
