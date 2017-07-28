<?php
class Wrapper{
	const VERSION = "0.0.1";
	const RUNNING = "START";
	const STOP = "STOP";
	const RESTART = "RESTART";

	//是否已守护进程方式执行
	//默认debug调试模式
	public static $daemonize = false;
	//daemonize模式下日志路径
	public static $logFile = '';
	public static $file;

	//当前运行状态
	private static $status = '';

	//执行命令
	private static $cmd;
	//pid保存路径
	private static $pidFile = '';

	/**
	 * 执行命令
	 * @return [type] [description]
	 */
	public static function run(){
		self::init();
		self::parseCommand();
		self::execute();	
	}

	private static function init(){
		if(empty(self::$logFile)){
        	self::$logFile = __DIR__ . '/../wrapper.log';
        }
	}

	/**
	 * 解析命令
	 * @return [type] [description]
	 */
	private static function parseCommand(){		
		global $argv;

		if(!isset($argv[1])){
			exit("服务启动参数设置不正确 php ./start.php {start|stop|restart|status} file \n");
		}		

		self::$cmd = $argv[1];

		if(isset($argv[2])){
			self::$file = $argv[2];			
		}

		if(empty(self::$file)){
			exit("执行脚本未配置\n");
		}

		//自动生成pid文件名,这里不要改动
		$pathInfo = pathinfo(self::$file);
		$masterPidPath = str_replace('.','_',$pathInfo['filename']);		
		self::$pidFile = sys_get_temp_dir()."/$masterPidPath.pid";
	}

	/**
	 * 脚本执行
	 * @return [type] [description]
	 */
	private static function execute(){

		switch (self::$cmd) {
			case 'start':
				self::start();
				break;
			case 'stop':
				self::stop();
				break;
			case 'restart':
				self::restart();
				break;
			case 'status':
				self::status();
				break;			
			default:
				exit("服务启动参数设置不正确 php ./start.php {start|stop|restart|status} file \n");
				break;
		}	
	}

	/**
	 * 启动服务
	 * @param  [type] $file [description]
	 * @return [type]       [description]
	 */
	private static function start(){
		self::$status = Wrapper::RUNNING;

		self::isStart();
		self::displayInfo();
		//判断是否已守护进程执行	
		self::resetStd();
		self::daemonize();
		self::saveMasterPid();

		require_once self::$file;
	}

	/**
	 * 终止服务
	 * @return [type] [description]
	 */
	private static function stop(){
		
		$masterPid = self::getPidFromFile();

		if( $masterPid >0 ){
			shell_exec("kill -9 $masterPid");

	        //清空pid
	        @unlink(self::$pidFile);
		
			self::$status = Wrapper::STOP;
			self::displayInfo();
		}else{
			exit("服务未启动，请先启动服务\n");
		}
	}

	/**
	 * 获取已经存在的pid
	 * @return [type] [description]
	 */
	private static function getPidFromFile(){
		if(file_exists(self::$pidFile)){			
			return file_get_contents(self::$pidFile);
		}
	}

	/**
	 * 重启服务
	 * @return [type] [description]
	 */
	private static function restart(){
		self::stop();
		sleep(2);
		self::start();
	}

	/**
	 * 获取服务的状态
	 * @return [type] [description]
	 */
	private static function status(){
		$pid = self::getPidFromFile();
		if($pid > 0){
			self::$status = Wrapper::RUNNING;
		}else{
			self::$status = Wrapper::STOP;
		}

		self::displayInfo();
	}


	/**
	 * 重定向输出文件
	 * @return [type] [description]
	 */
	protected static function resetStd(){
		if(!self::$daemonize){
			return;
		}

		global $STDIN,$STDOUT;

		$handle = fopen(self::$logFile,"a");
		if($handle){
			unset($handle);
			@fclose(STDOUT);
			@fclose(STDERR);
			$STDOUT = fopen(self::$logFile,"a");
			$STDERR = fopen(self::$logFile,"a");
        }else{
        	exit("日志文件".self::$logFile."不存在；");
        }

	}

	/**
	 * 判断服务是否已经启动
	 * @return boolean [description]
	 */
	protected static function isStart(){
		
		$pid = self::getPidFromFile();

		if( $pid > 0 ){
			exit("服务已经启动，请先结束服务 \n");
		}
	}

	/**
	 * 保存pid
	 * @return [type]       [description]
	 */
	protected static function saveMasterPid(){		
		
		if(!self::$daemonize){
			return;
		}

		file_put_contents(self::$pidFile,posix_getpid());
	}

	/**
     * 尝试以守护进程的方式运行
     * @throws Exception
     */
    protected static function daemonize()
    {
        if(!self::$daemonize){
        	return;
        }

        $pid = pcntl_fork();
        if($pid > 0){
        	exit(0);
        }

        posix_setsid();
        
        $pid = pcntl_fork();
        if($pid > 0){
        	exit(0);
        }else{
        	self::setProcName("PHP Wrapper Process @ ".self::$file);
        }
    }

    /**
     * 设置进程名称
     * @param [type] $title [description]
     */
    private static function setProcName($title){
    	if(function_exists("cli_set_process_title")){
    		@cli_set_process_title($title);
    	}
        elseif(extension_loaded('proctitle') && function_exists('setproctitle')){
        	@setproctitle($title);
        }
    }


	private static function displayInfo(){
		echo "============================\033[47;30m Wrapper \033[0m============================\n";
		echo str_pad("Wrapper版本:" ,15," "), Wrapper::VERSION,"\n";
		echo str_pad("当前时间:",15," "),date('Y-m-d H:i:s'),"\n";
		echo str_pad("PHP版本:",15," "),PHP_VERSION,"\n";
		echo str_pad("执行脚本:",15," "),self::$file,"\n";
		if(self::$status == Wrapper::RUNNING && self::$daemonize){
			echo str_pad("进程PID路径:",15," "),self::$pidFile,"\n";
		}
		echo str_pad("运行状态:",15," "),"\033[42;37m[",self::$status," SUCCESS]\033[0m\n";

		echo "\n";		
	}

}
