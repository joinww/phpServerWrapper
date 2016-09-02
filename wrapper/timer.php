<?php
//定时器
class Timer{
	//任务队列
	private static $task = array();

	private static function installSignal(){
		pcntl_signal(SIGALRM,array('Timer','signalHandler'),false);
	}

	private static function signalHandler($sig){
		self::doTask();
	}

	/**
	 * 添加事件队列
	 * @param [type] $obj [description]
	 */
	public static function add(Task $obj){
		self::$task[$obj->getTaskId()] = $obj;
	}

	/**
	 * 删除队列
	 * @param  [type] $obj [description]
	 * @return [type]      [description]
	 */
	public static function del(Task $obj){
		if(array_key_exists($obj->getTaskId(), self::$task)){
			self::$task[$obj->getTaskId()] = null;
		}
	}

	/**
	 * 执行队列
	 * @return [type] [description]
	 */
	public static function doTask(){
		foreach (self::$task as &$value) {
			if(($value->getLastTime() + $value->getInterval()) < time()){
				$value->setLastTime(time());
				require $value->getObj();
			}
		}
		pcntl_alarm(1); //继续
	}

	public static function run(){		
		self::installSignal();
		pcntl_alarm(1);
		while(1){
			pcntl_signal_dispatch();
		}
	}
}

class Task{
	private $taskId;
	private $obj; //执行路径
	private $interval; //秒
	private $lastTime;

	public function setTaskId($taskId){
		$this->taskId = $taskId;
	}

	public function getTaskId(){
		if(empty($this->taskId)){
			return md5($this->obj);
		}
		return $this->taskId;
	}

	public function setObj($obj){
		$this->obj = $obj;
	}

	public function getObj(){
		return $this->obj;
	}

	public function setInterval($interval){
		$this->interval = $interval;
	}

	public function getInterval(){
		if($this->interval <=0 ){
			exit("时间间隔未设置\n");
		}
		return $this->interval;
	}

	public function setLastTime($time){
		$this->lastTime = $time;
	}

	public function getLastTime(){
		if(empty($this->lastTime)){
			$this->lastTime = time();
		}
		return $this->lastTime;
	}

}