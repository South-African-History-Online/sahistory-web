#!/bin/sh

cd /docker/beierholm-intra/

# Start backup
echo "Starting backup"
docker exec -it web /usr/local/bin/drush status
docker exec -it web /usr/local/bin/drush sql-dump --gzip --extra-dump="--column-statistics=0" --result-file=/var/www/.novi/backups/beierholm-$(date +%Y-%m-%d-%H-%M-%S).sql
echo "Backup done"

# Start deploy
git pull
docker-compose pull
docker-compose create
docker-compose start

# Wait till Redis is healthy
until docker exec -it redis redis-cli ping; do echo "Waiting for Redis to be available..."; sleep 5; done

# Wait till MySQL is healthy
until docker exec -it web /usr/local/bin/drush sql:query 'SHOW TABLES;'; do echo "Waiting for DB to be available..."; sleep 5; done
echo "DB is available"

# Setup Solr
echo "Creating Beierholm Solr core"
docker exec -it solr /opt/solr/bin/solr create_core -c beierholm -d /opt/solr/server/solr/beierholm -n beierholm

# Setup Drupal
echo "Run database and configuration updates"
docker exec -it web /usr/local/bin/drush status
docker exec -it web /usr/local/bin/drush updb -y
docker exec -it web /usr/local/bin/drush cim -y
docker exec -it web /usr/local/bin/drush locale-check
docker exec -it web /usr/local/bin/drush locale-update
docker exec -it web /usr/local/bin/drush cr

# Cleanup old backups
echo "Cleaning up old database backups older than a month"
find /docker/beierholm-intra/.novi/backups -name "beierholm-*.sql.gz" -type f -mtime +31 -delete

# Cleanup old images
echo "Cleaning up dangling images - keeping image from the last 31 days"
docker image prune -f --filter "dangling=true" --filter "until=$(date -d '-31 days' --rfc-3339=second | awk '{print $1"T"$2}')"
