#!/bin/sh
MYSQL_USER=root

mysqldump --add-drop-table -u $MYSQL_USER orion > orion_data.sql
