# faceplus
 
### 安装


~~~
composer require thefunpower/faceplus
~~~

### 使用

~~~
$file = __DIR__.'/face/d.jpg';
$faceplus = new faceplus; 
//人脸检测 
//$face = $faceplus->get_detect($file);

$token = '9c5598****936cb25bac2be4b';
//搜索
$find = $faceplus->get_search($token);
pr($find); 
~~~

搜索返回数据

~~~
(
    [data] => Array
        (
            [facetoken] => 9c5598d16abac2be4b
            [confidence] => 97.237
            [flag] => 1
        )

    [code] => 0
    [type] => success
)
~~~

## mysql 

~~~

CREATE TABLE `faceplus_detect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uni` varchar(255) NOT NULL,
  `face_token` varchar(255) NOT NULL,
  `outer_id` varchar(255) NOT NULL,
  `gender` varchar(50) NOT NULL,
  `age` varchar(10) NOT NULL,
  `beauty` varchar(200) NOT NULL,
  `glass` varchar(10) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

CREATE TABLE `faceplus_outer_id` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `outer_id` varchar(255) NOT NULL,
  `face_tokens` text NOT NULL,
  `num` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

~~~


### 开源协议 
 
[Apache License 2.0](LICENSE)