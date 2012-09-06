#!/bin/sh
MYSQL_USER=root

echo "DROP DATABASE IF EXISTS orion;" | mysql -u $MYSQL_USER
echo "CREATE DATABASE orion;" | mysql -u $MYSQL_USER

mysql -u $MYSQL_USER orion < orion_data.sql
