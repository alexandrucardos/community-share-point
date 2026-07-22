FROM nginx:1.25-alpine3.18

RUN apk update
RUN apk add --upgrade curl expat

RUN apk update

WORKDIR /var/www/comm/public/

ARG ENVIRONMENT

COPY ./docker/nginx/nginx.conf /etc/nginx/nginx.conf

ADD ./public/ .

CMD ["nginx", "-g", "daemon off;"]
