#!/usr/bin/env bash
# Ensure WordPress Apache rewrite rules exist for pretty permalinks.
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
HTACCESS="${PROJECT_ROOT}/src/.htaccess"

if [[ -f "${HTACCESS}" ]]; then
  echo "OK: ${HTACCESS} already exists"
  exit 0
fi

cat > "${HTACCESS}" <<'EOF'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
EOF

chmod 644 "${HTACCESS}"
echo "Created ${HTACCESS}"
