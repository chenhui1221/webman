<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace plugin\nat\app\process;

use plugin\nat\app\model\NatApp;
use plugin\nat\app\model\NatUser;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Frame;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Timer;

/**
 * 内网穿透服务端
 */
class Server
{
    /**
     * 是否开启debug
     * @var bool
     */
    protected $debug = false;

    /**
     * 所有应用
     * @var array
     */
    protected $apps = [];

    /**
     * @var string
     */
    protected $auth;

    /**
     * 连接空闲59秒则关闭
     */
    const IDLE_TIMEOUT = 59;

    /**
     * 内网客户端连接
     * @var array
     */
    protected $natClientConnections = [];

    /**
     * 配置客户端连接，用于推送配置
     * [user_id => con, ...]
     * @var TcpConnection[]
     */
    protected $natSettingClientConnections = [];

    /**
     * 构造函数
     * @param bool $debug
     */
    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * 进程启动时
     * @return void
     */
    public function onWorkerStart()
    {
        $this->loadApps();
        $this->periodicDetectAppsUpdate();
        $this->natSettingClientPing();
    }

    /**
     * 加载数据库中域名配置
     * @return void
     */
    protected function loadApps()
    {
        $this->apps = NatApp::pluck('user_id', 'domain')->toArray();
        $this->debugLog("加载apps\n" .var_export($this->apps, true));
    }

    /**
     * 定时检测是否有用户更新应用
     * @return void
     */
    protected function periodicDetectAppsUpdate()
    {
        $checker = function () {
            // 记录最近更新时间
            static $lastUpdateTime;
            if (!$lastUpdateTime) {
                $lastUpdateTime = NatApp::max('updated_at') ?? date('Y-m-d H:i:s');
                return;
            }
            // 查找最新添加或更改的应用
            $items = NatApp::withTrashed()->where('updated_at', '>', $lastUpdateTime)->orderBy('updated_at')->get();
            $updatedUserIds = [];
            // 如果有新加或更新记录
            foreach ($items as $item) {
                $userId = $item['user_id'];
                $updatedUserIds[$userId] = $userId;
                $lastUpdateTime = $item['updated_at'];
                $domain = $item['domain'];
                // 应用已经删除
                if ($item->deleted_at) {
                    unset($this->apps[$domain]);
                    continue;
                }
                // 新增应用
                if (!isset($this->apps[$domain])) {
                    $this->apps[$domain] = $userId;
                }
            }
            // 向对应客户端推送配置
            foreach ($updatedUserIds as $userId) {
                $this->pushSettingByUserId($userId);
            }
        };
        $checker();
        Timer::add(2, $checker);
    }

