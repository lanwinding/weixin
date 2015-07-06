## 微信商户平台
微信裂变红包&微信红包接口&企业付款

## 版本号
version  v1.1

## 接口功能
* 微信红包接口
* 企业付款接口
* 裂变红包接口

## demo接口传入的参数：
* $mch_billno // 唯一订单号
* $act_name   // 名称
* $openid     // 红包openbid
* $amount     // 红包金额
* $wishing    // 描述
* $sendType   // 发送类型
* $sendNum    // 发送红包数量（裂变）

## 使用时注意
* 配置 WxPay.pub.config.php 中的文件
* 注意商户证书的路径 为绝对路径

## 红包接口注意
* 裂变红包不能传入 share_imgurl watermark_imgurl banner_imgurl 这三个参数，否则会出现签名错误
* 红包金额每次不能少于 100 分， 裂变红包平均每个不能少于100分
* APPSECRET 与 KEY 注意不能放错否则也会出现签名错误的情况
