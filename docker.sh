#!/bin/sh

docker run -v `pwd`:/var/www/html -p 80:80 -ti web_14.04 
