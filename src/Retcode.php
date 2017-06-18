<?php
namespace WebIM;

class Retcode
{
    const APP_ID                = 'wxd8cb6e0966856465';
    const WECHAT_PLATFORM_ID    = 21;
    const MESSAGE_MAX_LEN       = 200; //单条消息不能超过200个字
    const WORKER_HISTORY_ID     = 0;
    const SUCCESS               = 1;
    const ERR_NO_LOGIN          = -1001; // 未登录
    const ERR_HANDLE_FREQUENTLY = -1002; // 操作频繁
    const ERR_MESSAGE_MAX_LEN   = -1003; // 超过的错误
    const ERR_ACTIVITY_NOT_OPEN = -1004; // 活动未开启
    const ERR_ACTIVITY_FINISHED = -1005; // 活动已结束
    const ERR_PARAM_LACK        = -1006; // 缺少必传参数
    const ERR_USER_CHECK        = -1007; //
    const ERR_NO_COMMAND        = -1008;
    const ERR_LOGIN          = -1009; // 登录失败
    const ERR_NETWORK           = -1010; // 网络出错
    static public $codeArr = [
        self::SUCCESS               => '成功',
        self::ERR_NO_LOGIN          => '未登录',
        self::ERR_HANDLE_FREQUENTLY => '操作频繁',
        self::ERR_MESSAGE_MAX_LEN   => '长度超出的错误',
        self::ERR_ACTIVITY_NOT_OPEN => '活动未开启',
        self::ERR_ACTIVITY_FINISHED => '活动已结束',
        self::ERR_PARAM_LACK        => '缺少必传参数',
        self::ERR_USER_CHECK        => '检查用户返回出错',
        self::ERR_NO_COMMAND        => '没有该方法',
        self::ERR_LOGIN             => '登录失败',
        self::ERR_NETWORK             => '网络出错，请重试',

    ];
}
