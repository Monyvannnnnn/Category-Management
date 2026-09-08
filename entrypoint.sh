#!/bin/bash
set -e

# Ensure mysqld runtime directories exist with correct permissions
mkdir -p /var/run/mysqld /var/lib/mysql /var/log/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql /var/log/mysql
chmod 777 /var/run/mysqld

# Initialize MariaDB data directory if not already initialized
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "[Entrypoint] Initializing MariaDB data directory..."
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql --skip-name-resolve > /dev/null 2>&1 || true
fi

# Start MariaDB service
echo "[Entrypoint] Starting MariaDB service..."
if command -v service &> /dev/null; then
    service mariadb start || service mysql start || mysqld_safe --user=mysql &
else
    mysqld_safe --user=mysql &
fi

# Wait for MySQL daemon to accept connections
echo "[Entrypoint] Waiting for MariaDB to start..."
for i in {1..15}; do
    if mysqladmin ping --silent 2>/dev/null; then
        echo "[Entrypoint] MariaDB is up and running!"
        break
    fi
    sleep 1
done

# Ensure database & default root user exist
mysql -e "CREATE DATABASE IF NOT EXISTS inventory;" 2>/dev/null || true
mysql -e "CREATE DATABASE IF NOT EXISTS inventory_db;" 2>/dev/null || true
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '' WITH GRANT OPTION;" 2>/dev/null || true
mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' IDENTIFIED BY '' WITH GRANT OPTION;" 2>/dev/null || true
mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true

# Execute passed container command (Apache foreground)
exec "$@"
