# 基于thinkphp5.1的app登录api
### 功能
* 登录
* 注册
* 获取信息（登陆保持
### 不足
* 对注册时的账号密码没要求
* 对登陆注册的次数没要求
### 说明
* 全部功能在index/Loginx控制器里
* 至于数据表结果，请导入主文件下的login.sql后查看。含account表，use_online表。
* 使用两对rsa
'''
服务端和客户端各有一只rsa公匙和私匙，但他们不是一对。登录注册时用客户端加密，服务端
用私匙解密。服务端用公匙加密token，客户端用私匙解密token。
'''
* 登录保持的一些说明
```
登录成功后，服务器返回明文id，和加密后的token（这个token其实类似于盐的作用。之后客户端
每次与服务器的通信都要带上签名。签名使用sha256算法按特定顺序加密[url参数和解密后的token]。服务器接受到请求
后，根据id从数据库中得到token,按照同样的方法加密[url参数和token]，如果和客户端的签名同，则认可客户端的请求。
```
### 环境
* php7.1+
* mysql
