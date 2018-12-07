<?php

namespace app\index\controller;

use app\common\model\Account;
use app\common\model\User_online;
use think\Controller;
use think\Request;

use app\common\admin\IAuth;

class Login extends Controller
{
	var $private_key='-----BEGIN RSA PRIVATE KEY-----
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
	var $public_key='-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwupSfmDUStGkQuFQ8Mu/ePULd
OS9E//QN1RMfKuR/ss80VMVMjgY85WVX6oRkIn8hGfCJE3XTPBfRxAa0wV2kZQnj
ovAABcLsO0clPnNY8rytEV5vco0emrdxJv9oPw5uSXCjix9qLgHv7OfrvaJhx2MF
B5yeR90WrUBvdvHoZwIDAQAB
-----END PUBLIC KEY-----';
public function index(){

      if(!request()->isPost){
        return json(["flag"=>"failure","description"=>"请求方法错误"]);
      }
      $pi_key = openssl_pkey_get_private($this->private_key);
      $pu_key = openssl_pkey_get_public($this->public_key);

      $encrypted=request()->param("str");
      $encrypted = str_replace(" ","+",$encrypted); //传输中+号被转换成了空格，现在需要换回来

      openssl_private_decrypt(base64_decode($encrypted),$decrypted,$pi_key);
      $loginI=explode("&",$decrypted);
      $username = substr($loginI[0],9);
      //发送的密码是经过sha256加密原密码后的字符串
      $password = substr($loginI[1],9);
      // 服务器以sha256形式存储密码
      $the_timestamp = substr($loginI[2],10);


      //是否已经过期
      if($the_timestamp<strtotime("now - 1 minute")){ //密文中包含的时间要在前一分钟内
        return json(["flag"=>"failure","description"=>"登录信息已过期"]);
      }


      //用户名是否存在
      try{
        $account = model("Account")->get(["username"=>$username]);
      }catch(\Exception $e){
        $this->error($e->getMessage());
      }
      if(!$account){
        return json(["flag"=>"failure","description"=>"不存在的用户名"]);
      }

      // 密码是否正确
      $reall_password=$account->password;
      if($password==$reall_password){
          $sign = IAuth::setToken($pu_key);
          $data=["flag"=>"success","id"=>$sign["id"],"token"=>$sign["token"]];
          return json($data);
        }
      return json( $data=["flag"=>"failure","description"=>"用户名或密码错误"]);

    }

    public function create()
    {
      if(!request()->isPost()){
        return json(["flag"=>"failure","description"=>"请求方法错误"]);;
      }
      $pi_key = openssl_pkey_get_private($this->private_key);
      $pu_key = openssl_pkey_get_public($this->public_key);

      $encrypted=request()->param("str");
      $encrypted=str_replace(" ","+",$encrypted);

      $decrypted="";
      openssl_private_decrypt(base64_decode($encrypted),$decrypted,$pi_key);

      $regisI=explode("&",$decrypted);
      $username=substr($regisI[0],9);
      $password=substr($regisI[1],9);
      $password=hash("sha256",$password);
      $nickname=substr($regisI[2],9);

      $users=Account::where("username",'=',$username)->select();
      if(count($users)){
        return json(["flag"=>"failure","description"=>"用户名已存在"]);
      }

      $account = new Account;
      $account->username=$username;
      $account->password=$password;
      $account->create_time=strtotime("now");
      $account->save();

      return json(["flag"=>"success"]);


    }

    public function read()
    {

      $id=request()->param('id');
      try{
        $user = User_online::get($id);
      }catch(\Exception $e){
        $this->error($e->getMessage());
      }
      if($user){ 
        $token = $user->token;
        $time_out=$user->time_out;

        //检测token不能过期
        if($time_out>strtotime("now")){
          $the_username=request()->param('username');
          $the_timestamp=request()->param('timestamp');

          if($the_timestamp>strtotime("- 1 minute")){//如果是在之前一分钟内发出的
            $the_str="id=".$id."&timestamp=".$the_timestamp."&token=".$token."&username=".$the_username;
            $the_sign=request()->param('sign');
            $real_sign=hash("sha256",$the_str);
            if($real_sign==$the_sign){
              $the_user = Account::where('username','=',$the_username)->limit(1)->select()[0];
              $datas=["username"=>$the_user->username,"nickname"=>$the_user->nickname,"create_time"=>$the_user->create_time];
              return json($datas);
            }
            
          }
        }
      }
      return json($data=["flag"=>"failure","description"=>"出错啦"]);
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
