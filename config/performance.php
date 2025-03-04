<?php
// Cache settings
define('CACHE_ENABLED', true);
define('PAGE_CACHE_TTL', 300); // 5 minutes
define('QUERY_CACHE_TTL', 600); // 10 minutes

// Query performance thresholds
define('SLOW_QUERY_THRESHOLD', 1.0); // seconds

// Pagination defaults
define('ITEMS_PER_PAGE', 20);

// Rate limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Asset versioning
define('ASSET_VERSION', '1.0.0');

// Asset optimization
define('COMPRESS_OUTPUT', true);
define('MINIFY_HTML', true);
define('GZIP_COMPRESSION', true);

// Database optimization
define('DB_MAX_CONNECTIONS', 10);
define('DB_PERSISTENT_CONNECTIONS', true);
define('PREPARED_STMT_CACHE_SIZE', 100); 