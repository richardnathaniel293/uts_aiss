# Portal Akademik - image aplikasi (PHP + Apache) - VERSI HARDENED
FROM php:8.2-apache

# Ekstensi MySQL (mysqli). Argon2 & OpenSSL sudah tersedia di image resmi.
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Nonaktifkan signature server Apache (fix T8 - info disclosure)
RUN { \
      echo "ServerTokens Prod"; \
      echo "ServerSignature Off"; \
      echo "TraceEnable Off"; \
    } > /etc/apache2/conf-available/harden.conf \
 && a2enconf harden \
 && a2enmod headers

# Salin kode aplikasi
COPY . /var/www/html/

# Direktori log aplikasi (dibaca Fail2ban via volume bersama)
RUN mkdir -p /var/log/portal && touch /var/log/portal/app.log \
 && chown -R www-data:www-data /var/log/portal

# php.ini hardening (fix T8 + hardening umum)
RUN { \
      echo "display_errors=Off"; \
      echo "display_startup_errors=Off"; \
      echo "log_errors=On"; \
      echo "error_log=/var/log/portal/app.log"; \
      echo "expose_php=Off"; \
      echo "session.cookie_httponly=1"; \
      echo "session.cookie_samesite=Lax"; \
      echo "session.use_strict_mode=1"; \
    } > /usr/local/etc/php/conf.d/zz-harden.ini

EXPOSE 80
