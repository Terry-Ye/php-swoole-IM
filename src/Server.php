<?php
namespace WebIM;

use Swoole;
use Swoole\Filter;

class Server extends Swoole\Protocol\CometServer
{
    /**
     * @var Store\File;
     */
    protected $storage;
    protected $users;
    // 活动信息
    protected $activityInfo;

    /**
     * 上一次发送消息的时间
     * @var array
     */
    protected $lastSentTime = [];

    /**
     * [$clientId_activity 客户id对应活动]
     * @var array
     */
    protected $clientArr = [];

    public function __construct($config = array())
    {
        //检测日志目录是否存在
        $logDir = dirname($config['webim']['log_file']);
        if (!is_dir($logDir))
        {
            mkdir($logDir, 0777, true);
        }
        if (!empty($config['webim']['log_file']))
        {
            $logger = new Swoole\Log\FileLog($config['webim']['log_file']);
        }
        else
        {
            $logger = new Swoole\Log\EchoLog(true);
        }
        $this->setLogger($logger);   //Logger

        /**
         * 使用文件存储聊天信息
         */
        $this->storage = new Storage($config['redis']);

        // $this->origin = $config['server']['origin'];
        parent::__construct($config);
    }

    /**
     * 打开连接
     */
    public function cmdOpen($clientId, $activityId, $data)
    {
        // 保存登录态 返回在线人数
        $activityInfo = $this->storage->getActivityInfo($data['data']['activityId']);
        $addStatus    = $this->storage->addOnline($clientId, $activityId);
        if(!$addStatus)
        {
            $this->sendErrorMessage($clientId,
                Retcode::ERR_NETWORK,
                Retcode::$codeArr[Retcode::ERR_NETWORK],
                'Open'
            );
            return;
        }

        $this->users                = $this->storage->getOnlineUsers($activityInfo['id']);
        $total                      = count($this->users);
        $this->clientArr[$clientId] = $activityInfo['id'];
        $data                       = [
            'startTime'         => $activityInfo['start_time'],
            'endTime'           => $activityInfo['end_time'],
            'shareTitle'        => $activityInfo['share_title'],
            'shareDigest'       => $activityInfo['share_digest'],
            'details'           => $activityInfo['details'],
            'title'             => $activityInfo['title'],
            'total'             => $total,
            'liveStreamingPath' => '',
            'appId' => Retcode::APP_ID,
        ];
        $sendData = [
            'data'  => $data,
            'code'  => Retcode::SUCCESS,
            'msg'   => Retcode::$codeArr[Retcode::SUCCESS],
            'cmd'   => 'Open',
        ];
        $broadcastData = [
            "cmd" => 'Total',
            'data'  => [ "total" => $total],
            'code'  => Retcode::SUCCESS,
            'msg'   => Retcode::$codeArr[Retcode::SUCCESS],
        ];


        $this->sendJson($clientId, $sendData);
        $this->broadcastJson($clientId, $broadcastData);
        $this->log("client:".$clientId.",cmd-Open,total:".$total);
    }

    /**
     * 检查是否登陆
     */
    public function cmdCheckLogin($clientId, $activityId, $data)
    {
        $code      = Retcode::SUCCESS;
        $msg       = Retcode::$codeArr[Retcode::SUCCESS];
        $userInfo = $this->storage->getUserInfo($data['data']['token']);

        if(empty($userInfo)) {
                $code = Retcode::ERR_NO_LOGIN;
                $msg  = Retcode::$codeArr[Retcode::ERR_NO_LOGIN];
        }
        $data = [
            'appId' => Retcode::APP_ID,
        ];
        $return_data = [
                "code" => $code,
                "msg"  => $msg,
                "cmd"  => 'CheckLogin',
                "data" => $data,

            ];
        $this->log("client:".$clientId.",cmd-CheckLogin");
        $this->sendJson($clientId, $return_data);

    }


