<?php
/**
 *  ==================================================================
 *        文 件 名: IP.php
 *        概    要: 纯真IP地址数据解析类
 *        作    者: IT小强
 *        创建时间: 2019-01-03 17:48:35
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\ip;

use kelove\traits\SingleModelTrait;

/**
 * 纯真IP地址数据解析类
 * Class IP
 * @package kelove\ip
 */
class IP
{
    use SingleModelTrait;
    
    const  BEGIN_IP = 'begin_ip';
    const  END_IP = 'end_ip';
    const  COUNTRY = 'country';
    const  AREA = 'area';
    const  LOCATION = 'location';
    
    /**
     * @var array - 查询结果
     */
    protected $ipInfo = [];
    
    /**
     * IP数据库文件句柄
     * @var bool|resource
     */
    protected $fh;
    
    /**
     * 第一条索引
     * @var int
     */
    protected $first;
    
    /**
     * 最后一条索引
     * @var int
     */
    protected $last;
    
    /**
     * 索引总数
     * @var int
     */
    protected $total;
    
    /**
     * 查询IP信息
     * @param string $ip - ip地址
     * @param string $path - IP数据库文件路径
     * @return IP
     */
    public function lookup(string $ip, string $path = ''): IP {
        if (empty($path)) {
            $path = realpath(__DIR__ . '/data/qqwry.dat');
        }
        if (!$this->checkIp($ip)) {
            $this->ipInfo = [];
            return $this;
        }
        $this->ini($path);
        $ip = pack('N', intval(ip2long($ip)));
        //二分查找
        $l = 0;
        $r = $this->total;
        while ($l <= $r) {
            $m = floor(($l + $r) / 2); //计算中间索引
            fseek($this->fh, $this->first + $m * 7);
            $beginip = strrev(fread($this->fh, 4)); //中间索引的开始IP地址
            fseek($this->fh, $this->getLong3());
            $endip = strrev(fread($this->fh, 4)); //中间索引的结束IP地址
            if ($ip < $beginip) { //用户的IP小于中间索引的开始IP地址时
                $r = $m - 1;
            } else {
                if ($ip > $endip) { //用户的IP大于中间索引的结束IP地址时
                    $l = $m + 1;
                } else { //用户IP在中间索引的IP范围内时
                    $findip = $this->first + $m * 7;
                    break;
                }
            }
        }
        //查询国家地区信息
        fseek($this->fh, $findip);
        $location[self::BEGIN_IP] = long2ip($this->getLong4()); //用户IP所在范围的开始地址
        $offset = $this->getlong3();
        fseek($this->fh, $offset);
        $location[self::END_IP] = long2ip($this->getLong4()); //用户IP所在范围的结束地址
        $byte = fread($this->fh, 1); //标志字节
        switch (ord($byte)) {
            case 1:  //国家和区域信息都被重定向
                $countryOffset = $this->getLong3(); //重定向地址
                fseek($this->fh, $countryOffset);
                $byte = fread($this->fh, 1); //标志字节
                switch (ord($byte)) {
                    case 2: //国家信息被二次重定向
                        fseek($this->fh, $this->getLong3());
                        $location[self::COUNTRY] = $this->getInfo();
                        fseek($this->fh, $countryOffset + 4);
                        $location[self::AREA] = $this->getArea();
                        break;
                    default: //国家信息没有被二次重定向
                        $location[self::COUNTRY] = $this->getInfo($byte);
                        $location[self::AREA] = $this->getArea();
                        break;
                }
                break;
            case 2: //国家信息被重定向
                fseek($this->fh, $this->getLong3());
                $location[self::COUNTRY] = $this->getInfo();
                fseek($this->fh, $offset + 8);
                $location[self::AREA] = $this->getArea();
                break;
            default: //国家信息没有被重定向
                $location[self::COUNTRY] = $this->getInfo($byte);
                $location[self::AREA] = $this->getArea();
                break;
        }
        //gb2312 to utf-8（去除无信息时显示的CZ88.NET）
        foreach ($location as $k => $v) {
            $location[$k] = str_replace('CZ88.NET', '', iconv('gb2312', 'utf-8', $v));
        }
        $this->ipInfo = $location;
        return $this;
    }
    
    /**
     * 获取IP解析结果
     * @param string $type - 获取类型
     * @return array|mixed
     */
    public function get(string $type = self::LOCATION) {
        if ($type === self::LOCATION && count($this->ipInfo) === 4) {
            return $this->ipInfo;
        }
        return get_sub_value($type, $this->ipInfo, false);
    }
    
    /**
     * 初始化
     * @param string $path
     */
    protected function ini(string $path): void {
        $this->fh = fopen($path, 'rb');
        $this->first = $this->getLong4();
        $this->last = $this->getLong4();
        $this->total = ($this->last - $this->first) / 7; //每条索引7字节
    }
    
    /**
     * 查询地区信息
     * @return string
     */
    protected function getArea(): string {
        $byte = fread($this->fh, 1); //标志字节
        switch (ord($byte)) {
            case 0:
                $area = '';
                break; //没有地区信息
            case 1: //地区被重定向
                fseek($this->fh, $this->getLong3());
                $area = $this->getInfo();
                break;
            case 2: //地区被重定向
                fseek($this->fh, $this->getLong3());
                $area = $this->getInfo();
                break;
            default:
                $area = $this->getInfo($byte);
                break; //地区没有被重定向
        }
        return $area;
    }
    
    /**
     * 检查IP合法性
     * @param string $ip
     * @return bool
     */
    protected function checkIp(string $ip): bool {
        $arr = explode('.', $ip);
        if (count($arr) != 4) {
            return false;
        } else {
            for ($i = 0; $i < 4; $i++) {
                if ($arr[$i] < '0' || $arr[$i] > '255') {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * 查询信息
     * @param string $data
     * @return string
     */
    protected function getInfo(string $data = ''): string {
        $char = fread($this->fh, 1);
        while (ord($char) != 0) { //国家地区信息以0结束
            $data .= $char;
            $char = fread($this->fh, 1);
        }
        return $data;
    }
    
    protected function getLong4() {
        //读取little-endian编码的4个字节转化为长整型数
        $result = unpack('Vlong', fread($this->fh, 4));
        return $result['long'];
    }
    
    protected function getLong3() {
        //读取little-endian编码的3个字节转化为长整型数
        $result = unpack('Vlong', fread($this->fh, 3) . chr(0));
        return $result['long'];
    }
    
    /**
     * 析构函数
     */
    public function __destruct() {
        @fclose($this->fh);
    }
}