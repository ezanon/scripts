#!/bin/bash
# Lista de sites WordPress
SITES=(
    "/sites/igc/www/"
    "/sites/museu/www/"
    "/sites/ppegeo/www/portal"
    "/sites/didatico/"
)

for SITE in "${SITES[@]}"; do
    if [ -d "$SITE" ] && [ -f "$SITE/wp-config.php" ]; then
        cd "$SITE" && /usr/local/bin/wp cron event run --due-now > /dev/null 2>&1
    fi
done