    /**
     * 用户登录
     */
    public function cmdLogin($clientId, $activityId, $data)
    {
        switch ($data['data']['type']) {
            case 'wechat':
                $userInfo = [
                    'unionId' => 'otvZywXmY44dYLK-SKNZhbNBr0P0',
                    'openid' => 'ocuF9ws38ML445UxFZZJsLqqtOv0',
                    'nickname' => 'æ±<9f>æ<9b><89>æ<9d>±',
                    'headimgurl' => 'http://wx.qlogo.cn/mmopen/n3dIuTJ4qbptRVaGHsNicpqqJKMTWBHibw27liahl8O4l2do1YN07S4OwHEoojqWWRHDjCtt2OjrKWbDSlBsPibX3lulGEQBdJ0W/0',
                ];
                break;
            case 'auth':
                break;
            default:
                $this->sendErrorMessage($clientId, Retcode::ERR_LOGIN, Retcode::$codeArr[Retcode::ERR_LOGIN].',登录类型出错', 'Login');
                break;
        }

        if($userInfo['code'] != 0)
        {
            $this->sendErrorMessage($clientId, Retcode::ERR_LOGIN, Retcode::$codeArr[Retcode::ERR_LOGIN].",".$userInfo['msg'], 'Login');
            return;
        }

        $loginStatus = $this->storage->login($clientId, $activityId, $userInfo['data']);
        if(false === $loginStatus)
        {
            $this->sendErrorMessage($clientId, Retcode::ERR_LOGIN, Retcode::$codeArr[Retcode::ERR_LOGIN], 'Login');
            return;
        }

        $this->clientArr[$clientId] = $activityId;
        $userInfo['data']['token'] = $this->storage->getToken($activityId, $userInfo['data']['openid']);
        unset($userInfo['data']['unionId']);
        unset($userInfo['data']['openid']);
        $return_data = [
                "code" => Retcode::SUCCESS,
                "msg"  => Retcode::$codeArr[Retcode::SUCCESS],
                "cmd"  => 'Login',
                "data" => $userInfo['data'],
            ];
        $this->log("client:".$clientId.",cmd-Login,userInfo :".var_export($userInfo, true));
        $this->sendJson($clientId, $return_data);

    }

    /**
     * 任务执行
     */
    function onTask($serv, $taskId, $fromId, $data)
    {
        $req = unserialize($data);
        if ($req)
        {
            switch($req['cmd'])
            {
                // 数据入库
                case 'addHistory':
                    if (empty($req['msg']))
                    {
                        $req['msg'] = '';
                    }
                    $this->storage->addHistory($req['fd'], $req['msg'], $req['userInfo']);
                    break;
                default:
                    break;
            }
        }
    }


    /**
     * 发送信息请求
     */
    function cmdMessage($clientId, $activityId, $data)
    {
        // 检查登录态, 返回用户信息
        $userInfo = $this->storage->getUserInfo($data['data']['token']);
        if(empty($userInfo))
        {
            $this->sendErrorMessage($clientId,
                Retcode::ERR_NO_LOGIN,
                Retcode::$codeArr[Retcode::ERR_NO_LOGIN],
                'Message'
            );
            return;
        }
        // 限制200个字内
        if (mb_strlen($data['data']['msg']) > Retcode::MESSAGE_MAX_LEN)
        {
            $this->sendErrorMessage($clientId,
                Retcode::ERR_MESSAGE_MAX_LEN,
                Retcode::$codeArr[Retcode::ERR_MESSAGE_MAX_LEN],
                'Message'
            );
            return;
        }

        $now = time();
        $lastSentTime = empty($this->lastSentTime[$clientId]) ? 0 : $this->lastSentTime[$clientId];
        //上一次发送的时间超过了允许的值，每N秒可以发送一次
        if (  ($now - $lastSentTime) <= $this->config['webim']['send_interval_limit'])
        {
            $this->sendErrorMessage($clientId,
                Retcode::ERR_HANDLE_FREQUENTLY,
                Retcode::$codeArr[Retcode::ERR_HANDLE_FREQUENTLY],
                'Message'
            );
            return;
        }

        //记录本次消息发送的时间
        $this->lastSentTime[$clientId] = $now;

        $data['data']['userName']   = $userInfo['nickname'];
        $data['data']['headPic']    = $userInfo['headimgurl'];
        $data['data']['my']         = 0;

        $sendData = [
            'code' => Retcode::SUCCESS,
            'msg'  => Retcode::$codeArr[Retcode::SUCCESS],
            'data' => $data['data'],
            'cmd'  => 'Message',
        ];
        $this->log("client#".$clientId.",#nickname-".$userInfo['nickname'].",cmd-Message,msg:".var_export($data['data'], true));
        // 群发广播
        $this->broadcastJson($clientId, $sendData);

        // 入库操作
        $this->getSwooleServer()->task(serialize(array(
            'cmd'      => 'addHistory',
            'msg'      => $data['data'],
            'userInfo' => $userInfo,
            'fd'       => $clientId,
        )), Retcode::WORKER_HISTORY_ID);


    }




