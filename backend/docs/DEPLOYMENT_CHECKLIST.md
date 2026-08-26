# E-SKLD Backend Production Deployment & Runbook Checklist

**Target System**: E-SKLD Backend v1.0.0-GA  
**Target Environment**: Linux (Ubuntu 22.04 LTS / RHEL 8+) / Windows Server 2022  
**Runtime**: PHP 8.1+ / 8.2 (with `ext-json`, `ext-mysqli`, `ext-mbstring`, `ext-openssl`)  
**Web Server**: Nginx / Apache with `mod_rewrite`  
**Database**: MySQL 8.0.x / MariaDB 10.6+ (`eskld_db`)  

---

## 1. Pre-Deployment Infrastructure Prerequisites

- [x] **PHP Extensions**:
  - `php-fpm` installed and configured with `pm.max_children = 50+`
  - `php-mysqli`, `php-mbstring`, `php-openssl`, `php-json`, `php-curl` verified active.
- [x] **Database Connectivity**:
  - Database `eskld_db` provisioned with `utf8mb4_unicode_ci` character set.
  - Dedicated least-privilege DB user created with `SELECT`, `INSERT`, `UPDATE` grants (no `DROP TABLE` or `ALTER TABLE` in production).
- [x] **Environment Variables (`.env`)**:
  - `CI_ENVIRONMENT = production`
  - `app.baseURL = 'https://api.eskld.menpan.go.id/'`
  - `database.default.hostname = 'db-cluster.internal'`
  - `database.default.database = 'eskld_db'`
  - `database.default.username = 'eskld_app'`
  - `database.default.password = '<STRONG_VAULT_SECRET>'`
  - `JWT_SECRET = '<256_BIT_CRYPTOGRAPHIC_KEY>'`
  - `JWT_EXPIRY_SECONDS = 3600`

---

## 2. Web Server Configuration (Nginx)

```nginx
server {
    listen 443 ssl http2;
    server_name api.eskld.menpan.go.id;
    root /var/www/eskld/public;
    index index.php;

    ssl_certificate /etc/ssl/certs/eskld.crt;
    ssl_certificate_key /etc/ssl/private/eskld.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Security Headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 3. Deployment Steps

1. **Clone & Dependencies**:
   ```bash
   git clone https://github.com/KemenPANRB/student-management-api.git /var/www/eskld
   cd /var/www/eskld
   composer install --no-dev --optimize-autoloader
   ```
2. **File Permissions**:
   ```bash
   chown -R www-data:www-data /var/www/eskld/writable
   chmod -R 775 /var/www/eskld/writable
   ```
3. **Smoke Verification**:
   ```bash
   curl -I https://api.eskld.menpan.go.id/health
   # Expected: HTTP/1.1 200 OK
   ```
4. **Automated Verification**:
   ```bash
   vendor/bin/phpunit --testsuite Unit
   # Expected: 195 tests, 708 assertions, 0 errors, 0 failures (100% PASS)
   ```

---

## 4. Monitoring & Operational Health Checks

- **Health Check Endpoint**: `GET /health` (monitored by Prometheus / Uptime Kuma every 30 seconds).
- **Log Rotation**: Ensure `/var/www/eskld/writable/logs/` is rotated via `logrotate`.
- **Database Backups**: Automated daily physical backup (`mysqldump` / Percona XtraBackup) with binary logging enabled for point-in-time recovery.
