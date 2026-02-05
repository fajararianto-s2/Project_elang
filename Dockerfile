FROM php:8.2-apache

# Install extension PHP untuk MySQL (wajib biar koneksi.php jalan)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set izin akses folder agar bisa upload file/gambar
RUN chown -R www-data:www-data /var/www/html

# Aktifkan mode rewrite Apache (opsional tapi bagus buat jaga-jaga)
RUN a2enmod rewrite