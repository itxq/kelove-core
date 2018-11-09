<?php
/**
 *  ==================================================================
 *        文 件 名: IP.php
 *        概    要: IP信息查询
 *        作    者: IT小强
 *        创建时间: 2018-11-09 17:48
 *        修改时间:
 *        copyright (c) 2016 - 2018 mail@xqitw.cn
 *  ==================================================================
 */

namespace itxq\kelove\ip;

use itxq\kelove\SingleModel;

/**
 * IP信息查询 https://www.ipip.net
 * Class IP
 * @package itxq\kelove\ip
 */
class IP extends SingleModel
{
    private $fp = null;
    private $offset = null;
    private $index = null;
    private $driver = 'local';
    
    /**
     * IP 构造函数.
     * @param array|mixed $config
     * @throws \Exception
     */
    protected function __construct($config = []) {
        parent::__construct($config);
        $this->driver = get_sub_value('driver', $config, 'local');
        if ($this->driver === 'local') {
            $this->init();
        }
    }
    
    /**
     * 查询ip地址信息
     * @param $ip - ip地址
     * @return array|bool
     */
    public function lookup($ip) {
        if ($this->driver === 'local') {
            try {
                return $this->localLookup($ip);
            } catch (\Exception $exception) {
                return false;
            }
        } else if ($this->driver === 'taobao') {
            return $this->taoBaoLookup($ip);
        } else {
            return false;
        }
    }
    
    /**
     * 查询ip地址信息（本地查询）
     * @param $ip
     * @return array|bool
     */
    private function localLookup($ip) {
        if (empty($ip) || $this->fp === null) {
            return false;
        }
        $nip = gethostbyname($ip);
        $ipdot = explode('.', $nip);
        if ($ipdot[0] < 0 || $ipdot[0] > 255 || count($ipdot) !== 4) {
            return false;
        }
        $nip2 = pack('N', ip2long($nip));
        $tmp_offset = ((int)$ipdot[0] * 256 + (int)$ipdot[1]) * 4;
        $start = unpack('Vlen', $this->index[$tmp_offset] . $this->index[$tmp_offset + 1] . $this->index[$tmp_offset + 2] . $this->index[$tmp_offset + 3]);
        $index_offset = $index_length = null;
        $max_comp_len = $this->offset['len'] - 262144 - 4;
        for ($start = $start['len'] * 9 + 262144; $start < $max_comp_len; $start += 9) {
            if ($this->index{$start} . $this->index{$start + 1} . $this->index{$start + 2} . $this->index{$start + 3} >= $nip2) {
                $index_offset = unpack('Vlen', $this->index{$start + 4} . $this->index{$start + 5} . $this->index{$start + 6} . "\x0");
                $index_length = unpack('nlen', $this->index{$start + 7} . $this->index{$start + 8});
                break;
            }
        }
        if ($index_offset === null) {
            return false;
        }
        fseek($this->fp, $this->offset['len'] + $index_offset['len'] - 262144);
        $data = explode("\t", fread($this->fp, $index_length['len']));
        fclose($this->fp);
        $info = [];
        $info['country'] = get_sub_value(0, $data, '');
        $info['province'] = get_sub_value(1, $data, '');
        $info['city'] = get_sub_value(2, $data, '');
        $info['district'] = get_sub_value(3, $data, '');
        $info['isp'] = get_sub_value(4, $data, '');
        return $info;
    }
    
    /**
     * 查询ip地址信息
     * @param $ip
     * @return array|bool
     */
    private function taoBaoLookup($ip) {
        $url = 'http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip;
        try {
            $data = curl_request($url);
            $data = json_decode($data, true);
        } catch (\Exception $exception) {
            return false;
        }
        if (!isset($data['data'])) {
            return false;
        }
        $data = $data['data'];
        $info = [];
        $info['country'] = get_sub_value('country', $data, '');
        $info['province'] = get_sub_value('region', $data, '');
        $info['city'] = get_sub_value('city', $data, '');
        $info['district'] = get_sub_value('district', $data, '');
        $info['isp'] = get_sub_value('isp', $data, '');
        return $info;
    }
    
    /**
     * 初始化
     * @throws \Exception
     */
    private function init() {
        if ($this->fp === null) {
            $this->fp = fopen(__DIR__ . '/ipdb.datx', 'rb');
            if ($this->fp === false) {
                throw new \Exception('Invalid datx file!');
            }
            $this->offset = unpack('Nlen', fread($this->fp, 4));
            if ($this->offset['len'] < 4) {
                throw new \Exception('Invalid datx file!');
            }
            $this->index = fread($this->fp, $this->offset['len'] - 4);
        }
    }
}