# Stage 1: Build Hugo site
FROM klakegg/hugo:ext-alpine AS builder

WORKDIR /app
COPY . .
RUN hugo --minify

# Stage 2: Serve with nginx
FROM nginx:alpine

# Verwijder standaard nginx config en voeg je eigen toe
RUN rm /etc/nginx/conf.d/default.conf
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Kopieer de gegenereerde site naar nginx webroot
COPY --from=builder /app/public /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
