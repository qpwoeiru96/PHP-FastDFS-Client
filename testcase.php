<?php
set_time_limit(10);

include(__DIR__ . '/Exception.php');
include(__DIR__ . '/Base.php');
include(__DIR__ . '/Tracker.php');
include(__DIR__ . '/Storage.php');

$time_start = microtime(TRUE);

$tracker_addr = '168.192.122.29';
$tracker_port = 22122;

$tracker      = new FastDFS\Tracker($tracker_addr, $tracker_port);
$storage_info = $tracker->applyStorage('lms');

var_dump($storage_info);


$group_name = 'lms';
//$file_path = 'M00/00/00/CgAABVFYZgmAQ_9nAKnrXobBHdI433.rar';
//$appender_file_path = 'M00/00/00/CgAABVFc8duEOo6HAAAAAD1cKVQ817.txt';

$storage = new FastDFS\Storage($storage_info['storage_addr'], $storage_info['storage_port']);

var_dump(
    $storage->downloadToFile('lms', 'M00/00/00/qMB6HVLYiECAJ2KTACvD-UdfQeU480.pdf', __DIR__ . '/test.pdf')
    //$storage->uploadFile($storage_info['storage_index'], 'F:\\Downloads\\desktop.ini')
    //$storage->getFileInfo($group_name, $file_path),
    //$storage->deleteFile($group_name, $file_path),
    //$storage->setFileMetaData($group_name, $file_path, array(
    //    'time' => time()
    //), 2),
    //$storage->uploadSlaveFile('I:\\FastDFS_v4.06\\FastDFS\\HISTORY', $file_path, 'randdom', 'txt'),
    //$storage->getFileInfo($group_name, $file_path)
    //$storage->getFileMetaData($group_name, $file_path)
    //$storage->downloadFile($group_name, $file_path)
    //$storage->uploadAppenderFile($storage_info['storage_index'], 'I:\\FastDFS_v4.06\\FastDFS\\HISTORY', 'txt')
    //$storage->appendFile('TEST' . time() . PHP_EOL, $appender_file_path)
    //$storage->modifyFile('I:\\FastDFS_v4.06\\FastDFS\\INSTALL', $appender_file_path, 0)
);

$time_end = microtime(TRUE);

printf("[内存最终使用: %.2fMB]\r\n", memory_get_usage() /1024 /1024 ); 
printf("[内存最高使用: %.2fMB]\r\n", memory_get_peak_usage()  /1024 /1024) ; 
printf("[页面执行时间: %.2f毫秒]\r\n", ($time_end - $time_start) * 1000 );