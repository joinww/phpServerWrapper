## phpServerWrapper

### 此框架可以直接利用pcntl、posix进行进程部署

#### 只有简单的start、stop命令

#### 依赖
* posix
* pcntl

#### 启动文件配置

* start.php

```
//加载文件

require_once './wrapper/wrapper.php';

Wrapper::$daemonize = true;

Wrapper::$masterPidPath = '/tmp/testShell.pid';

Wrapper::$file = "./shell/testShell.php";

Wrapper::run();
```

#### 启动start.php
```
php ./start.php start
```