# syntax=docker/dockerfile:1

FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY index.html tsconfig*.json vite.config.ts ./
COPY public ./public
COPY src ./src
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY api/composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

FROM php:8.4-fpm-alpine AS api
WORKDIR /var/www/api
RUN apk add --no-cache icu-libs \
    && apk add --no-cache --virtual .build-deps icu-dev \
    && docker-php-ext-install intl \
    && apk del .build-deps
COPY api ./
COPY --from=vendor /app/vendor ./vendor
RUN mkdir -p var/cache var/log var/integrations \
    && chown -R www-data:www-data var
ENV APP_ENV=prod APP_DEBUG=0
USER www-data

FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=frontend /app/dist /var/www/frontend
COPY api/public /var/www/api/public
EXPOSE 80
