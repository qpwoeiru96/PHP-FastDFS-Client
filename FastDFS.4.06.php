<?php
/************************************************
 * PHP-FastDFS-Client (FOR FastDFS v4.06)
 ************************************************
 * @Description: 用PHP Socket实现的FastDFS客户端
 * @Author: $Author: QPWOEIRU96 $
 * @Version: $Rev: 96 $
 * @Date: $Date: 2013-04-04 01:30:36 +0800 (周四, 04 四月 2013) $
 *************************************************/

define('FDFS_PROTO_PKG_LEN_SIZE', 8);
//body_length + command + status
define('FDFS_HEADER_LENGTH', 10);
define('FDFS_IP_ADDRESS_SIZE', 16);
define('FDFS_FILE_EXT_NAME_MAX_LEN', 6);
define('FDFS_GROUP_NAME_MAX_LEN', 16);
define('FDFS_OVERWRITE_METADATA', 1);
define('FDFS_MERGE_METADATA', 2);
// 连接超时时间
define('FDFS_CONNECT_TIME_OUT', 5);
define('FDFS_FILE_PREFIX_MAX_LEN', 16);
//传输超时时间
//TODO: stream_set_timeout($fp, $timeout);
define('FDFS_TRANSFER_TIME_OUT', 0);

//文件传输块大小 不需要修改 影响内存使用 跟传输时间 太大太小都有问题 建议为1MB 
//已废弃 使用stream_copy_to_stream
//define('FDFS_FILE_TRANSFER_BLOCK_SIZE', 1048576);

class FastDFS_Exception extends \Exception {

    public function __construct($message = '', $code = 0) {
        die("<pre>FastDFS Error:\n\t code: $code\n\t message: $message</pre>");
    }
}

abstract class FastDFS_Base {

    //socket套接字
    protected $_sock;

    //连接主机地址
    protected $_host;

    //连接端口
    protected $_port;

    //错误代码
    protected $_errno;

    //错误信息
    protected $_errstr;

    public function __construct($host, $port) {

        $this->_host = $host;
        $this->_port = $port;
        $this->_sock = $this->_connect();
        
    }

    private function _connect() {

        $sock = @fsockopen(
            $this->_host,
            $this->_port,
            $this->_errno, 
            $this->_errstr,
            FDFS_CONNECT_TIME_OUT
        );

        if( !is_resource($sock) ) {
            throw new FastDFS_Exception($this->_errstr, $this->_errno);
            return FALSE;
        }

        return $sock;

    }

    protected function send($data, $length = 0) {

        if(!$length) $length = strlen($data);

        if ( feof($this->_sock) 
            || fwrite( $this->_sock, $data, $length ) !== $length ) {

            $this->_error = 'connection unexpectedly closed (timed out?)';
            $this->_errno = 0;
            throw new FastDFS_Exception($this->_errstr, $this->_errno);
            return FALSE;
        }

        return TRUE;
    }

    protected function read($length) {

        if( feof($this->_sock) ) {

            $this->_error = 'connection unexpectedly closed (timed out?)';
            $this->_errno = 0;
            throw new FastDFS_Exception($this->_errstr, $this->_errno);
            return FALSE;
        }

        /*$data  = '';

        while (!feof($this->_sock)) {
            $data .= fread($this->_sock, $length);
            $stream_meta_data = stream_get_meta_data($this->_sock); //Added line
            if($stream_meta_data['unread_bytes'] <= 0) break; //Added line
        }*/

        $data = stream_get_contents($this->_sock, $length);

        assert( $length === strlen($data) );

        return $data;

    }

    final static function padding($str, $len) {

        $str_len = strlen($str);

        return $str_len > $len
            ? substr($str, 0, $len)
            : $str . pack('x'. ($len - $str_len));
    }

    final static function buildHeader($command, $length = 0) {

        return self::packU64($length) . pack('Cx', $command);

    }

    final static function buildMetaData($data) {

        $S1 = "\x01";
        $S2 = "\x02";
           
        $list = array();
        foreach($data as $key => $val) {
            $list[] = $key . $S2 . $val;
        };

        return implode($S1, $list);
    }

    final static function parseMetaData($data) {

        $S1 = "\x01";
        $S2 = "\x02";

        $arr    = explode($S1, $data);
        $result = array();

        foreach($arr as $val) {
            list($k, $v) = explode($S2, $val);
            $result[$k] = $v;
        }

        return $result;

    }    

    final static function parseHeader($str) {

        assert(strlen($str) === FDFS_HEADER_LENGTH);

        $result = unpack('C10', $str);

        $length  = self::unpackU64(substr($str, 0, 8));
        $command = $result[9];
        $status  = $result[10];

        return array(
            'length'  => $length,
            'command' => $command,
            'status'  => $status
        );

    }

