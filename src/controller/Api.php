<?php
/**
 *  ==================================================================
 *        文 件 名: Api.php
 *        概    要: API 控制器基类
 *        作    者: IT小强
 *        创建时间: 2019-01-15 20:06:00
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\controller;

/**
 * @title API 控制器基类
 * @code -1 AccessToken 未传递、不正确、不匹配、已过期
 * @code -2 请求方式错误 必须为 指定的请求方式（POST\GET ...）
 * Class Api
 * @package kelove\core
 */
abstract class Api extends Base
{
    const POST = 'post';
    const GET = 'get';
    
    /**
     * @var bool - 客户端token是否加密
     */
    protected $isEncrypt = true;
    
    /**
     * @var string - 当前请求的AccessToken
     */
    protected $accessToken = '';
    
    /**
     * @var int - AccessToken 有效时间
     */
    protected $expiresIn = 7200;
    
    /**
     * @var string - 返回数据的格式
     */
    protected $resultType = 'json';
    
    /**
     * @var bool - 是否检查TOKEN
     */
    protected $checkAccessToken = true;
    
    /**
     * @var array - 允许直接访问的接口
     */
    protected $allowAction = [];
    
    /**
     * @var array - post请求操作
     */
    protected $postAction = [];
    
    /**
     * @var array - get请求操作
     */
    protected $getAction = [];
    
    /**
     * 初始化
     */
    protected function initialize()
    {
        // 获取当前操作名
        $action = strtolower($this->request->action());
        // 允许直接访问的接口
        $this->allowAction = array_map('strtolower', $this->allowAction);
        // 如果开启TOKEN验证 且当前接口不允许直接访问时验证TOKEN
        if ($this->checkAccessToken === true && !in_array($action, $this->allowAction)) {
            $this->getRequestAccessToken();
            $this->checkAccessToken();
        }
        // 检查请求方式
        $this->methodCheck($action, self::POST);
        $this->methodCheck($action, self::GET);
        parent::initialize();
    }
    
    /**
     * 必须实现的方法 - 通过接口传递的token查询已保存的token信息
     * @param string $accessToken 解密后的token字符串
     * @return array 获取失败返回空数组 成功返回 AccessToken 信息数组
     * eg:
     * [
     *      'access_token'=>'657305548834e0899fcaba3ab0bb8671080665ea',
     *      'create_time' =>'1527562208',
     *      'expires_in'  =>'7200',
     * ]
     */
    abstract protected function getAccessTokenInfo(string $accessToken): array;
    
    /**
     * 创建AccessToken
     * @return array[0=>'未加密的token信息',1=>'加密后的token信息']
     */
    protected function createAccessToken(): array
    {
        if (empty($accessToken)) {
            $accessToken = sha1(md5(uniqid(md5(microtime(true)), true)));
        }
        // 拼装数组信息
        $tokenInfo = ['access_token' => $accessToken, 'create_time' => time(), 'expires_in' => $this->expiresIn];
        return [$tokenInfo, $this->encryptAccessToken($tokenInfo)];
    }
    
    /**
     * 接口数据返回
     * @param int $code - 返回的code
     * @param string $msg - 提示信息
     * @param string $type - 返回数据格式
     * @param mixed $data - 返回的数据
     * @param array $header - 发送的Header信息
     * @return bool
     */
    protected function apiResult(int $code = 0, string $msg = '', $data = [], string $type = 'json', array $header = [])
    {
        $type = empty($type) ? $this->resultType : $type;
        $this->result($data, $code, $msg, $type, $header);
        return false;
    }
    
    /**
     * 加密accessToken（继承后可重写）
     * @param array $accessToken - Token明文
     * @return string - 输出密文
     */
    protected function encryptAccessToken(array $accessToken): string
    {
        return strval(base64_encode(json_encode($accessToken)));
    }
    
    /**
     * 解密accessToken（继承后可重写）
     * @param string $accessToken - Token密文
     * @return array
     */
    protected function decryptAccessToken(string $accessToken): array
    {
        return (array)json_decode(base64_decode($accessToken), true);
    }
    
    /**
     * 获取数组、对象下标对应值，不存在时返回指定的默认值
     * @param string|integer $name - 下标（键名）
     * @param array|object $data - 原始数组/对象
     * @param mixed $default - 指定默认值
     * @return mixed
     */
    protected function getSubValue($name, $data, $default = '')
    {
        if (is_object($data)) {
            $value = isset($data->$name) ? $data->$name : $default;
        } else {
            if (is_array($data)) {
                $value = isset($data[$name]) ? $data[$name] : $default;
            } else {
                $value = $default;
            }
        }
        return $value;
    }
    
    /**
     * AccessToken验证成功后的回调处理
     * @return bool
     */
    protected function checkAccessTokenCallBack(): bool
    {
        return true;
    }
    
    /**
     * 检查请求方式
     * @param string $action - 当前请求的操作名
     * @param string $type - 检查类型GET/POST
     * @return bool
     */
    private function methodCheck(string $action, $type = self::POST): bool
    {
        if ($type === self::POST) {
            if (in_array($action, array_map('strtolower', $this->postAction)) && !$this->request->isPost()) {
                return $this->apiResult(-2, '请求方式错误 必须为POST', [], $this->resultType, []);
            }
            return true;
        } else {
            if ($type === self::GET) {
                if (in_array($action, array_map('strtolower', $this->getAction)) && !$this->request->isGet()) {
                    return $this->apiResult(-2, '请求方式错误 必须为GET', [], $this->resultType, []);
                }
                return true;
            } else {
                return false;
            }
        }
    }
    
    /**
     * 检查AccessToken
     * @return bool
     */
    private function checkAccessToken(): bool
    {
        $tokenInfo = $this->getAccessTokenInfo($this->accessToken);
        $createTime = intval($this->getSubValue('create_time', $tokenInfo, 0));
        $expiresIn = intval($this->getSubValue('expires_in', $tokenInfo, 0));
        if ($expiresIn <= 0) {
            $expiresIn = $this->expiresIn;
        }
        $token = $this->getSubValue('access_token', $tokenInfo, '');
        if (!$tokenInfo || empty($token) || $token !== $this->accessToken) {
            // TOKEN 不正确
            return $this->apiResult(-1, 'AccessToken 不匹配', [], $this->resultType, []);
        }
        if ($createTime <= 1 || (time() - $createTime) > $expiresIn) {
            // TOKEN 生成时间不正确 或者 TOKEN 已过期
            return $this->apiResult(-1, 'AccessToken 已过期', [], $this->resultType, []);
        }
        return $this->checkAccessTokenCallBack();
    }
    
    /**
     * 获取请求参数中的AccessToken，并解密为数组
     * @return bool
     */
    private function getRequestAccessToken(): bool
    {
        // 获取header中的token参数
        $accessToken = urldecode(trim(strip_tags($this->request->header('access-token', ''))));
        if (empty($accessToken)) {
            return $this->apiResult(-1, 'AccessToken 未传递', [], $this->resultType, []);
        }
        if ($this->isEncrypt) {
            $accessToken = $this->decryptAccessToken($accessToken);
            if (!isset($accessToken['access_token']) || !isset($accessToken['create_time']) || !isset($accessToken['expires_in'])) {
                return $this->apiResult(-1, 'AccessToken 不正确', [], $this->resultType, []);
            }
            $accessToken = $accessToken['access_token'];
        }
        $this->accessToken = $accessToken;
        return true;
    }
}
