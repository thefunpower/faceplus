<?php  
/**
* https://www.faceplusplus.com.cn/ 
*/
class faceplus{
    protected $api_key;
    protected $api_secret;
    protected $auth_url = 'https://api-cn.faceplusplus.com/sdk/v3/auth';
    protected $detect_url = 'https://api-cn.faceplusplus.com/facepp/v3/detect';
    protected $search_url = 'https://api-cn.faceplusplus.com/facepp/v3/search';
    protected $faceset_create_url = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/create';
    protected $faceset_update_url = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/update';
    protected $faceset_delete_url = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/delete';
    protected $addface_url = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/addface';
    protected $face_url = 'https://api-cn.faceplusplus.com/facepp/v3/faceset/getdetail';
    public function __construct(){
        $this->api_key    = get_config("faceplug_api_key");
        $this->api_secret = get_config("faceplug_api_secret"); 
    }
    // for sdk,暂时不用
    protected function get_auth(){
        $arr = [
            'api_key'=>$this->api_key,
            'api_secret'=>$this->api_secret,
            'auth_duration'=>1,
            'auth_msg'=>mt_rand(1,9999)
        ];
        $client = guzzle_http();
        $res    = $client->request('POST', $this->auth_url,[
            'form_params' => $arr
        ]);
        $res = (string)$res->getBody();  
        pr($res);
    }
    /**
    * 生成outer_id
    */
    protected function get_outer_id(){
        $limit = 1;
        $find = db_get("faceplus_outer_id",[],1);
        if(!$find || $find['count'] > $limit){
            $outer_id = time();
            db_insert("faceplus_outer_id",[
                'outer_id'=>$outer_id,
                'count'=>0
            ]);
            return $outer_id;
        }else{
            return $find['outer_id'];
        }
    }
    /**
     * 更新outer_id中人脸数量
     */
    protected function update_outer_id_num($outer_id,$num = 1){ 
        $find = db_get("faceplus_outer_id",['outer_id'=>$outer_id],1);
        $num = $find['num']+$num;
        db_update("faceplus_outer_id",['num'=>$num],['outer_id'=>$outer_id]);
    }
    /**
    * 设置人脸库
    * 创建一个人脸的集合 FaceSet，用于存储人脸标识 face_token。
    * 一个 FaceSet 能够存储10000个 face_token。 
    * 试用API Key可以创建1000个FaceSet，正式API Key可以创建10000个FaceSet。
    */
    protected function set_faceset($outer_id,$face_token){   
        $find = db_get("faceplus_outer_id",['outer_id'=>$outer_id],1); 
        $arr = [ 
             'api_key'   =>$this->api_key,
             'api_secret'=>$this->api_secret,
             'face_tokens'=>$face_token,  
             'outer_id'=>$outer_id,   
        ];
        try {
            $client = guzzle_http();
            $res    = $client->request('POST', $this->faceset_create_url,[
                'form_params' => $arr
            ]);
            $res = (string)$res->getBody();  
            $res = json_decode($res,true);  
        } catch (\Exception $e) {
            
        } 
    }
    /**
    * 添加人脸
    */
    protected function add_face($outer_id,$face_token){ 
        $this->get_face($outer_id);
        $arr = [ 
             'api_key'   =>$this->api_key,
             'api_secret'=>$this->api_secret,
             'face_tokens'=>$face_token,  
             'outer_id'=>$outer_id,   
        ];
        $client = guzzle_http();
        $res    = $client->request('POST', $this->addface_url,[
            'form_params' => $arr
        ]);
        $res = (string)$res->getBody();  
        $res = json_decode($res,true);   
        $this->update_outer_id_num($outer_id,1);
    }
    /**
    * 获取人脸库中的数据
    */
    protected function get_face($outer_id){ 
        $arr = [ 
             'api_key'   =>$this->api_key,
             'api_secret'=>$this->api_secret, 
             'outer_id'=>$outer_id,   
        ]; 
        $client = guzzle_http();
        $res    = $client->request('POST', $this->face_url,[
            'form_params' => $arr
        ]);
        $res = (string)$res->getBody();  
        $res = json_decode($res,true);   
        $all = $res['face_tokens']; 
        return $all;
    }

