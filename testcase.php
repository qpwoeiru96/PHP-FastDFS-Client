<?php
set_time_limit(10);

include('FastDFS.php');

$time_start = microtime(TRUE);

$tracker_addr = '10.0.0.5';
$tracker_port = 22122;

$tracker      = new FastDFS_Taracker($tracker_addr, $tracker_port);
$storage_info = $tracker->applyStorage('group1');

$group_name = 'group1';
$file_path = 'M00/00/00/CgAABVFYZgmAQ_9nAKnrXobBHdI433.rar';

$storage = new FastDFS_Storage($storage_info['storage_addr'], $storage_info['storage_port']);

var_dump(
    //$storage->uploadFile($storage_info['storage_index'], 'F:\\Software\\kugou7403.exe'),
    //$storage->getFileInfo($group_name, $file_path),
    //$storage->deleteFile($group_name, $file_path),
    //$storage->setFileMetaData($group_name, $file_path, array(
    //    'time' => time()
    //), 2),
    $storage->uploadSlaveFile('I:\\FastDFS_v4.06\\FastDFS\\HISTORY', $file_path, 'randdom', 'txt'),
    $storage->getFileInfo($group_name, $file_path)
    //$storage->getFileMetaData($group_name, $file_path)
    //$storage->downloadFile($group_name, $file_path)
);

$time_end = microtime(TRUE);

printf("[内存最终使用: %.2fMB]\r\n", memory_get_usage() /1024 /1024 ); 
printf("[内存最高使用: %.2fMB]\r\n", memory_get_peak_usage()  /1024 /1024) ; 
printf("[页面执行时间: %.2f毫秒]\r\n", ($time_end - $time_start) * 1000 );
