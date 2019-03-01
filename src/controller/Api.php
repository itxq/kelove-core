<?php
/**
 *  ==================================================================
 *        文 件 名: Api.php
 *        概    要: API 控制器基类
 *        作    者: IT小强
 *        创建时间: 2019-02-27 19:28:23
 *        修改时间:
 *        copyright (c) 2016 - 2019 mail@xqitw.cn
 *  ==================================================================
 */

namespace kelove\controller;

use think\facade\Request;
use think\facade\Session;

/**
 * Class Api
 * @title API 控制器基类
 * @code -1 AccessToken或AccessSession 未传递、不正确、不匹配、已过期
 * @code -2 请求方式错误 必须为 指定的请求方式（POST\GET ...）
 * @package kelove\controller
 * @author IT小强
 * @createTime 2019-02-27 20:18:33
 */
abstract class Api extends Base
{
    /**
     * 验证方式：token或session
     */
    protected const CHECK_ANY = 1;
    
    /**
     * 验证方式：仅token
     */
    protected const CHECK_TOKEN = 2;
    
    /**
     * 验证方式：仅session
     */
    protected const CHECK_SESSION = 3;
    
    /**
     * 验证方式：token和session
     */
    protected const CHECK_ALL = 4;
    
    /**
     * @var int API验证类型
     */
    protected $checkType = self::CHECK_ANY;
    
    /**
     * @var string 返回数据的格式
     */
    protected $resultType = 'json';
    
    /**
     * @var string session 标识
     */
    protected $sessionKey = 'access-session';
    
    /**
     * @var string token标识
     */
    protected $tokenKey = 'access-token';
    
    /**
     * @var string 当前请求的操作名
     */
    protected $action = '';
    
    /**
     * @var string 当前请求的AccessToken
     */
    protected $accessToken = '';
    
    /**
     * @var string 当前请求的AccessSession
     */
    protected $accessSession = '';
    
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
     * @var array 验证成功后获取到的用户信息
     */
    protected $userInfo = [];
    
    /**
     * @var string 错误代码
     */
    protected $errorMessage = '未知错误';
    
    /**
     * @title 初始化
     * @author IT小强
     * @createTime 2019-02-27 19:45:26
     */
    protected function initialize(): void
    {
        // 获取action token session数据
        $this->getApiCheckInfo();
        // 请求方式检查
        $this->requestTypeCheck();
        // 接口验证
        if (!in_array($this->action, array_map('strtolower', $this->allowAction), true)) {
            $this->apiCheck();
        }
        parent::initialize();
    }
    
    /**
     * @title 接口认证
     * @author IT小强
     * @createTime 2019-02-27 20:17:54
     * @return bool
     */
    protected function apiCheck(): bool
    {
        if ($this->checkType === self::CHECK_SESSION) {
            $check = $this->sessionCheck();
        } else if ($this->checkType === self::CHECK_TOKEN) {
            $check = $this->tokenCheck();
        } else if ($this->checkType === self::CHECK_ALL) {
            $check = $this->sessionCheck() && $this->tokenCheck();
        } else if ($this->checkType === self::CHECK_ANY) {
            $check = $this->sessionCheck() || $this->tokenCheck();
        } else {
            $check = false;
        }
        if (!$check) {
            return $this->apiResult(-1, $this->errorMessage);
        }
        return true;
    }
    
    /**
     * @title token验证
     * 验证成功时：赋值userInfo
     * 验证失败时：赋值errorMessage
     * @author IT小强
     * @createTime 2019-02-27 20:20:13
     * @return bool
     */
    protected function tokenCheck(): bool
    {
        return true;
    }
    
    /**
     * @title session验证
     * 验证成功时：赋值userInfo
     * 验证失败时：赋值errorMessage
     * @author IT小强
     * @createTime 2019-02-27 20:19:09
     * @return bool
     */
    protected function sessionCheck(): bool
    {
        return true;
    }
    
    /**
     * @title 请求方式检查
     * @author IT小强
     * @createTime 2019-02-27 19:54:46
     * @return bool
     */
    protected function requestTypeCheck(): bool
    {
        if (!Request::isPost() && in_array($this->action, array_map('strtolower', $this->postAction), true)) {
            return $this->apiResult(-2, '请求方式错误 必须为POST');
        }
        if (!Request::isGet() && in_array($this->action, array_map('strtolower', $this->getAction), true)) {
            return $this->apiResult(-2, '请求方式错误 必须为GET');
        }
        return true;
    }
    
    /**
     * @title 获取action token session数据
     * @author IT小强
     * @createTime 2019-02-27 19:43:16
     */
    protected function getApiCheckInfo(): void
    {
        // 获取action
        $this->action = strtolower(Request::action());
        // 获取token
        $this->accessToken = urldecode(trim(strip_tags(Request::header($this->tokenKey, ''))));
        // 获取session
        $this->accessSession = Session::get($this->sessionKey, '');
    }
    
    /**
     * @title 成功时接口返回
     * @author IT小强
     * @createTime 2019-02-27 20:35:05
     * @param string $msg - 提示信息
     * @param mixed $data - 返回的数据
     * @param array $header - 发送的Header信息
     * @param string $type - 返回数据格式
     * @return bool
     */
    protected function apiResultSuccess(string $msg = '', $data = [], array $header = [], string $type = ''): bool
    {
        return $this->apiResult(1, $msg, $data, $header, $type);
    }
    
    /**
     * @title 失败时接口返回
     * @author IT小强
     * @createTime 2019-02-27 20:35:00
     * @param string $msg - 提示信息
     * @param mixed $data - 返回的数据
     * @param array $header - 发送的Header信息
     * @param string $type - 返回数据格式
     * @return bool
     */
    protected function apiResultError(string $msg = '', $data = [], array $header = [], string $type = ''): bool
    {
        return $this->apiResult(0, $msg, $data, $header, $type);
    }
    
    /**
     * @title 接口返回
     * @author IT小强
     * @createTime 2019-02-27 20:35:15
     * @param int $code - 返回的code
     * @param string $msg - 提示信息
     * @param mixed $data - 返回的数据
     * @param array $header - 发送的Header信息
     * @param string $type - 返回数据格式
     * @return bool
     */
    protected function apiResult(int $code, string $msg = '', $data = [], array $header = [], string $type = ''): bool
    {
        $type = empty($type) ? $this->resultType : $type;
        $this->result($data, $code, $msg, $type, $header);
        return false;
    }
}