    /**
     * From: sphinxapi.php
     */
    final static function unpackU64($v) {
        list ( $hi, $lo ) = array_values ( unpack ( "N*N*", $v ) );

        if ( PHP_INT_SIZE>=8 )
        {
            if ( $hi<0 ) $hi += (1<<32); // because php 5.2.2 to 5.2.5 is totally fucked up again
            if ( $lo<0 ) $lo += (1<<32);

            // x64, int
            if ( $hi<=2147483647 )
                return ($hi<<32) + $lo;

            // x64, bcmath
            if ( function_exists("bcmul") )
                return bcadd ( $lo, bcmul ( $hi, "4294967296" ) );

            // x64, no-bcmath
            $C = 100000;
            $h = ((int)($hi / $C) << 32) + (int)($lo / $C);
            $l = (($hi % $C) << 32) + ($lo % $C);
            if ( $l>$C )
            {
                $h += (int)($l / $C);
                $l  = $l % $C;
            }

            if ( $h==0 )
                return $l;
            return sprintf ( "%d%05d", $h, $l );
        }

        // x32, int
        if ( $hi==0 )
        {
            if ( $lo>0 )
                return $lo;
            return sprintf ( "%u", $lo );
        }

        $hi = sprintf ( "%u", $hi );
        $lo = sprintf ( "%u", $lo );

        // x32, bcmath
        if ( function_exists("bcmul") )
            return bcadd ( $lo, bcmul ( $hi, "4294967296" ) );
        
        // x32, no-bcmath
        $hi = (float)$hi;
        $lo = (float)$lo;
        
        $q = floor($hi/10000000.0);
        $r = $hi - $q*10000000.0;
        $m = $lo + $r*4967296.0;
        $mq = floor($m/10000000.0);
        $l = $m - $mq*10000000.0;
        $h = $q*4294967296.0 + $r*429.0 + $mq;

        $h = sprintf ( "%.0f", $h );
        $l = sprintf ( "%07.0f", $l );
        if ( $h=="0" )
            return sprintf( "%.0f", (float)$l );
        return $h . $l;
    }

    /**
     * From: sphinxapi.php
     */
    final static function packU64 ($v) {

       
        assert ( is_numeric($v) );
        
        // x64
        if ( PHP_INT_SIZE>=8 )
        {
            assert ( $v>=0 );
            
            // x64, int
            if ( is_int($v) )
                return pack ( "NN", $v>>32, $v&0xFFFFFFFF );
                              
            // x64, bcmath
            if ( function_exists("bcmul") )
            {
                $h = bcdiv ( $v, 4294967296, 0 );
                $l = bcmod ( $v, 4294967296 );
                return pack ( "NN", $h, $l );
            }
            
            // x64, no-bcmath
            $p = max ( 0, strlen($v) - 13 );
            $lo = (int)substr ( $v, $p );
            $hi = (int)substr ( $v, 0, $p );
        
            $m = $lo + $hi*1316134912;
            $l = $m % 4294967296;
            $h = $hi*2328 + (int)($m/4294967296);

            return pack ( "NN", $h, $l );
        }

        // x32, int
        if ( is_int($v) )
            return pack ( "NN", 0, $v );
        
        // x32, bcmath
        if ( function_exists("bcmul") )
        {
            $h = bcdiv ( $v, "4294967296", 0 );
            $l = bcmod ( $v, "4294967296" );
            return pack ( "NN", (float)$h, (float)$l ); // conversion to float is intentional; int would lose 31st bit
        }

        // x32, no-bcmath
        $p = max(0, strlen($v) - 13);
        $lo = (float)substr($v, $p);
        $hi = (float)substr($v, 0, $p);
        
        $m = $lo + $hi*1316134912.0;
        $q = floor($m / 4294967296.0);
        $l = $m - ($q * 4294967296.0);
        $h = $hi*2328.0 + $q;

        return pack ( "NN", $h, $l );
    }

    public function __destruct() {

        if(is_resource($this->_sock))
            fclose($this->_sock);
    }
}

class FastDFS_Taracker extends FastDFS_Base{

    /**
     * 根据GroupName申请Storage地址
     *
     * @command 104
     * @param string $group_name 组名称
     */
    public function applyStorage($group_name) {

        $req_header = self::buildHeader(104, FDFS_GROUP_NAME_MAX_LEN);
        $req_body   = self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN);

        $this->send($req_header . $req_body);

        $res_header = $this->read(FDFS_HEADER_LENGTH);        
        $res_info   = self::parseHeader($res_header);

        if($res_info['status'] !== 0) {

            throw new FastDFSException(
                'something wrong with get storage by group name', 
                $res_info['status']);

            return FALSE;
        }

        $res_body = !!$res_info['length'] 
            ? $this->read($res_info['length'])
            : '';

