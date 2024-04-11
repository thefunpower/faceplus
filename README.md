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




### 开源协议 
 
[Apache License 2.0](LICENSE)