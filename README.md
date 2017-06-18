
## IM系统请求文档
 
| version | author |time | 
|:-- |:-- |:-- |
|1.0 | terry |2017-06-12 |

* 请求方式: websocket 
* 请求地址: ws://127.0.0.1:9502（本地开发环境地址）


### 初始化直播信息
* 请求的参数（json格式）

| 参数 | 说明 |
|:-- |:-- |
|cmd |值为：Open 必填 |
|data|json数据 |
|data - activityId | 直播活动id |

* 返回数据（json格式）

```
{ 
    "data": {
        "title": "直播标题",
        "details": "直播活动详情内容",
        "shareTitle": "分享标题",
        "shareDigest": "分享摘要",
        "startTime": "10位时间戳", // 开始时间
        "endTime": "10位时间戳",  // 结束时间
        "sharePic": "分享图片", // 分享图片
        "total": 2, // 在线总人数
        "liveStreamingPath": "", // 直播地址
        "appId": "微信appid",
    },
    "code": 1,
    "msg" : "成功",
    "cmd" : "Open", 
}
```

### 总人数广播信息
* 请求的参数（json格式）

| 参数 | 说明 |
|:-- |:-- |
|cmd |值为：Total 必填 |
|data|json数据 |
|data - activityId | 直播活动id |

* 返回数据（json格式）

```
{ 
    "data": {
        "total": 2, // 在线总人数
    },
    "code": 1,
    "msg" : "成功",
    "cmd" : "Total", 
}
```

### 检查登陆接口
| 参数 | 说明 |
|:-- |:-- |
|cmd |值为：CheckLogin 必填 |
|data|json数据 |
|data - activityId |直播活动id |
|data - token |用户的唯一标识 |


* 返回数据（json格式）

```
{
	"code": -1001,
	"msg" : "未登录",
	"cmd" : "CheckLogin",
	"data": {
		"appId": "微信appid",
	},    
}
```

### 登陆接口
| 参数 | 说明 |
|:-- |:-- |
|cmd |值为：Login 必填 |
|data|json数据 |
|data - activityId |直播活动id |
|data - code | 微信code |
|data - type | 用户类型，微信传值：wechat |


* 返回数据（json格式）

```
{
	"code": 1,
	"msg" : "成功",
	"cmd" : "Login",
	"data": {
		"token": "用来校验用户的token",
		"nickname": "微信名",
		"headimgurl": "头像地址",
	},    
}
```



### 即时通讯信息接口
| 参数 | 说明 |
|:-- |:-- |
|cmd |值为：Message 必填 |
|data|json数据 |
|data - activityId |直播活动id |
|data - msg | 信息内容|
|data - code | 微信code |
|data - type | 用户类型，微信传值：wechat |
|data - token | 用户唯一标识 |

* 返回数据（json格式）

```
{
	"code": 1,
	"msg" : "中国",
	"data": " {
        "userName": "用户名",
        "headPic": "头像地址",
        "my" : "1", // 1为自己消息，0为他人的消息
        "time": "2017-06-13 04:06:09", // 时间
        'msg' : "通讯信息" ,
    }",
    "cmd" : "Message"
}
```



### code 说明
| code | 说明 |
|:-- |:-- |
|1|成功 |
|-1001 |用户未登录，禁止发言 |
|-1002 |发言频繁，请稍后再试 |
|-1003 |超过最大字符数，200字 |
|-1004 |直播活动未开启 |
|-1005 |直播活动已结束 |
|-1006 |缺少必传参数 |
|-1007 |网络问题出错，请重试 |
|-1008 |没有该方法 |
|-1009 |登录失败 |











