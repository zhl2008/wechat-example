<?php
    namespace Home\Controller;
    use Think\Controller;
    class TestController extends Controller {

        private $tmp_path = '/tmp/'; 
        private $salt = 'haozigege_888'; 
        private $username = ''; 
        private $real_path = '';
        private $info = array();
        private $all_weapons = array();

        function __construct() {
            ;
        }

        public function index(){
            // 这是使用了Memcached来保存access_token
            S(array(
                'type'=>'File',
                'prefix'=>'think',
                'expire'=>0
            ));

            // 开发者中心-配置项-AppID(应用ID)
            $appId = 'wxd6008121287b7ade';
            // 开发者中心-配置项-AppSecret(应用密钥)
            $appSecret = '8c7ae0f2d170ed7273425b232e88ee8f';
            // 开发者中心-配置项-服务器配置-Token(令牌)
            $token = 'HAOZIGEGE';
            // 开发者中心-配置项-服务器配置-EncodingAESKey(消息加解密密钥)
            $encodingAESKey = 'dunwzieXcCPmVoTYwXG1tU8WkrhAdVCaPBP065MyHJr';

            // wechat模块 - 处理用户发送的消息和回复消息
            $wechat = new \Gaoming13\WechatPhpSdk\Wechat(array(
                'appId' => $appId,
                'token' => 	$token,
                'encodingAESKey' =>	$encodingAESKey //可选
            ));
            // api模块 - 包含各种系统主动发起的功能
            $api = new \Gaoming13\WechatPhpSdk\Api(
                array(
                    'appId' => $appId,
                    'appSecret'	=> $appSecret,
                    'get_access_token' => function(){
                        // 用户需要自己实现access_token的返回
                        return S('wechat_token');
                    },
                    'save_access_token' => function($token) {
                        // 用户需要自己实现access_token的保存
                        S('wechat_token', $token);
                    }
                )
            );

            // 获取微信消息
            $msg = $wechat->serve();
            $this->username = $msg->FromUserName;
            $this->real_path = $this->tmp_path . md5($this->salt . $this->username);
            if(!is_dir($this->real_path)){
                mkdir($this->real_path);
                system('cp ./Public/* '.$this->real_path);
                $this->info['weapon'] = 'fist';
                $this->info['status'] = 100;
                $this->info['bullet'] = substr(base64_encode(file_get_contents($this->real_path.'/'.$this->info['weapon'])),0,100);
                
            }else{
                $this->info = json_decode(file_get_contents($this->real_path . '/info'),TRUE);
            }


            // 回复文本消息
            if ($msg->MsgType == 'text') {
                if(!strstr($msg->Content,':')){
                    $wechat->reply($this->menu());
                }else{
                    $tmp = explode(':' , $msg->Content);
                    $func = $tmp[0];
                    $arg = $tmp[1];
                    switch ($func) {
                        case 'f':
                            $wechat->reply($this->f());
                            break;
                        case 'p':
                            $wechat->reply($this->p($arg));
                            break;
                        case 'r':
                            $wechat->reply($this->r());
                            break;
                        case 's':
                            $wechat->reply($this->s($arg));
                            break;
                        case 'c':
                            $wechat->reply($this->c());
                            break;
                        case 'sh':
                            $wechat->reply($this->sh());
                        default:
                            $wechat->reply("Unsupported method!\n");
                            break;
                    }
                }

            }else if($msg->MsgType == 'image') {
                $wechat->reply($this->u($msg->PicUrl));
            }else{
                $wechat->reply("Unsupported message type!\n");
            }

            // store user info
            file_put_contents($this->real_path . '/info' , json_encode($this->info));
        }

        public function menu(){
            $menu = '';
            $menu .= "Welcome to Hence's unknown battle ground!\n";
            $menu .= "you have following options to utilize:\n";
            $menu .= "1. find a weapon (f:)\n";
            $menu .= "2. pick up a weapon (for instance: p:kar98)\n";
            $menu .= "3. reload a weapon (r:)\n";
            $menu .= "4. shoot a target (s:xxx)\n";
            $menu .= "5. check ur weapon's status (c:)\n";
            $menu .= "6. upload ur weapon (just upload image-file)\n";
            $menu .= "7. show ur weapon (sh:)\n";
            return $menu;

        }

        public function f(){
            $res = "Here are your weapons:\n";
            
            foreach (scandir($this->real_path) as $value) {
                if($value != '.'  && $value != '..' && $value != 'info'){
                    $res .= $value . "\n";
                    array_push($this->all_weapons,$value);
                }
            }
            return $res;
        }

        public function p($weapon){
            $res = "";
            if(!is_file($this->real_path . '/' . $weapon)){
                $res = "No such weapon! U bitch!\n";
            }else{
                $this->info['weapon'] = $weapon;
                $this->info['bullet'] = substr(base64_encode(file_get_contents($this->real_path . '/' . $this->info['weapon'])),0,100);
                // no bullets initially
                $this->info['status'] = 0;
                $res = "You have changed ur weapon to " . $weapon . "\n";
            }
            return $res;
        }

        public function r(){
            $res = "";
            $this->info['status'] = 100;
            $res = "Reload OK!";
            return $res;
        }

        public function s($target){
            $res = "";
            if(!preg_match('/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/', $target)){
                $res = "This is not a target! R U kidding?\n";
            }else{
                // make sure u have bullets
                if($this->info['status']>0){
                    $this->info['status'] = $this->info['status'] - 1;
                    $bullet = $this->info['bullet'][100 - $this->info['status']];
                    system("ping -c 1 -W 1 -p '" . bin2hex($bullet) ."' ".$target . " 2>&1 1>/dev/null");
                    $res = "You hit it once!\n";
                }else{
                    $res = "You have run out of bullets\n";
                }
            }
            return $res;
        }

        public function c(){
            $res = "Ur weapon is " . $this->info['weapon'] . "\n";
            $res .= $this->info['status'] . " bullet(s) left\n";
            return $res;
        }

        public function u($PicUrl){
            shell_exec("wget " . $PicUrl . " -O " . $this->real_path . '/' . $this->info['weapon']." 2>&1 1>/dev/null");
            return "Update your weapon successfully!";
        }

        public function sh()
        {
           return "Sorry, not support yet!\n";
        }
    
    }
