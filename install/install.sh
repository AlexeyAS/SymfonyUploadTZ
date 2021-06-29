#!/usr/bin/env bash

echo ""
echo "Установка окружения"
echo "-------------------"
echo ""

### sudo apt install composer
#sudo apt install postgresql-client-common
#sudo apt-get install postgresql-client
### sudo apt-get install docker-compose

sudo docker-compose up -d || exit

sudo chmod -R 777 ../var/
sudo chmod -R 777 ../logs/
sudo chmod -R 777 ../data/

echo ""
echo "Ожидание 5 сек"
echo ""
sleep 5

echo ""
echo "Установка приложения"
echo "--------------------"
echo ""

sudo docker exec php8.0-upload composer install --ignore-platform-reqs
sudo chmod -R 777 ../vendor/
sudo chmod -R 777 ../public/

echo ""
echo "Создание БД"
echo "Заведение пользователя. Назначение прав"
echo "--------------------"
echo ""

./db_create.sh

echo ""
echo "Установка завершена."
echo "--------------------"
echo "Login:    admin"
echo "Password: admin"
echo ""
