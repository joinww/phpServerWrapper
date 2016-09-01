<?php
//加载配置文件
require_once './wrapper/wrapper.php';

Wrapper::$daemonize = true;
Wrapper::$file = "./shell/testShell.php";

Wrapper::run();