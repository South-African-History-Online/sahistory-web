#!/bin/sh

cd /web

# Start backup
#echo "Starting backup"
docker run -it -v /web:/app --network internet --env-file /web/.env --rm sahistory/jobs:latest /usr/local/bin/drush status
docker run -it -v /web:/app --network internet --env-file /web/.env --rm sahistory/jobs:latest /usr/local/bin/drush sql-dump --gzip --result-file=/app/.novi/backups/saho-$(date +%Y-%m-%d-%H-%M-%S).sql
#echo "Backup done"

# Start deploy
git pull
docker-compose pull
docker-compose create
docker-compose start

# Update composer packages
docker run -it -v /web:/app --network internet --env-file /web/.env --rm sahistory/jobs:latest composer install

# Wait till Redis is healthy
until docker exec -it redis redis-cli ping; do echo "Waiting for Redis to be available..."; sleep 5; done

# Wait till MySQL is healthy
until docker run -it -v /web:/app --network internet --env-file /web/.env --rm sahistory/jobs:latest /usr/local/bin/drush sql:query 'SHOW TABLES;'; do echo "Waiting for DB to be available..."; sleep 5; done
echo "DB is available"

# Setup Solr
echo "Creating SAHO Solr core"
docker exec -it solr /opt/solr/bin/solr create_core -c saho -d /opt/solr/server/solr/saho -n saho

# Setup Drupal
echo "Run database and configuration updates"
docker run -it -v /web:/app --network internet --env-file /web/.env --rm sahistory/jobs:latest /usr/local/bin/drush status
docker run -it -v /web:/app --network internet --env-file /web/.env --rm sahistory/jobs:latest /usr/local/bin/drush updb -y
docker run -it -v /web:/app --network internet --env-file /web/.env --rm sahistory/jobs:latest /usr/local/bin/drush cim -y
docker run -it -v /web:/app --network internet --env-file /web/.env --rm sahistory/jobs:latest /usr/local/bin/drush locale-check
docker run -it -v /web:/app --network internet --env-file /web/.env --rm sahistory/jobs:latest /usr/local/bin/drush locale-update
docker run -it -v /web:/app --network internet --env-file /web/.env --rm sahistory/jobs:latest /usr/local/bin/drush cr

# Cleanup old backups
echo "Cleaning up old database backups older than a month"
find /web/.novi/backups -name "saho-*.sql.gz" -type f -mtime +31 -delete

# Cleanup old images
echo "Cleaning up dangling images - keeping image from the last 31 days"
docker image prune -f --filter "dangling=true" --filter "until=$(date -d '-31 days' --rfc-3339=second | awk '{print $1"T"$2}')"
