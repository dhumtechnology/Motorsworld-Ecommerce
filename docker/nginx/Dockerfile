FROM nginx:alpine

RUN apk add --no-cache netcat-openbsd

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/wait-for-fpm.sh /wait-for-fpm.sh
RUN chmod +x /wait-for-fpm.sh

CMD ["/wait-for-fpm.sh"]