    /**
     * 根据用户向内网客户端推送配置
     * @param $userId
     * @return void
     */
    protected function pushSettingByUserId($userId)
    {
        $connection = $this->natSettingClientConnections[$userId] ?? null;
        if ($connection) {
            $items = NatApp::where('user_id', $userId)->get()->keyBy('domain');
            $connection->send(json_encode(['type' => 'setting', 'setting' => $items], JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 当内网客户端或者外网浏览器发来消息时
     * @param TcpConnection $connection
     * @param Request $request
     * @return void
     */
    public function onMessage(TcpConnection $connection, Request $request)
    {
        $host = $request->header('nat-host');
        $token = $request->header('nat-token');
        $request->connection = null;
        // ==== 处理内网客户端连接 ====
        if ($request->method() === 'OPTIONS' && $token && $host) {
            if (!$userID = NatUser::where('token', $token)->value('user_id')) {
                echo "验证内网客户端失败：token不存在 \n" . $request->rawHead() . PHP_EOL;
                return;
            }
            if ($request->header('nat-setting-client')) {
                // 处理内网配置下发客户端连接
                $this->handleNatSettingClient($connection, $userID);
                return;
            }
            if (!isset($this->apps[$host])) {
                echo "验证内网客户端失败：$host 对应的应用不存在:\n" . $request->rawHead() . PHP_EOL;
                return;
            }
            // 处理内网客户端连接
            $this->handleNatClientRequest($connection, $request, $host);
            return;
        }

        // ==== 处理外网浏览器连接 ====
        $this->handleBrowserRequest($connection, $request);
    }

    /**
     * 浏览器发来请求时
     * @param $connection
     * @param $request
     * @return void
     */
    protected function handleBrowserRequest(TcpConnection $connection, $request)
    {
        $host = $request->host();

        $ip = $this->getRealIp($connection, $request);
        $this->debugLog("接受外网浏览器客户端连接 Ip:$ip with host $host");

        // 如果对用的域名没有内网客户端，则返回503
        if (empty($this->natClientConnections[$host])) {
            $connection->send(new Response(503, [], 'Service Unavailable'));
            return;
        }
        // 取出一个内网客户端连接
        $clientConnection = array_pop($this->natClientConnections[$host]);
        // 发送请求
        $clientConnection->send((string)$request);
        // 成为代理
        $clientConnection->pipe($connection);
        $connection->protocol = null;
        $connection->pipe($clientConnection);
        $connection->onClose = function () use ($clientConnection, $host) {
            $clientConnection->close();
            unset($this->natClientConnections[$host][$clientConnection->id]);
            if ($clientConnection->timeoutTimer) {
                Timer::del($clientConnection->timeoutTimer);
                $clientConnection->timeoutTimer = null;
            }
        };
    }

    /**
     * 内网客户端发起连接时
     * @param TcpConnection $connection
     * @param $request
     * @param $host
     * @return void
     */
    protected function handleNatClientRequest(TcpConnection $connection, $request, $host)
    {
        $ip = $this->getRealIp($connection, $request);
        $this->debugLog("接受内网客户端连接 Ip:$ip with $host");

        // 将协议http改为tcp，成为tcp代理
        $connection->protocol = null;
        $connection->lastBytesRead = 0;
        // 连接关闭时将内网客户端连接从natClientConnections删除
        $connection->onClose = function ($connection) use ($host) {
            unset($this->natClientConnections[$host][$connection->id]);
            if ($connection->timeoutTimer) {
                Timer::del($connection->timeoutTimer);
                $connection->timeoutTimer = null;
            }
        };
        $this->natClientConnections[$host][$connection->id] = $connection;
        // 连接长时间不通讯，则关闭
        $connection->timeoutTimer = Timer::add(static::IDLE_TIMEOUT, function () use ($connection, $ip, $host) {
            // 已读字节与之前相同，说明 IDLE_TIMEOUT 时间内连接一直未通讯，则执行关闭，内网客户端会重连
            if ($connection->lastBytesRead == $connection->bytesRead || $connection->getStatus() === TcpConnection::STATUS_CLOSED) {
                Timer::del($connection->timeoutTimer);
                $connection->timeoutTimer = null;
                $connection->close();
                unset($this->natClientConnections[$host][$connection->id]);

                $ip = $connection->getRemoteIpAddress();
                $this->debugLog("内网客户端连接" . static::IDLE_TIMEOUT . "秒未通讯执行正常关闭 Ip:$ip");
            }
            $connection->lastBytesRead = $connection->bytesRead;
        });
    }

    /**
     * 处理内网下发配置连接
     * @param TcpConnection $connection
     * @param $userId
     * @return void
     */
    protected function handleNatSettingClient(TcpConnection $connection, $userId)
    {
        $ip = $connection->getRemoteAddress();
        $this->debugLog("接受内网配置下发客户端连接 Ip:$ip");

        // 将协议切换未Frame
        $connection->protocol = Frame::class;
        $connection->userId = $userId;
        $this->natSettingClientConnections[$userId] = $connection;
        // 连接关闭时删除
        $connection->onClose = function ($connection) use ($userId) {
            if(isset($connection->userId)) {
                unset($this->natSettingClientConnections[$userId]);
            }
        };
        $connection->onMessage = function () {
            // 收到的是 心跳
        };

        // 推送配置
        $this->pushSettingByUserId($userId);
    }

    /**
     * 心跳
     * @return void
     */
    public function natSettingClientPing()
    {
        Timer::add(10, function(){
            foreach ($this->natSettingClientConnections as $connection) {
                $connection->send(json_encode(['type' => 'ping']));
            }
        });
    }

    /**
     * 输出日志
     * @param $msg
     * @return void
     */
    protected function debugLog($msg)
    {
        if ($this->debug) {
            echo date('Y-m-d H:i:s') . " 内网穿透服务端：$msg" . PHP_EOL;
        }
    }

    /**
     * 获取真实ip
     * @param TcpConnection $connection
     * @param Request $request
     * @return array|string|null
     */
    protected function getRealIp(TcpConnection $connection, Request $request)
    {
        $ip = $connection->getRemoteAddress();
        if (str_starts_with($ip, '127.0.0.1')) {
            // 要求nginx有 proxy_set_header X-Real-IP $remote_addr; 配置
            $ip = $request->header('x-real-ip', $ip);
        }
        return $ip;
    }
}
