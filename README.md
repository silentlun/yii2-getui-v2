基于个推RestAPI V2开发的Yii2扩展。
======================
因iOS不显示个推的通知消息，目前只有透传消息功能，如需个推通知消息功能可自行修改Push.php文件。

Installation 安装
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer require --prefer-dist silentlun/yii2-getui-v2 "*"
```

or add

```
"silentlun/yii2-getui-v2": "*"
```

to the require section of your `composer.json` file.


## 使用方法
-----

### 1.配置组件  :

```php
    'components' => [
        'getui' => [
            'class' => 'silentlun\getui\Push',
            'appName' => 'com.xxx.app', //APP包名
            'appId' => 'xxxx', //个推APPID
            'appKey' => 'xxxxx', //个推APPKEY
            'masterSecret' => 'xxxxx', //个推masterSecret
            'host' => 'https://restapi.getui.com',
        ],
    ],
```

### 2.执行群推 pushToAll
```php 
    public function actionPush()
    {
        $data = [
            'title' => '系统通知标题',
            'body' => '系统通知内容',
            'payload' => [], //自定义参数
        ];
        
        $result = Yii::$app->getui->pushToAll($data);
        if ($result['code'] == 0) {
            echo 'success';
        } else {
            echo $result['msg'];
        }
    }
    
```

### 3.执行cid单推 pushToSingleByCid
```php 
    public function actionPush()
    {
        $data = [
            'title' => '系统通知标题',
            'body' => '系统通知内容',
            'payload' => [], //自定义参数
        ];
        $cid = 'd6d5f5df5d8e6b4eb9557bbdd98bb449';
        $result = Yii::$app->getui->pushToSingleByCid($cid, $data);
        if ($result['code'] == 0) {
            echo 'success';
        } else {
            echo $result['msg'];
        }
    }
    
```

### 4.执行cid批量推 pushToListByCid
```php
    public function actionPush()
    {
        $data = [
            'title' => '系统通知标题',
            'body' => '系统通知内容',
            'payload' => [], //自定义参数
        ];
        $cids = [
            'd6d5f5df5d8e6b4eb9557bbdd98b34c9', 
            'd6d5f5df5d8e6b4eb9557bbdd98b4444',
            'd6d5f5df5d8e6b4eb9557bbdd98b4444',
        ];
        
        $result = Yii::$app->getui->pushToListByCid($cids, $data);
        if ($result['code'] == 0) {
            echo 'success';
        } else {
            echo $result['msg'];
        }
    }
    
```

## 其他接口
--------

待完善。。。