# phpServerWrapper
之前部署php服务一直都是shell脚本 

此框架可以直接利用posix进行进程部署

依赖posix.so

配置参数
start.php

//加载文件
require_once './wrapper/wrapper.php';
Wrapper::$daemonize = true;
Wrapper::$masterPidPath = '/tmp/testShell.pid';
Wrapper::$file = "./shell/testShell.php";
Wrapper::run();


启动start.php
php ./start.php start
