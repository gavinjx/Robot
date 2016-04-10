<?php
error_reporting(5);
require_once 'SphinxSearch.php' ;

date_default_timezone_set('Asia/Shanghai') ;

ob_implicit_flush();

define('spinxHost', '127.0.0.1') ;
define('sphinxPort', 9310) ;
define('robotName', '小R') ;
define('userName', '你') ;


$sk=new Sock('127.0.0.1',9011);
$sk->run();
class Sock{
    public $sockets;
    public $users;
    public $master;
    public $cl ;
     
    public function __construct($address, $port){
        $this->master=$this->WebSocket($address, $port);
        $this->sockets=array('s'=>$this->master);
        $this->cl = new SphinxSearch ();
    }
     
     
    function run(){
        while(true){
            $changes=$this->sockets;
            socket_select($changes,$write=NULL,$except=NULL,NULL);
            foreach($changes as $sock){
                if($sock==$this->master){
                    $client=socket_accept($this->master);
                    $this->sockets[]=$client;
                    $this->users[]=array(
                        'socket'=>$client,
                        'shou'=>false
                    );
                }else{
                    $len=socket_recv($sock,$buffer,2048,0);
                    $k=$this->search($sock);
                    //断开
                    if($len<7){
                        $this->close($sock);
                        continue;
                    }
                    if(!$this->users[$k]['shou']){
                        $this->woshou($k,$buffer);
                    }else{
                        $buffer = $this->uncode($buffer);
                        $buffer = $this->getAutoReplyMsg($buffer) ;
                        $this->send($k,$buffer);
                    }
                }
            }
             
        }
         
    }
    
    function getAutoReplyMsg($keyword='')
    {
    	$this->e($keyword."#keyword");
    	
    	$condition = "@word = $keyword " ;
    	$maxMatch = 3 ;
		//取前3条记录
    	$search = array ('host' => spinxHost, 'port' => sphinxPort, 'keyword' => $condition,
    			'limit'=>$maxMatch,'offset'=>0, 'matchMode'=>SPH_MATCH_EXTENDED2) ;
    	
		//如果一直占用cl会使查询结果一直追加，最终导致socket崩溃
		
    	$this->cl = new SphinxSearch ();
    	$results = $this->cl->Search ( $search ) ;
    	$totalResult = $results['total'] ;
    	
    	if($totalResult==0){
    		//没搜索到的时候任意给一个关键词的搜索结果
    		$search = array ('host' => spinxHost, 'port' => sphinxPort, 'keyword' => $condition, 
    			'limit'=>$maxMatch,'offset'=>0, 'matchMode'=>SPH_MATCH_ANY) ;
    		$results = $this->cl->Search ( $search ) ;
    		$totalResult = $results['total'] ;
    	}
    	if($totalResult>0){
    		$maxIndex = $maxMatch<$totalResult?$maxMatch:$totalResult ;
    		$replyIndex = rand(0, ($maxIndex-1)) ;
    		$reply = $results[$replyIndex]['reply'] ;
    		//替换robotname
    		$reply = str_replace("%robotname%", robotName, $reply);
    		//替换【换行】
    		$reply = str_replace("【换行】", "
", $reply);
    		//替换六个点
			//$reply = str_replace("……", "。。。。。。", $reply);
			//替换 XXX
    		$reply = str_replace("XXX", userName, $reply);
    		
    		unset($results) ;
    		
    		if(!$reply){
    			echo "NotFound:".$keyword."\n" ;
    			return robotName.'暂时还不会哦' ;
    		}
    		return htmlspecialchars_decode($reply) ;
    	}else{
    		unset($results) ;
    		echo "NotFound:".$keyword."\n" ;
    		return robotName.'暂时还不会哦' ;
    	}
    }
    function close($sock){
        $k=array_search($sock, $this->sockets);
        socket_close($sock);
        unset($this->sockets[$k]);
        unset($this->users[$k]);
        $this->e("key:$k close");
    }
     
    function search($sock){
        foreach ($this->users as $k=>$v){
            if($sock==$v['socket'])
            return $k;
        }
        return false;
    }
     
    function WebSocket($address,$port){
        $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($server, $address, $port);
        socket_listen($server);
        $this->e('Server Started : '.date('Y-m-d H:i:s'));
        $this->e('Listening on   : '.$address.' port '.$port);
        return $server;
    }
     
     /**
      * 与客户端握手
      */
    function woshou($k,$buffer){
        $buf  = substr($buffer,strpos($buffer,'Sec-WebSocket-Key:')+18);
        $key  = trim(substr($buf,0,strpos($buf,"\r\n")));
     
        $new_key = base64_encode(sha1($key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11",true));
         
        $new_message = "HTTP/1.1 101 Switching Protocols\r\n";
        $new_message .= "Upgrade: websocket\r\n";
        $new_message .= "Sec-WebSocket-Version: 13\r\n";
        $new_message .= "Connection: Upgrade\r\n";
        $new_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n\r\n";
         
        socket_write($this->users[$k]['socket'],$new_message,strlen($new_message));
        $this->users[$k]['shou']=true;
        return true;
         
    }
    
    function uncode($str){
    	$buffer = $str ;
    	$len = $mask = $data = $decoded = null;
    	$len = ord($buffer[1]) & 127;
    	if ($len === 126) {
    		$mask = substr($buffer, 4, 4);
    		$data = substr($buffer, 8);
    	} else if ($len === 127) {
    		$mask = substr($buffer, 10, 4);
    		$data = substr($buffer, 14);
    	} else {
    		$mask = substr($buffer, 2, 4);
    		$data = substr($buffer, 6);
    	}
    	for ($index = 0; $index < strlen($data); $index++) {
    		$decoded .= $data[$index] ^ $mask[$index % 4];
    	}
    	return $decoded;
    }
     
    function code($msg){
    	$buffer = $msg ;
    	$len = strlen($buffer);
    	if($len<=125)
    	{
    		return "\x81".chr($len).$buffer;
    	}
    	else if($len<=65535)
    	{
    		return "\x81".chr(126).pack("n", $len).$buffer;
    	}
    	else
    	{
    		return "\x81".char(127).pack("xxxxN", $len).$buffer;
    	}
    }
     
     
    function send($k,$msg){
        $this->e($msg);
        $msg = $this->code($msg);
        socket_write($this->users[$k]['socket'],$msg,strlen($msg));
    }
     
     
    function e($str){
        $path=dirname(__FILE__).'/replylog.txt';
        $str='Time:'.date('Y-m-d H:i:s').'#'.$str."\n";
        error_log($str,3,$path);
    }
}
?>
