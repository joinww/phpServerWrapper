<?php

class Helper{
    public static $count = 2;
    public static $daemonize = true;  
    public static $pidFile = 'helper.pid';
    public static $logFile='./helper.log';    

    private static $_masterPid;
    private static $_pidMap = array();
  

    /**
     * 设置进程名称
     * @param [type] $title [description]
     */
    private static function setProcessName($title){
        if(function_exists("cli_set_process_title")){
            @cli_set_process_title($title);
        }
        elseif(extension_loaded('proctitle') && function_exists('setproctitle'))
        {
            @setproctitle($title);
        }
    }

    public static function runAll(){
        self::init();
        self::parseCommand();
        self::daemonize();
        self::installSignal();
        self::saveMasterPid();
        self::forkWorkers();
        self::info();
        self::resetStd();
        self::monitor();
    }

    private static function init(){
        self::setProcessName("Helper:Master Process");
    }

    private static function parseCommand(){
        global $argv;

        $cmd = $argv[1];

        $masterPid = @file_get_contents(self::$pidFile);
    
        switch($cmd){
            case 'start':
                if($masterPid >0){
                    exit("服务已经启动了，请先停止服务\n");
                }
                break;
            case 'stop':
                self::stop();
                break;
            case 'reload':
                posix_kill($masterPid,SIGUSR1);
                exit;
            default:
                exit("please input {start|stop|reload} \n");
        }
    }

    private static function stop(){
        @unlink(self::$pidFile);
        exec("ps aux|grep Helper:|grep -v 'grep Helper:'|awk '{print $2}'|xargs kill -9");
    }

    private static function daemonize(){
        if(!self::$daemonize){
            return;
        }
        $pid = pcntl_fork();
        if($pid == -1 ){
            throw new \Exception("Fork Error\n");
        }
        if($pid > 0){
            exit(0);
        }

        if(posix_setsid() == -1){
            throw new \Exception("posix setsid error\n");
        }

        $pid = pcntl_fork();
        if($pid == -1){
            throw new \Exception("fork error\n");
        }
        elseif($pid != 0){
            exit(0);
        }
    }

    private static function installSignal(){
        pcntl_signal(SIGINT,array('Helper','signalHandler'),false);
        pcntl_signal(SIGUSR1,array('Helper','signalHandler'),false);
        pcntl_signal(SIGUSR2,array('Helper','signalHandler'),false);
    }

    private static function signalHandler($signal){
        switch($signal){
            //stop
            case SIGINT:
                self::stopAll();
                break;
            //reload
            case SIGUSR1:
                self::reload();
                break;
            //status
            case SIGUSR2:
                break;
        }
    }


    /**
     * reload
     */
    private static function reload(){
        if(self::$_masterPid == posix_getpid()){
            //主进程
            foreach(self::$_pidMap as $pid){
                //posix_kill($pid,SIGUSR1);
            }
        }
    }

    private static function forkWorkers(){
        for($i = 0;$i<self::$count;$i++){
            self::_forkWorker();
        }
    }

    private static function _forkWorker(){
        $pid = pcntl_fork();
        if($pid > 0){
            self::$_pidMap[]=$pid;
        }elseif($pid == 0){
            self::setProcessName("Helper:Process");
            self::resetStd();
            //子进程do something
            self::doSth();
            exit(200);
        }
    }
    
    private static function doSth(){
        while(1){
            pcntl_signal_dispatch();
            //echo "";
            echo "i am doSth before @ ",date('Y-m-d H:i:s'),"\n";
            sleep(20);
            echo "i am doSth after @ ",date('Y-m-d H:i:s'),"\n";
        }
    }

    private static function saveMasterPid(){
        self::$_masterPid = posix_getpid();
        if(@file_put_contents(self::$pidFile,self::$_masterPid) === false){
            throw new \Exception('can not save pid to '.self::$pidFile."\n");
        }
    }

    private static function info(){
        echo "--------------------------------------------\n";
        echo "\033[30;47m","This is Helper","\033[0m","\n";
        echo "Master Pid:",self::$_masterPid,"\n";
        echo "Child Pid:",implode(",",self::$_pidMap),"\n";
        echo "--------------------------------------------\n";
    }

    private static function resetStd(){
        if(!self::$daemonize){
            return;
        }

        global $STDOUT,$STDERR;

        $handle = @fopen(self::$logFile,'a');
        if($handle){
            @fclose(STDOUT);
            @fclose(STDERR);
            unset($handle);
            $STDOUT=fopen(self::$logFile,'a');
            $STDERR=fopen(self::$logFile,'a');
        }else{
            throw new \Exception("不能打开stdOutFile ". self::$logFile."\n");
        }
    }

    private static function monitor(){
        while(1){
            pcntl_signal_dispatch();
            $status = 0;
            $pid = pcntl_wait($status,WUNTRACED);
            pcntl_signal_dispatch();
            //
            //sleep(10);
        }
    }

}