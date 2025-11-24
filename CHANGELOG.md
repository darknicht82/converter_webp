# Changelog - WebP Converter System

## [2025-11-24] - WordPress Plugin v1.1.4 & Dashboard Integration

### WordPress Plugin Fixes

#### v1.1.4
- **Fixed**: Fatal error `Cannot redeclare logConversion()` by wrapping function in `function_exists` check
- **Fixed**: Restore action now properly cleans up backup files and metadata after successful restoration
- **Fixed**: Dashboard now queries `conversion_logs` table instead of `conversion_events` for accurate metrics
- **Improved**: Dashboard stats now calculate actual bandwidth savings using `webp_size` vs `original_size`

#### v1.1.3
- **Fixed**: Restore backup path resolution with intelligent fallback logic
- **Improved**: Plugin now searches for backup files in current directory if stored path is invalid
- **Fixed**: Backup file paths are automatically updated when found via fallback

#### v1.1.2
- **Fixed**: 404 error on restore by removing HTTP status code from error responses
- **Fixed**: Corrupted main plugin file `webp-converter-bridge.php`
- **Added**: Plugin versioning system for better tracking and rollback capabilities

#### v1.1.1
- **Fixed**: Critical syntax errors in `admin.js` causing global JavaScript breakage
- **Improved**: File manager AJAX calls now have proper `.fail()` handlers for error reporting
- **Optimized**: Bulk conversion batch processing delay increased to 2000ms to prevent 502 errors
- **Fixed**: File manager nonce corrected to use `wcbAdmin.fileNonce`

#### v1.1.0
- **Added**: File Manager tab with separate views for WebP images and backups
- **Added**: Restore, Delete Backup, and Delete File actions
- **Improved**: Settings page reorganized into multi-panel layout
- **Added**: "Test Connection" button integrated into API Connection panel
- **Added**: "Costo por imagen" field implementation
- **Optimized**: `ajax_restore_backup` with `set_time_limit(300)` and `ignore_user_abort(true)`
- **Fixed**: Script caching issues by versioning `admin.js` with `WCB_PLUGIN_VERSION`

### Dashboard & Integration

#### Integration Database
- **Added**: `logIntegrationConversion()` function to save conversions to `conversion_logs` table
- **Fixed**: Dashboard queries updated to read from `conversion_logs` instead of `conversion_events`
- **Fixed**: `logs-data.php` require path corrected from `includes/` to `lib/`
- **Improved**: Stats calculation now includes actual converted bytes and savings

#### API Improvements
- **Fixed**: API routing for `?action=log_conversion` POST requests now works correctly
- **Added**: Action parameter check before image conversion logic
- **Verified**: WordPress plugin conversions now log to database successfully

### File Structure
```
webp/
├── api.php                          # Main API endpoint (needs routing fix)
├── lib/
│   ├── integration-db.php           # Database functions (logIntegrationConversion added)
│   └── integration-dashboard.php    # Dashboard queries (updated to use conversion_logs)
├── webp-wordpress/
│   └── logs-data.php                # Logs endpoint (path fixed)
└── wordpress-plugin/
    └── webp-converter-bridge/       # Plugin v1.1.4
        ├── webp-converter-bridge.php
        ├── includes/
        │   ├── class-wcb-admin.php  # Admin interface & AJAX handlers
        │   └── class-wcb-converter.php
        └── assets/
            └── admin.js             # Frontend JavaScript (syntax fixed)
```

### Migration Notes
- Clients upgrading from v1.1.0 to v1.1.4 should clear browser cache
- No database migrations required
- Backup files remain compatible across versions

### Next Steps
1. Fix API routing for `log_conversion` endpoint
2. Test end-to-end conversion logging from WordPress to dashboard
3. Verify dashboard metrics update in real-time
4. Consider adding conversion history export feature
