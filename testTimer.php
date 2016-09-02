<?php

require_once './wrapper/timer.php';

$task= new Task();
$task->setInterval(5);//秒
$task->setObj('./shell/testTimer.php');

Timer::add($task);

Timer::run();