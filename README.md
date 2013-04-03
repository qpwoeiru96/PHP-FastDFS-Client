PHP-FastDFS-Client
==================

用PHP Socket 实现的 FastDFS 客户端.
目前支持版本为 FastDFS 4.06

实现功能：

    /**
     * 根据GroupName申请Storage地址
     *
     * @command 104
     * @param string $group_name 组名称
     * @return array/boolean
     */
     
     FastDFS_Tracker::applyStorage($group_name)

    /**
     * 上传文件
     *
     * @command 11
     * @param char $index 索引
     * @param string $filename
     * @param string $文件扩展名
     * @return array 
     */
     
     FastDFS_Storage::uploadFile($index, $filename, $ext = '')
     
    /**
     * 上传Slave文件
     *
     * @command 21
     * @param string $filename 待上传的文件名称
     * @param string $master_file_path 主文件名称
     * @param string $prefix_name 后缀的前缀名
     * @param string $ext 后缀名称
     * @return array/boolean
     */
     
     FastDFS_Storage::uploadSlaveFile($filename, $master_file_path, $prefix_name, $ext = '')
     
    /**
     * 删除文件
     *
     * @command 12
     * @param string $group_name 组名称
     * @param string $file_path 文件路径 
     * @return boolean 删除成功与否
     */
     
     FastDFS_Storage::deleteFile($group_name, $file_path)
     
    /**
     * 获取文件元信息
     *
     * @command 15
     * @param string $group_name 组名称
     * @param string $file_path 文件路径 
     * @return array 元信息数组
     */
     
     FastDFS_Storage::getFileMetaData($group_name, $file_path)
     
    /**
     * 设置文件元信息
     *
     * @command 13
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @param array $meta_data 元信息数组
     * @return boolean 设置成功与否
     */
     
     FastDFS_Storage::setFileMetaData($group_name, $file_path, array $meta_data, $flag =  FDFS_OVERWRITE_METADATA)
     
    /**
     * 下载文件(不建议对大文件使用)
     *
     * @command 14
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @param int $offset 下载文件偏移量
     * @param int $length 下载文件大小
     * @return string 文件内容
     */
     
     FastDFS_Storage::downloadFile($group_name, $file_path, $offset = 0, $length = 0)
     
    /**
     * 检索文件信息
     *
     * @command 22
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @return array
     */
     
     FastDFS_Storage::getFileInfo($group_name, $file_path)
     
TODO LIST:
上传可更新文件: uploadAppenderFile()
附加可更新文件信息: appendFile()
