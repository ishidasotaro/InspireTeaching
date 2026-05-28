# PHP環境 - 軽量化版
FROM php:8.3-fpm-alpine

# タイムゾーン設定
ENV TZ=Asia/Tokyo

# 必要最小限のパッケージをインストール
RUN apk add --no-cache \
    # タイムゾーン設定用
    tzdata \
    # MySQL接続用
    mysql-client \
    && rm -rf /var/cache/apk/*

# PHP拡張機能のインストール（必要最小限）
RUN docker-php-ext-install \
    pdo_mysql \
    mysqli

# Composerのインストール（ライブラリ管理）
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
