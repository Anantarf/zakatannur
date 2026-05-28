<?php

/**
 * Zakat Application Configuration
 * 
 * Centralized configuration untuk semua magic numbers dan settings
 * yang sebelumnya hardcoded di service, controller, dan support classes.
 */

return [
    /**
     * Timezone untuk aplikasi Zakat
     * Override dari config/app.php jika diperlukan
     */
    'timezone' => env('ZAKAT_TIMEZONE', config('app.timezone', 'Asia/Jakarta')),

    /**
     * Transaction Number (no_transaksi) Configuration
     * Format: TRX-YYYYMMDD-####
     */
    'transaction' => [
        // Prefix untuk no_transaksi (e.g., TRX-)
        'prefix' => 'TRX-',
        
        // Separator antara date dan sequence
        'date_separator' => '-',
        
        // Separator antara sequence number dan padding
        'sequence_separator' => '-',
        
        // Jumlah digit untuk sequence number (e.g., 0001, 0002)
        'sequence_digits' => 4,
        
        // Retry attempts untuk collision resolution
        'retry_attempts' => 5,
        
        // Delay antar retry dalam milliseconds
        'retry_delay_ms' => 100,

        // Maximum number of batch items in a single save request
        'max_batch_items' => 30,
    ],

    /**
     * Cache Configuration
     */
    'cache' => [
        // TTL untuk app settings cache (detik)
        'app_settings_ttl' => 3600,

        // Lock timeout untuk transaction sync (detik)
        'lock_timeout_seconds' => 30,
        
        // TTL untuk public summary cache (detik)
        'public_summary_ttl' => 3600,
        
        // TTL untuk public home stats cache (detik)
        'public_home_stats_ttl' => 3600,
        
        // TTL untuk annual defaults cache (detik)
        'annual_defaults_ttl' => 86400,
    ],

    /**
     * Data Retention Configuration
     */
    'retention' => [
        // Hari untuk hard delete trashed transactions
        'purge_days' => env('ZAKAT_PURGE_DAYS', 30),
        
        // Enable/disable auto-purge di production
        'enable_auto_purge' => env('ZAKAT_ENABLE_AUTO_PURGE', true),
    ],

    /**
     * Annual Setting Fallback Defaults
     */
    'annual_defaults' => [
        'fitrah_cash_per_jiwa' => 50000,
        'fitrah_beras_per_jiwa' => 2.5,
        'fidyah_per_hari' => 30000,
        'fidyah_beras_per_hari' => 0.75,
    ],

    /**
     * Transaction Status Constants
     */
    'statuses' => [
        'VALID' => 'valid',
        'VOID' => 'void',
    ],

    /**
     * Transaction Categories
     */
    'categories' => [
        'FITRAH' => 'fitrah',
        'FIDYAH' => 'fidyah',
        'KHUSUS' => 'khusus',
    ],

    /**
     * Transaction Methods (Metode Pembayaran)
     */
    'methods' => [
        'TUNAI' => 'tunai',
        'TRANSFER' => 'transfer',
        'ONLINE' => 'online',
    ],

    /**
     * User Roles
     */
    'roles' => [
        'STAFF' => 'staff',
        'ADMIN' => 'admin',
        'SUPER_ADMIN' => 'super_admin',
    ],

    /**
     * API & Export Configuration
     */
    'export' => [
        // Maximum export batch size
        'max_batch_size' => 1000,
        
        // Pagination per page untuk large exports
        'pagination_size' => 500,

        // Maximum execution time for export jobs (seconds)
        'execution_time_seconds' => 300,

        // Memory limit for large spreadsheet exports
        'memory_limit' => '512M',

        // Yearly export needs more room than daily export
        'yearly_execution_time_seconds' => 600,
        'yearly_memory_limit' => '1024M',
    ],

    /**
     * Dashboard Configuration
     */
    'dashboard' => [
        // Default chart range in days
        'default_active_days' => 14,

        // Allowed chart ranges in days
        'allowed_active_days' => [7, 14, 30],
    ],

    /**
     * Year Bounds
     */
    'year_bounds' => [
        'min' => 2000,
        'max' => 2100,
    ],

    /**
     * Public Refresh Configuration
     */
    'public_refresh' => [
        // Default refresh interval when setting is not configured
        'default_seconds' => 15,

        // Allowed non-zero interval range for public refresh settings
        'min_seconds' => 10,
        'max_seconds' => 60,

        // Maximum upper bound accepted by the settings form
        'form_max_seconds' => 600,
    ],

    /**
     * Audit Logging
     */
    'audit' => [
        // Enable audit logging
        'enabled' => env('ZAKAT_AUDIT_ENABLED', true),
        
        // Log sensitive actions
        'log_sensitive_actions' => true,
    ],

    /**
     * Feature Flags
     */
    'features' => [
        // Enable off-season (bulan Ramadan) special handling
        'enable_ramadan_handling' => true,
        
        // Enable transaction correction via trash bin
        'enable_trash_bin' => true,
        
        // Enable batch import
        'enable_batch_import' => true,
    ],

    /**
     * Validation String Length Limits
     */
    'validation' => [
        // User/Profile fields
        'user_name_max' => 100,
        'username_max' => 50,
        'phone_max' => 50,
        
        // Muzakki/Payer fields
        'muzakki_name_max' => 255,
        'muzakki_name_edit_max' => 150,
        'payer_name_max' => 255,
        'payer_phone_max' => 50,
        'muzakki_phone_max' => 30,
        
        // Search/Query fields
        'search_query_max' => 100,
        
        // Reason/Description fields
        'reason_min' => 5,
        'reason_max' => 255,
        'description_max' => 255,
        
        // File upload limits (in bytes)
        'template_file_max_bytes' => 10240,
    ],
];
