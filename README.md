Robot的整体工作原理是：浏览器通过websocket连接到Server，不断的把聊天内容通过websocket推送到Server，在Server端通过一个PHP脚本监听端口来接收请求，并把用户传输过来的文本作为关键词，到sphinx里进行关键词搜索，把搜索结果通过websocket回传给客户端。

* Sphinx 

	Path: /data/app/sphinx
	
	Version: sphinx-for-chinese-2.1.0
	
	安装：编译安装
	
	./configure --prefix=/data/app/sphinx #因为词库被我转换为为xml的了，所以没有用到mysql
	
	make && make install

	安装完毕，接下来是处理词库文件，生成sphinx索引了：	
	* xml文件的存放目录为：/data/base/sphinx_xml，我通过php脚本：```createIndexXmlFile.php```将词库的文本文件转换为sphinx可以读取的xml文件，这样创建索引的时候就可以直接读取xml文件了。
	* 词库文件放置路径为：/data/base/sphinx_xml/new/ciku.txt，格式为：

		```
		hello
		你好~
		```
		第一行为关键词，第二行为回复语
	* 执行完createIndexXmlFile.php后，我们启动下sphinx并生成索引（后面如果有新增词库的时候我们可以重新生成索引文件或者生成增量索引）
		
		```bash
		/data/app/sphinx/bin/searchd
		/data/app/sphinx/bin/indexer --all --config /data/app/sphinx/conf/sphinx.conf.robot
		```
	* 到此，sphinx的工作完成了，我们等待php来查询关键词就可以了~

	
	[createIndexXmlFile.php]()
	

	conf文件：[sphinx.conf.robot]()
	
* Ciku

	Ciku目录下是词库的源文件，格式如上面所说的

* html
	
	index.html 负责连接远端websocket
	
	核心代码：

	```js
	var ws = new WebSocket("ws://robot.guojianxiang.com/chat");
	ws.onopen = function(){
	    console.log("握手成功");
	    /* ws.send("客官来了..."); */
	};
	ws.onclose = function(event) {
		//WebSocket Status:: Socket Closed
			alert("链接即将断开，页面重新载入中...");
			document.location.reload() ;
	};
	ws.onmessage = function(e){
	    console.log("message:" + e.data);
	    
    	var appendMsg =  
    		"<li class=\"even\">"+
        	"<a class=\"user\" href=\"#\"><img class=\"img-responsive avatar_\" width=\"44px\" height=\"41px\" src=\"images/avatar-1.png?v=2\" alt=\"\">"+
        	"<span class=\"user-name\">小R</span></a>"+
        	"<div class=\"reply-content-box\">"+
        	"<span class=\"reply-time\">"+getTime()+"</span>"+
            "<div class=\"reply-content pr\">"+
            	"<span class=\"arrow\">&nbsp;</span>"+
            	e.data
            	+
            "</div>"+
        	"</div>"+
    		"</li>" ;
	    $('.content-reply-box ').append(appendMsg) ;
	    $('#clientReply').focus() ;



	    $("html,body").animate({scrollTop:$("#clientReply").offset().top},1000)
	};
	```
	
* nginx

	nginx 负责将websocket请求转发到php脚本监听的端口上
	
* php 

	聊天支持的php脚本存放在 php 目录下，通过监听端口，接收并处理socket请求，并把从sphinx里查询出的结果返回给客户端。
	


