# Portal Akademik - image aplikasi (PHP + Apache)
FROM php:8.2-apache

# Ekstensi MySQL
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Salin kode aplikasi
COPY . /var/www/html/

# [BEFORE HARDENING] menampilkan error PHP ke browser (Information Disclosure).
# Saat hardening: display_errors=Off, log ke file.
RUN { \
      echo "display_errors=On"; \
      echo "display_startup_errors=On"; \
      echo "error_reporting=E_ALL & ~E_DEPRECATED & ~E_NOTICE"; \
    } > /usr/local/etc/php/conf.d/zz-dev.ini

EXPOSE 80