    /**
     * 接收到消息时
     * @see WSProtocol::onMessage()
     */
    function onMessage($clientId, $ws)
    {
        $msg = json_decode($ws['message'], true);

        if (empty($msg['cmd']))
        {
            $this->sendErrorMessage($clientId, Retcode::ERR_PARAM_LACK, "invalid command", '');
            return;
        }
        $func = 'cmd'.$msg['cmd'];
        if (method_exists($this, $func))
        {
            $this->$func($clientId, $msg['data']['activityId'], $msg);
        }
        else
        {
            $this->sendErrorMessage($clientId, Retcode::ERR_NO_COMMAND, "command $func no support.", $msg['cmd']);
            return;
        }
    }

    /**
     * 下线时，
     */
    public function onExit($clientId)
    {

        $activityId = $this->clientArr[$clientId];
        $before = count($this->users);
        unset($this->clientArr[$clientId]);
        // 退出
        $this->storage->logout($clientId, $activityId);
        // 更新在线用户列表
        $this->users = $this->storage->getOnlineUsers($activityId);
        $this->log("client#".$clientId.",#after-".count($this->users).",before-".$before);
        $broadcastData = [
            "cmd" => 'Total',
            'data'  => [ "total" => count($this->users)],
            'code'  => Retcode::SUCCESS,
            'msg'   => Retcode::$codeArr[Retcode::SUCCESS],
        ];

        $this->broadcastJson($clientId, $broadcastData);
    }

    /**
     * 发送错误信息
    * @param $clientId
    * @param $code
    * @param $msg
     */
    public function sendErrorMessage($clientId, $code, $msg, $cmd)
    {
        $this->sendJson($clientId, ['code' => $code, 'msg' => $msg, 'cmd' => $cmd ]);
    }

    /**
     * 发送JSON数据
     * @param $clientId
     * @param $array
     */
    public function sendJson($clientId, $array)
    {
        $msg = json_encode($array);
        if ($this->send($clientId, $msg) === false)
        {
            unset($this->clientArr[$clientId]);
            $this->storage->logout($clientId, $activityId);
            $this->close($clientId);
        }
    }

    /**
     * 广播JSON数据
     * @param $clientId
     * @param $array
     */
    function broadcastJson($sesionId, $data)
    {
        $msg = json_encode($data);
        $data['data']['my'] = 1;
        $msg_my = json_encode($data);

        $this->broadcast($sesionId, $msg, $msg_my);
    }


    function broadcast($currentSessionId, $msg, $msg_my)
    {
        foreach ($this->users as $key => $clientId)
        {

            if ($currentSessionId != $clientId)
            {
                $this->send($clientId, $msg);
            }else
            {
                $this->send($clientId, $msg_my);
            }

        }

    }


    function onFinish($serv, $taskId, $data)
    {
        $this->send(substr($data, 0, 32), substr($data, 32));
    }
}


