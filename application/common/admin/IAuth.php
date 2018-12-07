<?php
namespace app\common\admin;

use app\common\model\User_online;

class IAuth{
  /*
  var $public_key='-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwupSfmDUStGkQuFQ8Mu/ePULd
OS9E//QN1RMfKuR/ss80VMVMjgY85WVX6oRkIn8hGfCJE3XTPBfRxAa0wV2kZQnj
ovAABcLsO0clPnNY8rytEV5vco0emrdxJv9oPw5uSXCjix9qLgHv7OfrvaJhx2MF
B5yeR90WrUBvdvHoZwIDAQAB
-----END PUBLIC KEY-----';
   */
  public static function setToken($pu_key){
    //生成token
    $token=sha1(md5(uniqid(md5(microtime(true)),true)));

    //公匙加密得到token
    openssl_public_encrypt($token,$fake_token,$pu_key);
    $fake_token=base64_encode($token);

    $user_online = new User_online;
    $id = md5($token);
    $user_online->id=$id;
    $user_online->token=$token;
    $user_online->time_out= strtotime("+7 days");
    $user_online->save();

    return ["id"=>$id,"token"=>$fake_token];
  }
}

