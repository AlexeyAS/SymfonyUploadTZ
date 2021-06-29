#!/usr/bin/env bash

POSTGRES_DB='upload'
POSTGRES_USER='upload_su'
POSTGRES_PASSWORD='4R2s1EpZ'
POSTGRES_PORT=5431

psql -p $POSTGRES_PORT -h localhost -U postgres << EOF
create database $POSTGRES_DB;
create user $POSTGRES_USER with encrypted password '$POSTGRES_PASSWORD';
grant all privileges on database $POSTGRES_DB to $POSTGRES_USER;
ALTER DATABASE $POSTGRES_DB SET timezone TO 'Europe/Moscow';
EOF

echo ""
echo "Выполнение миграции"
echo "--------------------"
sudo docker exec php8.0-upload ./bin/console doctrine:migrations:migrate
echo ""