        $group_name   = trim(substr($res_body, 0, FDFS_GROUP_NAME_MAX_LEN));
        $storage_addr = trim(substr($res_body, FDFS_GROUP_NAME_MAX_LEN, 
            FDFS_IP_ADDRESS_SIZE - 1));

        list(,,$storage_port)  = unpack('N2', substr($res_body, 
            FDFS_GROUP_NAME_MAX_LEN + FDFS_IP_ADDRESS_SIZE - 1, 
            FDFS_PROTO_PKG_LEN_SIZE));

        $storage_index  = ord(substr($res_body, -1));


        return array(
            'group_name'    => $group_name,
            'storage_addr'  => $storage_addr,
            'storage_port'  => $storage_port,
            'storage_index' => $storage_index
        );


    }

}

class FastDFS_Storage extends FastDFS_Base {

    /**
     * 上传文件
     *
     * @command 11
     * @param char $index 索引
     * @param string $filename
     * @param string $文件扩展名
     * @return array 
     */
    public function uploadFile($index, $filename, $ext = '') {


        if(!file_exists($filename))
            return FALSE;

        $path_info = pathinfo($filename);

        if(strlen($ext) > FDFS_FILE_EXT_NAME_MAX_LEN) {

            throw new FastDFSException('file ext too long.', 0);
            return FALSE;
        }

        if($ext === '') {
            $ext = $path_info['extension'];
        }

        $fp = fopen($filename, 'rb');
        flock($fp, LOCK_SH);

        $filesize = filesize($filename);

        $req_body_length = 1 + FDFS_PROTO_PKG_LEN_SIZE + 
            FDFS_FILE_EXT_NAME_MAX_LEN + $filesize;

        $req_header = self::buildHeader(11, $req_body_length);
        $req_body   = pack('C', $index) . self::packU64($filesize) . self::padding($ext, FDFS_FILE_EXT_NAME_MAX_LEN);
        $this->send($req_header . $req_body);

        /*while( !feof($fp) ) {
            fwrite($this->_sock, fread($fp, FDFS_FILE_TRANSFER_BLOCK_SIZE));
        }*/

        stream_copy_to_stream($fp, $this->_sock, $filesize);

        flock($fp, LOCK_UN);
        fclose($fp);

        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info   = self::parseHeader($res_header);

        if($res_info['status'] !== 0) {

            throw new FastDFS_Exception(
                'something wrong with uplode file', 
                $res_info['status']);

            return FALSE;
        }

        $res_body  = $res_info['length'] 
            ? $this->read($res_info['length']) 
            : FALSE;

        $group_name = trim(substr($res_body, 0, FDFS_GROUP_NAME_MAX_LEN));
        $file_path  = trim(substr($res_body, FDFS_GROUP_NAME_MAX_LEN));

        return array(
            'group_name' => $group_name,
            'file_path'  => $file_path
        );

    }

    /**
     * 上传Slave文件
     *
     * @command 21
     *
     */
    public function uploadSlaveFile($filename, $master_file_path, $prefix_name, $ext = '') {

        if(!file_exists($filename))
            return FALSE;

        $path_info = pathinfo($filename);

        if(strlen($ext) > FDFS_FILE_EXT_NAME_MAX_LEN) {

            throw new FastDFSException('file ext too long.', 0);
            return FALSE;
        }

        if($ext === '') {
            $ext = $path_info['extension'];
        }

        $fp = fopen($filename, 'rb');
        flock($fp, LOCK_SH);

        $filesize = filesize($filename);
        $master_file_path_length = strlen($master_file_path);

        $req_body_length = 16 + FDFS_FILE_PREFIX_MAX_LEN + 
            FDFS_FILE_EXT_NAME_MAX_LEN + $master_file_path_length + $filesize;

        $req_header = self::buildHeader(21, $req_body_length);
        $req_body   = pack('x4N', $master_file_path_length) . self::packU64($filesize) . self::padding($prefix_name, FDFS_FILE_PREFIX_MAX_LEN);
        $req_body  .= self::padding($ext, FDFS_FILE_EXT_NAME_MAX_LEN) . $master_file_path;

        $this->send($req_header . $req_body);

        stream_copy_to_stream($fp, $this->_sock, $filesize);

        flock($fp, LOCK_UN);
        fclose($fp);

        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info   = self::parseHeader($res_header);

        if($res_info['status'] !== 0) {

            if($res_info['status'] == 17) {
                $msg = 'targe slave file already existd';
            } else {
                $msg = 'something in upload slave file';
            }

            throw new FastDFS_Exception(
                $msg, 
                $res_info['status']);

            return FALSE;
        }

        $res_body  = $res_info['length'] 
            ? $this->read($res_info['length']) 
            : FALSE;

        $group_name = trim(substr($res_body, 0, FDFS_GROUP_NAME_MAX_LEN));
        $file_path  = trim(substr($res_body, FDFS_GROUP_NAME_MAX_LEN));

        return array(
            'group_name' => $group_name,
            'file_path'  => $file_path
        );

    }

