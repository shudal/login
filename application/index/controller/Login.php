<?php

namespace app\index\controller;

use app\common\model\Account;
use app\common\model\User_online;
use think\Controller;
use think\Request;

class Login extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
  public function index(){
    $private_key='-----BEGIN RSA PRIVATE KEY-----
MIICWwIBAAKBgQC8xDeHjWG2pFuK8gMoLEZ/bm+VH8L/hYFZJPztoiV0aKP5phRD
MZhtPcUFuOjDRddvu/u+3Wj9HB+MJevscpw2AwpvJJeickrhABl79udBVOcRMSBP
wKF00xNoHevbORQU03a/PQqUiTMIkmT/k4NavRKo0OUMeTQcSWEQMAokbQIDAQAB
AoGAQlDs8UJKQdAcGQRM96AWZE54BPvTldFhT+Aeu51rayoX8WzXUYPq+PXqccg0
feXbefWgy70dVU68BpCMAdWB6xzNyyQx2iD/dkCC1w1j/xfoep9zhcsuQCLTktbZ
4tKrl+vSIoiO2v5qazwjHfEXjcBJNMxYoj+Zg2CQbwin2kECQQDueDVM4ZtXinJ7
ejRf9zAF0KAHcet8Z+9/wEv/syMiY5bLQ7uXqsRFJOdGQrT74J5aOVIftQzf5xCG
xVBlIHVTAkEAyqSjq/sSdV/tt8G1WiaLZRAv9ndcoZPyXfUdbOPuNXvkxsw0oLu/
wFkY3PbFea/p13mSOjM3XkjrzcYYG8gHPwJAVLwD/HCB2SZJrZRrvdnAh6Bs7JhP
G6J22IcEujP1/Qc0Er/bjXXRTdxiDXYwhvt2aQrLIpcbnwekuK6t9XEGHwJAWD5n
082q1RgoEbw1+AMO8ryg1khWOzqM8aN6499B7WJ9VqC4TkJUFzP1YsvHZN1ZDG8x
YUzKULGaleost3RcywJAGv8AeJDBVmmWx/TzEX8Yampv/hyyuh1QYvSKigNPoW3W
sRRPR8xTnD+yif7kKXCfDv0/0PY7GvNpOvlaHHnx7w==
-----END RSA PRIVATE KEY-----';
	$public_key='-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwupSfmDUStGkQuFQ8Mu/ePULd
OS9E//QN1RMfKuR/ss80VMVMjgY85WVX6oRkIn8hGfCJE3XTPBfRxAa0wV2kZQnj
ovAABcLsO0clPnNY8rytEV5vco0emrdxJv9oPw5uSXCjix9qLgHv7OfrvaJhx2MF
B5yeR90WrUBvdvHoZwIDAQAB
-----END PUBLIC KEY-----';
$pi_key = openssl_pkey_get_private($private_key);
$pu_key = openssl_pkey_get_public($public_key);
    $encrypted=request()->param("str");
    //传输中+号被转换成了空格，现在需要换回来
    $encrypted = str_replace(" ","+",$encrypted);
      $decrypted="";
      openssl_private_decrypt(base64_decode($encrypted),$decrypted,$pi_key);
      $loginI=explode("&",$decrypted);
      $username = substr($loginI[0],9);
      //发送的密码是经过sha256加密原密码后的字符串
      $password = substr($loginI[1],9);

      // 服务器以sha256形式存储密码
      $the_timestamp = substr($loginI[2],10);
      $time_ago=strtotime("now - 1 minute");
      if($the_timestamp<$time_ago){ //密文中包含的时间要在前一分钟内
        return json(["flag"=>"failure","description"=>"登录信息已过期"]);
      }
      $account = Account::where('username','=',$username)->limit(1)->select();
      if(count($account)){
        $account=$account[0];
        $reall_password=$account->password;
        if($password==$reall_password){
          //生成token
          $token_str=md5(uniqid(md5(microtime(true)),true));
          $token_str=sha1($token_str);
          $real_token=$token_str;
          //dump($token_str);
          //使用公匙加密token
          openssl_public_encrypt($token_str,$token_str,$pu_key);
          $token_str=base64_encode($token_str);

          //生成token过期时间，使用strtotime()函数
          $time_out=strtotime("+7 days");

          $user_online = new User_online;
          $id=md5($token_str);
          $user_online->id=$id;
          $user_online->token=$real_token;
          $user_online->time_out=$time_out;
          $user_online->save();

          $data=["flag"=>"success","id"=>$id,"token"=>$token_str];
          return json($data);
        }
        else $tai=0;
      }
      $data=["flag"=>"failure","description"=>"用户名或密码错误"];
      return json($data);

    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
      
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read()
    {
      $tai=0;
      $error_message=array("登陆信息已经过期，请重新登录");

      $id=request()->param('id');
      //检测token是否存在
      $user=User_online::where('id','=',$id)->limit(1)->select();
      //dump($user);
      if(count($user)){ //如果token存在
        $token = $user[0]->token;
        $time_out=$user[0]->time_out;
        

        $time_now=strtotime("now");
        //检测token是否过期
        if($time_out>$time_now){//如果token未过期
          //dump("wei");
          $the_username=request()->param('username');
          $the_timestamp=request()->param('timestamp');
          

          if($the_timestamp>strtotime("- 1 minute")){//如果是在之前一分钟内发出的
            //dump("2");
            $the_str="id=".$id."&timestamp=".$the_timestamp."&token=".$token."&username=".$the_username;
            $the_sign=request()->param('sign');

            $real_sign=hash("sha256",$the_str);

            if($real_sign==$the_sign){
              //dump("3");
              $the_user = Account::where('username','=',$the_username)->limit(1)->select()[0];
              $datas=["username"=>$the_user->username,"nickname"=>$the_user->nickname,"create_time"=>$the_user->create_time];
              return json($datas);
            }
            
          }
        }
      }
      else $error=0;

      $data=["flag"=>"failure","description"=>$error_message[$error]];
      return json($data);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }
}
