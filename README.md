# slim-swoole-service
use slim as a Microservices with swoole

php框架服务化的通用方案，兼容原来的http接口，可以将原有的http接口瞬间服务化

* 下载依赖 slim扩展 ```composer install```
* http服务，可以在nginx配置 root指向web目录
* 启动service
  ```
  php worker.php
  ```
* 这样一个接口就可以同时以http方式和service方式提供服务了
