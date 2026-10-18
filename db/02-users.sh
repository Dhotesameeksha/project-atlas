#!/bin/bash
# Creates least-privilege DB users. Passwords come from environment variables.
# atlas_ro (used by the SQL tool) can read ONLY the reports catalogue: no figures, no clients.
set -e
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" atlas <<EOSQL
CREATE USER IF NOT EXISTS 'atlas_app'@'%' IDENTIFIED BY '${MYSQL_APP_PASSWORD}';
GRANT SELECT, INSERT, UPDATE ON atlas.* TO 'atlas_app'@'%';
CREATE USER IF NOT EXISTS 'atlas_ro'@'%' IDENTIFIED BY '${MYSQL_RO_PASSWORD}';
GRANT SELECT ON atlas.reports TO 'atlas_ro'@'%';
FLUSH PRIVILEGES;
EOSQL