    //TODO
    public function upload_appender_file() {

    } 

    /**
     * 删除文件
     *
     * @command 12
     * @param string $group_name 组名称
     * @param string $file_path 文件路径 
     * @return boolean 删除成功与否
     */
    public function deleteFile($group_name, $file_path) {

        $req_body_length = strlen($file_path) + FDFS_GROUP_NAME_MAX_LEN;
        $req_header      = self::buildHeader(12, $req_body_length);        
        $req_body        = self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN) . $file_path;

        $this->send($req_header . $req_body);

        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info   = self::parseHeader($res_header);

        return !$res_info['status'];

    }

    /**
     * 获取文件元信息
     *
     * @command 15
     * @param string $group_name 组名称
     * @param string $file_path 文件路径 
     * @return array 元信息数组
     */
    public function getFileMetaData($group_name, $file_path) {

        $req_body_length = strlen($file_path) + FDFS_GROUP_NAME_MAX_LEN;
        $req_header      = self::buildHeader(15, $req_body_length);        
        $req_body        = self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN) . $file_path;

        $this->send($req_header . $req_body);

        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info   = self::parseHeader($res_header);

        if(!!$res_info['status'])
            return FALSE;

        $res_body  = $res_info['length'] 
            ? $this->read($res_info['length']) 
            : FALSE;

        return self::parseMetaData($res_body);

    }

    /**
     * 设置文件元信息
     *
     * @command 13
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @param array $meta_data 元信息数组
     * @return boolean 设置成功与否
     */
    public function setFileMetaData($group_name, $file_path, array $meta_data, $flag =  FDFS_OVERWRITE_METADATA) {

        $meta_data        = self::buildMetaData($meta_data);
        $meta_data_length = strlen($meta_data);
        $file_path_length = strlen($file_path);
        $flag = $flag === FDFS_OVERWRITE_METADATA ? 'O' : 'M';

        $req_body_length = (FDFS_PROTO_PKG_LEN_SIZE * 2) + 1 + $meta_data_length + $file_path_length + FDFS_GROUP_NAME_MAX_LEN;

        $req_header      = self::buildHeader(13, $req_body_length);

        $req_body =  self::packU64($file_path_length) . self::packU64($meta_data_length);
        $req_body .= $flag . self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN) . $file_path . $meta_data;

        $this->send($req_header . $req_body);

        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info   = self::parseHeader($res_header);

        return !$res_info['status'];

    }

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
    public function downloadFile($group_name, $file_path, $offset = 0, $length = 0) {

        $file_path_length = strlen($file_path);
        $req_body_length  = (FDFS_PROTO_PKG_LEN_SIZE * 2) + $file_path_length + FDFS_GROUP_NAME_MAX_LEN;
        
        $req_header       = self::buildHeader(14, $req_body_length);

        $req_body         = self::packU64($offset) . self::packU64($length) . self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN);
        $req_body        .= $file_path;

        $this->send($req_header . $req_body);

        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info   = self::parseHeader($res_header);

        if(!!$res_info['status']) return FALSE;

        return $this->read($res_info['length']);

    }

    /**
     * 检索文件信息
     *
     * @command 22
     * @param string $group_name 组名称
     * @param string $file_path 文件路径
     * @return array
     */
    public function getFileInfo($group_name, $file_path) {

        $req_body_length = strlen($file_path) + FDFS_GROUP_NAME_MAX_LEN;
        $req_header      = self::buildHeader(22, $req_body_length);        
        $req_body        = self::padding($group_name, FDFS_GROUP_NAME_MAX_LEN) . $file_path;

        $this->send($req_header . $req_body);

        $res_header = $this->read(FDFS_HEADER_LENGTH);
        $res_info   = self::parseHeader($res_header);

        if(!!$res_info['status']) return FALSE;

        $res_body  = $res_info['length'] 
            ? $this->read($res_info['length']) 
            : FALSE;

        $file_size     = self::unpackU64(substr($res_body, 0, FDFS_PROTO_PKG_LEN_SIZE));
        $timestamp     = self::unpackU64(substr($res_body, FDFS_PROTO_PKG_LEN_SIZE, FDFS_PROTO_PKG_LEN_SIZE));
        list(,,$crc32) = unpack('N2', substr($res_body, 2 * FDFS_PROTO_PKG_LEN_SIZE, FDFS_PROTO_PKG_LEN_SIZE));
        $crc32         = base_convert(sprintf('%u', $crc32), 10, 16);
        $storage_id    = trim(substr($res_body, -16));

        return array(
            'file_size'  => $file_size,
            'timestamp'  => $timestamp,
            'crc32'      => $crc32,
            'storage_id' => $storage_id
        );

    }

}

