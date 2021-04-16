<?php
namespace silentlun\getui;

use yii\base\Component;
use yii\base\Exception;
use silentlun\getui\request\push\GTPushRequest;
use silentlun\getui\request\push\GTPushMessage;
use silentlun\getui\request\push\GTPushChannel;
use silentlun\getui\request\push\ios\GTIos;
use silentlun\getui\request\push\ios\GTAps;
use silentlun\getui\request\push\ios\GTAlert;
use silentlun\getui\request\push\android\GTAndroid;
use silentlun\getui\request\push\android\GTUps;
use silentlun\getui\request\push\android\GTThirdNotification;
use silentlun\getui\request\push\GTSettings;
use silentlun\getui\request\push\GTStrategy;
use silentlun\getui\request\push\GTAudienceRequest;

/**
 * @author allen
 * 'components' => [
        'getui' => [
            'class' => 'silentlun\getui\Push',
            'appName' => 'com.xxx.app', //APP包名
            'appId' => 'xxxx', //个推APPID
            'appKey' => 'xxxxx', //个推APPKEY
            'masterSecret' => 'xxxxx', //个推masterSecret
            'host' => 'https://restapi.getui.com',
        ],
    ],
 *
 */

class Push extends Component
{
    /**
     * @var string 个推AppId
     */
    public $appId;
    
    /**
     * @var string 个推AppKey
     */
    public $appKey;
    
    /**
     * @var string 个推masterSecret
     */
    public $masterSecret;
    
    /**
     * @var string APP包名
     */
    public $appName;
    
    /**
     * @var string 个推推送接口域名
     */
    public $host;
    
    /**
     * 应用群发
     */
    public function pushToAll($data = [])
    {
        $api = $this->clientApi();
        $push = $this->getParam($data);
        return $api->pushApi()->pushAll($push);
    }
    
    /**
     * 应用单推接口
     */
    public function pushToSingleByCid($cid, $data = [])
    {
        $api = $this->clientApi();
        $push = $this->getParam($data);
        $push->setCid($cid);
        return $api->pushApi()->pushToSingleByCid($push);
    }
    
    /**
     * cid批量推接口
     */
    public function pushToListByCid($cids, $data = [])
    {
        $api = $this->clientApi();
        
        $push = $this->getParam($data);
        $push->setGroupName("usergroup");
        $result = $api->pushApi()->createListMsg($push);
        if ($result['code'] == 0) {
            $taskid = $result['data']['taskid'];
        } else {
            throw new Exception("获取taskid失败:" . $result['msg']);
        }
        $user = new GTAudienceRequest();
        $user->setIsAsync(true);
        $user->setTaskid($taskid);
        $clientids = array_chunk(array_filter($cids, "self::cidsFilter"), 200);
        foreach ($clientids as $cids){
            $user->setCidList($cids);
            $result = $api->pushApi()->pushListByCid($user);
        }
        return $result;
    }
    
    /**
     * 推送参数
     */
    protected function getParam($data = [])
    {
        $push = new GTPushRequest();
        $push->setRequestId(self::getMicroTime());
        $message = new GTPushMessage();
        $message->setTransmission(json_encode($data));
        $push->setPushMessage($message);
        //设置setting
        $set = new GTSettings();
        $set->setTtl(259200000);
        $strategy = new GTStrategy();
        $strategy->setDefault(GTStrategy::STRATEGY_THIRD_FIRST);
        $set->setStrategy($strategy);
        $push->setSettings($set);
        //厂商推送消息参数
        $pushChannel = new GTPushChannel();
        //ios
        $ios = new GTIos();
        $ios->setType("notify");
        $ios->setAutoBadge("+1");
        $ios->setPayload(json_encode($data['payload']));
        //$ios->setApnsCollapseId("apnsCollapseId");//使用相同的apns-collapse-id可以覆盖之前的消息
        //aps设置
        $aps = new GTAps();
        $aps->setContentAvailable(0);
        //$aps->setSound("com.gexin.ios.silenc");
        //$aps->setCategory("category");
        //$aps->setThreadId("threadId");
        
        $alert = new GTAlert();
        $alert->setTitle($data['title']);
        $alert->setBody($data['body']);
        $alert->setActionLocKey("ActionLocKey");
        $alert->setLocKey("LocKey");
        $alert->setLocArgs(array("LocArgs1","LocArgs2"));
        $alert->setLaunchImage("LaunchImage");
        $alert->setTitleLocKey("TitleLocKey");
        $alert->setTitleLocArgs(array("TitleLocArgs1","TitleLocArgs2"));
        //$alert->setSubtitle("Subtitle");
        //$alert->setSubtitleLocKey("SubtitleLocKey");
        //$alert->setSubtitleLocArgs(array("subtitleLocArgs1","subtitleLocArgs2"));
        $aps->setAlert($alert);
        $ios->setAps($aps);
        
        $pushChannel->setIos($ios);
        
        //安卓
        $android = new GTAndroid();
        $ups = new GTUps();
        $thirdNotification = new GTThirdNotification();
        $thirdNotification->setTitle($data['title']);
        $thirdNotification->setBody($data['body']);
        $thirdNotification->setClickType(GTThirdNotification::CLICK_TYPE_INTENT);
        $thirdNotification->setIntent('intent:#Intent;action=android.intent.action.oppopush;launchFlags=0x14000000;component='.$this->appName.'/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title='.$data['title'].';S.content='.$data['body'].';S.payload='.json_encode($data['payload']).';end');
        //$thirdNotification->setPayload("payload");
        //$thirdNotification->setNotifyId(456666);
        //$ups->addOption("HW","badgeAddNum",1);
        //$ups->addOption("OP","channel","Default");
        //$ups->addOption("OP","aaa","bbb");
        //$ups->addOption(null,"a","b");
        
        $ups->setNotification($thirdNotification);
        $android->setUps($ups);
        $pushChannel->setAndroid($android);
        $push->setPushChannel($pushChannel);
        
        //print_r(json_encode($push->getApiParam()));exit;
        return $push;
    }
    
    protected function clientApi()
    {
        return new GTClient($this->host, $this->appKey, $this->appId, $this->masterSecret);
    }
    
    protected static function cidsFilter($cid)
    {
        return $cid != 'null' & !empty($cid);
    }
    
    protected static function getMicroTime()
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = ($sec . substr($usec, 2, 3));
        return $time;
    }
}