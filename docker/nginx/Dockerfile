# Stage 1: Build Vite assets
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
RUN npm rebuild
COPY . .
RUN npm run build || mkdir -p /app/public/build

# Stage 2: Nginx
FROM nginx:1.25-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Static files from source + compiled Vite assets
COPY public /var/www/public
COPY --from=assets /app/public/build /var/www/public/build

EXPOSE 80
