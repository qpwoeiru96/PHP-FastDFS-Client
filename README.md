PHP-FastDFS-Client
==================

用PHP Socket 实现的 FastDFS 客户端，不完全版本，修改下Exception能在测试环境使用，2013年3月29日（也就是大大前天 星期五）了解的FastDFS。
星期六开始实现，因为没有Windows端的客户端，（试了下移植它自带的php client，不成功，契合的太深了。）

星期天去玩了 今天又被上头完成一个微博的功能... 估计一段时间都没法完善了 先放出来 

实现了以下几个必须的功能吧：

    /**
     * 根据GroupName申请Storage地址
     *
     * @command 104
     * @param string $group_name 组名称
     */
     
     
    /**
     * 上传文件
  	 *
  	 * @command 11
  	 * @param char $index 索引
  	 * @param string $filename
  	 * @param string $ext
  	 */


    /**
     * 删除文件
     *
     * @command 12
     * @param string $group_name 组名称
     * @param string $file_path 文件路径 
     * @return boolean 删除成功与否
     */
     
    /**
     * 获取文件元信息
     *
     * @command 15
     * @param string $group_name 组名称
     * @param string $file_path 文件路径 
     * @return array 元信息数组
     */
     
    /**
     * 设置文件元信息
     *
     * @command 13
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @param array $meta_data 元信息数组
     * @return boolean 设置成功与否
     */
     
    /**
     * 下载文件(不建议对大文件使用)
     *
     * @command 14
     */
     
性能 还过得去吧 内存控制比较好 本机测试了下 能达到百兆网卡的峰值 条件不是很好 没多少测试 可能有一些隐藏的Bug 欢迎反馈 比较我也才了解没几天
