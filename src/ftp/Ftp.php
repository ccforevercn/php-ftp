<?php
declare(strict_types=1);
/**
 * @author   :  ccforevercn<1253705861@qq.com>
 * @link     http://ccforever.cn
 * @license  https://github.com/ccforevercn
 * @day      :     2022/1/5
 */


namespace Ftp;

/**
 * Class Ftp
 * @package Ftp
 */
class Ftp
{
    /**
     * Ftp
     * @var resource
     */
    private $connect;

    /**
     * 是否手动关闭FTP资源
     * @var bool
     */
    private $close = false;

    /**
     * FTP参数
     * key            desc
     * host           服务器地址
     * username       用户名
     * password       密码
     * port           端口
     * @var array
     */
    private $option;

    public function __construct(array $option)
    {
        $this->option = $option;
    }

    /**
     * 连接并登陆
     *
     * @return $this
     * @throws FtpException
     */
    public function connect(): self
    {
        $this->connect = ftp_connect($this->option["host"]); // 连接FTP
        if(is_bool($this->connect)){throw new FtpException("地址：" . $this->option["host"] . "连接失败！");} // FTP连接失败
        $bool = ftp_login($this->connect, $this->option["username"], $this->option["password"]); // 登陆FTP
        if(!$bool){throw new FtpException("FTP登陆失败，账号或者密码错误");} // FTP登陆失败
        return $this;
    }

    /**
     * 文件列表
     * $path 服务器端文件夹地址
     *
     * @param string $path
     * @return array
     * @throws FtpException
     */
    public function nlist(string $path): array
    {
        $nlist = ftp_nlist($this->connect, $path); // 获取文件夹列表
        if(is_bool($nlist)) { throw new FtpException("文件列表获取失败！！！"); } // 文件夹列表失败
        if(count($nlist) === 1 && reset($nlist) === $path) { throw new FtpException("文件不支持打开"); } // 验证是否为文件
        if($path !== DIRECTORY_SEPARATOR) { // 验证当前查看的文件夹列表是不是根目录
            $paths = explode(DIRECTORY_SEPARATOR, $path); // 使用分隔符切割文件夹地址
            if(count($paths) > 2){ // 验证是否是多层文件夹地址
                array_pop($paths); // 删除最后一个文件夹空文件夹
                array_unshift($nlist, implode(DIRECTORY_SEPARATOR, $paths)); // 文件夹列表中添加查询的文件夹地址
            }
            array_unshift($nlist, DIRECTORY_SEPARATOR); // 文件夹列表中添加根目录地址
        }
        return $nlist;
    }

    /**
     * 创建文件夹
     * $target 服务器端文件夹地址
     * $dirs 要在服务器端文件夹地址内创建的文件夹名称
     *
     * @param string $target
     * @param array $dirs
     * @throws FtpException
     */
    public function mkdir(string $target, array $dirs): void
    {
        $nlist = ftp_nlist($this->connect, $target); // 获取服务器端的文件夹列表
        foreach ($dirs as $dir) { // 循环创建文件夹
            if(!in_array($dir, $nlist)) { // 验证要创建的文件夹是否已经存在，如果不存在再创建
                $bool = ftp_mkdir($this->connect, $dir); // 创建文件夹
                if(is_bool($bool)){ throw new FtpException("文件夹创建失败！！！"); }
            }
        }
    }

    /**
     * 上传文件
     * $target FTP服务器的地址
     * $self 需要上传的文件地址
     *
     * @param string $target
     * @param string $self
     * @param int $start
     * @throws FtpException
     */
    public function upload(string $target, string $self, int $start): void
    {
        $bool = ftp_put($this->connect, $target, $self,FTP_BINARY, $start);
        if(!$bool) { throw new FtpException("文件上传失败!!!"); }
    }

    /**
     * 关闭FTP资源
     *
     * @return bool
     */
    public function close(): bool
    {
        $this->close = ftp_close($this->connect);
        return $this->close;
    }

    public function __destruct()
    {
        !$this->close ? ftp_close($this->connect) : [];
    }
}