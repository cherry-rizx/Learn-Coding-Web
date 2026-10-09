#!/usr/bin/env bash
set -e # berhenti kalau ada perintah gagal

URL="https://${CODESPACE_NAME}-8080.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}/" # link web codespace ini

cat > .env <<EOF # buat .env otomatis
CI_ENVIRONMENT = development
app.baseURL = '${URL}'
database.default.hostname = db
database.default.database = belajar_coding
database.default.username = ci
database.default.password = cipass
database.default.DBDriver = MySQLi
database.default.port = 3306
EOF

until mariadb-admin ping -h db -u ci -pcipass --silent; do sleep 2; done # tunggu MariaDB siap

php spark migrate --all
php spark db:seed DatabaseSeeder # isi bab, level, konten