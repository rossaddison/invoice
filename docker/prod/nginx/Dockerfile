FROM nginx:1.31.1-alpine3.23-slim

RUN apk add --no-cache curl

COPY docker/prod/nginx/nginx.conf /etc/nginx/conf.d/default.conf

WORKDIR /app/public

USER nginx

HEALTHCHECK --interval=30s --timeout=5s --start-period=1s CMD curl --fail http://127.0.0.1:8080/health || exit 1
