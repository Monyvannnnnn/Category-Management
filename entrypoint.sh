#!/bin/bash
set -e

# Start MariaDB service if installed in container
if command -v mariadb &> /dev/null || command -v mysql &> /dev/null; then
    echo "[Entrypoint] Starting MariaDB service..."
    service mariadb start || service mysql start || true
    
    # Wait for MySQL daemon socket to accept connections
    for i in {1..10}; do
        if mysqladmin ping &>/dev/null; then
            echo "[Entrypoint] MariaDB is up and running."
            break
        fi
        echo "[Entrypoint] Waiting for MariaDB to initialize ($i)..."
        sleep 1
    done

    # Ensure database & default root user exist
    mysql -e "CREATE DATABASE IF NOT EXISTS inventory;" || true
    mysql -e "CREATE DATABASE IF NOT EXISTS inventory_db;" || true
    mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '' WITH GRANT OPTION;" || true
    mysql -e "FLUSH PRIVILEGES;" || true
fi

# Execute passed container command (Apache foreground)
exec "$@"
