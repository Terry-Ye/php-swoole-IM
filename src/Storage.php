<?php
namespace WebIM;

use Predis\Client;


class Storage
{
    /**
     * @var \redis
     */
    protected $redis;

    const PREFIX = 'message';

    public function __construct($config)
    {
        $this->redis = new Client([
            'scheme' => $config['scheme'],
            'host'   => $config['host'],
            'port'   => $config['port'],
        ]);

        $this->redis->del(self::PREFIX.':online');
        $this->config = $config;
    }

    public function login($clientId, $activityId, $userInfo)
    {
        $token = $this->getToken($activityId, $userInfo['openid']);
        // key 的有效期统一后台直播活动结束清除
        $userStatus = $this->redis->set(self::PREFIX.'_'.$token, json_encode($userInfo));
        return $userStatus;

    }

    public function getToken($activityId, $openId)
    {
        $token = substr(md5($activityId.$openId),0,16);
        return $token;
    }
    public function addOnline($clientId, $activityId)
    {
        $onlineStatus = $this->redis->sAdd(self::PREFIX.':online'.$activityId, $clientId);
        return $onlineStatus;
    }



    public function getUserInfo($token)
    {

        $userInfo = $this->redis->get(self::PREFIX.'_'.$token);
        return json_decode($userInfo, true);

    }



    public function logout($clientId, $activityId)
    {
        // $this->redis->del(self::PREFIX.'_'.$activityId.'_'.$clientId);
        $this->redis->sRem(self::PREFIX.':online'.$activityId, $clientId);
    }
    /**
     * 用户在线用户列表
     */
    public function getOnlineUsers($activityId)
    {
        return $this->redis->sMembers(self::PREFIX . ':online'.$activityId);
    }

    function addHistory($clientId, $msg, $userInfo)
    {
        $id = base64_encode(openssl_random_pseudo_bytes(16));
        table('tb_message_history')->put(array(
            'id'          => $id,
            'client_id'   => $clientId,
            'user_name'   => $userInfo['nickname'],
            'user_id'     => $userInfo['unionId'],
            'message'     => $msg['msg'],
            'activity_id' => $msg['activityId'],
            'create_time' => time(),
            'type'        => empty($msg['type']) ? '' : $msg['type'],
        ));
    }

    /**
     * 获取活动基本信息
     */
    public function getActivityInfo($activityId)
    {
        $activityInfo = table('tb_activity')->get($activityId, 'id');
        $activity_arr = [
            'id'           => $activityInfo->id,
            'title'        => $activityInfo->title,
            'details'      => $activityInfo->details,
            'share_title'  => $activityInfo->share_title,
            'share_digest' => $activityInfo->share_digest,
            'start_time'   => $activityInfo->start_time,
            'end_time'     => $activityInfo->end_time,
        ];
        return $activity_arr;
    }
}