    /**
    * 人脸检测 
    */
    public function get_detect($file){
        if(!file_exists($file)){   
            return ['code'=>250,'type'=>'error','msg'=>'图片不存在'];
        }
        $content = file_get_contents($file); 
        $uni = md5($content);
        $all = db_get("faceplus_detect",[
                    'uni'=>$uni,
                ]);
        if($all){
            return ['data'=>$all,'code'=>0,'msg'=>'已存在，无需要调用接口','type'=>'success'];
        } 
        $arr = [
             'image_base64'=>base64_encode($content),
             'api_key'   =>$this->api_key,
             'api_secret'=>$this->api_secret,
             'return_landmark'=>1, 
             'return_attributes'=>"gender,age,smiling,headpose,facequality,blur,eyestatus,emotion,ethnicity,beauty,mouthstatus,eyegaze,skinstatus"
        ];
        $client = guzzle_http();
        $res    = $client->request('POST', $this->detect_url,[
            'form_params' => $arr
        ]);
        $res = (string)$res->getBody();  
        $res = json_decode($res,true); 
        $face_token_in = [];
        if($res['faces']){ 
            foreach($res['faces'] as $v){
                $outer_id = $this->get_outer_id();
                $face_token = $v['face_token'];
                $attributes = $v['attributes'];
                $gender = strtolower($attributes['gender']['value']);
                $age = $attributes['age']['value'];
                $beauty = $attributes['beauty'][$gender.'_score'];
                $glass = $attributes['glass']['value'];
                $insert = [
                    'uni'=>$uni,
                    'outer_id'=>$outer_id,
                    'face_token'=>$face_token,
                    'gender'=>$gender,
                    'age'=>$age,
                    'beauty'=>$beauty,
                    'glass'=>$glass,
                    'created_at'=>now(),
                ];
                $face_token_in[] = $face_token;
                $find = db_get("faceplus_detect",[
                    'face_token'=>$face_token,
                ],1);
                if(!$find){ 
                    db_insert("faceplus_detect",$insert);
                    $this->set_faceset($outer_id,$face_token);
                    $this->add_face($outer_id,$face_token);
                    $this->update_outer_id_num($outer_id,1);
                } 
            }
            $all = db_get("faceplus_detect",[
                'face_token'=>$face_token,
            ]);
            return ['data'=>$all,'code'=>0,'msg'=>'','type'=>'success'];
        }
        return ['type'=>'error','code'=>250,'msg'=>'检测失败'];
    }
    /**
    * 取所有的outer_id
    */
    protected function get_all_outer_id(){
        $all = db_get("faceplus_outer_id",[]);
        $in = [];
        foreach($all as $v){
            $in[] = $v['outer_id'];
        }
        return $in;
    }
    /**
    * 人脸比对
    */
    public function get_search($find_face_token){ 
        $all = $this->get_all_outer_id(); 
        foreach($all as $outer_id){
            $arr = [
                 'face_token'=>$find_face_token,
                 'api_key'   =>$this->api_key,
                 'api_secret'=>$this->api_secret,
                 'outer_id'=>$outer_id,  
            ]; 
            $client = guzzle_http();
            $res    = $client->request('POST', $this->search_url,[
                'form_params' => $arr
            ]);
            $res = (string)$res->getBody();  
            $res = json_decode($res,true); 
            $results = $res['results'][0];
            $confidence = $results['confidence'];
            $face_token = $results['face_token']; 
            if($confidence > 85){
                return ['data'=>['facetoken'=>$face_token,'confidence'=>$confidence,'flag'=>true],'code'=>0,'type'=>'success'];
            }
        }
        return ['data'=>['facetoken'=>$face_token,'confidence'=>$confidence,'flag'=>false],'code'=>250,'type'=>'error'];
    }
}