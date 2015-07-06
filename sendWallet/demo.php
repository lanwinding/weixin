<?php


/**
 * +---------------------------------------------------+ 
 * +        红包接口，裂变红包，企业付款demo           +
 * +---------------------------------------------------+
 * 请求方式
 * post AND get
 *
 * 必传参数
 * 1、mch_billno 唯一订单号，不可重复
 * 2、act_name   商家名称
 * 3、openid     openid 
 * 4、amount      红包金额
 * 5、wishing    红包描述
 * 6、sendType   发送类型
 * 7、sendNum    发送数量（裂变红包）
 * 
 * 注意：
 * 1、微信红包金额最小不能小于 1元
 * 2、如发送的金额小于 1元时，只能选择企业付款
 * 3、为保证安全性 请求时要带上签名
 *     \__  先以 Util::generateSignature 方法生成签名
 *		|_  在之前的参数基础上增加  signature、timestamp、nonce 这三个验证 签名的参数
 *
 * @version v1.0
 * @copyright  小农民科技
 * @author sixian
 * @createtime 2015-07-06 13:20
 */


require_once "./WxPay.pub.config.php";
require_once "./sendWallet_app.php";


$mch_billno = isset($_REQUEST['mch_billno']) ? htmlspecialchars(trim($_REQUEST['mch_billno'])) : '';// 唯一订单号
$act_name   = isset($_REQUEST['act_name'])   ? htmlspecialchars(trim($_REQUEST['act_name']))   : '';// 名称
$openid     = isset($_REQUEST['openid'])     ? htmlspecialchars(trim($_REQUEST['openid']))     : '';// 红包openbid 
$amount     = isset($_REQUEST['amount']) 	 ? htmlspecialchars(trim($_REQUEST['amount']))      : ''; // 红包金额
$wishing    = isset($_REQUEST['wishing']) 	 ? htmlspecialchars(trim($_REQUEST['wishing']))    : ''; // 描述

$sendType    = isset($_REQUEST['sendType']) 	 ? htmlspecialchars(trim($_REQUEST['sendType']))    : ''; // 发送类型
$sendNum     =  isset($_REQUEST['sendNum']) ? htmlspecialchars(trim($_REQUEST['sendNum'])) : '';// 发送红包数量（裂变）

$sendWallet_app = new sendWallet();
// 如果金额小于1元时 使用企业付款  否则 使用红包功能
if(empty($sendType)){
	$sendType = $amount < 100 ? 'transfers' : 'redpack';
}

// 签名验证
$signature = isset($_REQUEST['signature']) ? $_REQUEST['signature'] : '';
$timestamp = isset($_REQUEST['timestamp']) ? $_REQUEST['timestamp'] : '';
$nonce     = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';

// 线上开启以下签名代码
// if (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] == 'production') {		
// 	if ( $sendWallet_app->checkSignature($signature, $timestamp, $nonce) ) {
// 	    $_SESSION[SESSIONUSER] = $_GET['nonce'];
// 	}else{		 
// 		// 出现错误时 下发调试信息 到微信   
// 	    $res = $sendWallet_app->debugErrorSendWx("sendWalletApi签名不正确" , array('真实签名' => WxPayConf_pub::privateKey));

// 		$sendData['真实签名'] =  WxPayConf_pub::privateKey;

// 	    $res = AppUtil::debugErrorSendWx("sendWalletApi签名不正确" , $sendData, true, 5, '');
// 	    echo json_encode( array('return_code' => 'FAIL', 'return_msg' => 'sendWalletApi签名不正确', 'return_ext' => $res ) );
// 	    exit;
// 	}
// }



switch ($sendType) {
	case 'redpack':
			$SendRedpack = $sendWallet_app; //红包 与 企业付款公用 类

			$SendRedpack->set_mch_billno( $mch_billno );  				//唯一订单号
			//$SendRedpack->set_mch_id( WxPayConf_pub::MCHID ); 	     	// 商户号 默认已在配置文件中配置
			//$SendRedpack->set_wxappid( WxPayConf_pub::APPID );			// appid  默认已在配置文件中配置
			$SendRedpack->set_nick_name( $act_name );                		// 提供方名称     idea0086
			$SendRedpack->set_send_name( $act_name );                 	 	
			// 红包发送者名称  商户名称
			$SendRedpack->set_re_openid( $openid);						    // 用户在wxappid下的openid

			$SendRedpack->set_total_amount( $amount );  // 付款金额，单位分
			$SendRedpack->set_min_value( $amount );     // 最小红包金额，单位分
			$SendRedpack->set_max_value( $amount );     // 最大红包金额，单位分（ 最小金额等于最大金额： min_value=max_value =total_amount）
			$SendRedpack->set_total_num(1);		 	   // 红包发放总人数
			$SendRedpack->set_wishing($wishing);	   // 红包祝福语 感谢您参加猜灯谜活动，祝您元宵节快乐！
			$SendRedpack->set_client_ip( walletWeixinUtil::getRealIp() ); //调用接口的机器Ip地址

			$SendRedpack->set_act_name( $act_name );  // 活动名称 猜灯谜抢红包活动
			$SendRedpack->set_act_id(1);  					 // 活动id
			$SendRedpack->set_remark( $wishing ); 			 // 备注信息 猜越多得越多，快来抢！
			$SendRedpack->set_logo_imgurl('');			     // 商户logo的url
			$SendRedpack->set_share_content('');			 // 分享文案
			$SendRedpack->set_share_url('');				 // 分享链接
			$SendRedpack->set_share_imgurl('');				 // 分享的图片url
			$SendRedpack->set_nonce_str( walletWeixinUtil::getNonceStr() ); // 随机字符串

			// 得到签名和其它设置的 xml 数据
			$getNewData  = $SendRedpack->getSendRedpackXml($SendRedpack);
			$data 		 = walletWeixinUtil::curl_post_ssl($getNewData['api_url'], $getNewData['xml_data']);
			$res 		 = @simplexml_load_string($data,NULL,LIBXML_NOCDATA);

            // TODO::红包通知		            
            $callback                  = json_encode($res);
            $callbackArr               = json_decode($callback, true);
            $debugArrayNew['openid']   = $openid;
            $debugArrayNew['状态']     = @$callbackArr['return_code'];
            $debugArrayNew['返回信息'] = @$callbackArr['return_msg'] . "\n";

            $debugArrayNew['活动名称'] = $act_name;
            $debugArrayNew['发放金额'] = $amount."【分】";
            $debugArrayNew['发放类型'] = '微信红包';
            $debugArrayNew['红包编号'] = $mch_billno . "\n";

            $wxuser = $this->dao_read->getWxuserByopenid($openid, '', 1);
            $wxuser = @$wxuser[0];

            @$debugArrayNew['用户'] =  $wxuser['nickname'];
            @$debugArrayNew['性别'] =  $wxuser['sex'] == 1 ? '男' : '女';
            @$debugArrayNew['头像'] = "<a href='{$wxuser['headimgurl']}'>查看</a>";
			AppUtil:: debugErrorSendWx('发红包接口返回', $debugArrayNew, true, 4);

			if (!empty($res)){
				echo json_encode($res);
			}else{
				echo json_encode( array('return_code' => 'FAIL', 'return_msg' => 'redpack_接口出错', 'return_ext' => array() ));						
			}

			exit;
		break;

	case 'group_redpack':						
			$SendRedpack = $sendWallet_app; //红包 与 企业付款公用 类

			$SendRedpack->set_mch_billno( $mch_billno );  				//唯一订单号
			//$SendRedpack->set_mch_id( WxPayConf_pub::MCHID ); 	     	// 商户号 默认已在配置文件中配置
			//$SendRedpack->set_wxappid( WxPayConf_pub::APPID );			// appid  默认已在配置文件中配置
			$SendRedpack->set_nick_name( $act_name );                		// 提供方名称     idea0086
			$SendRedpack->set_send_name( $act_name );                 	 	
			// 红包发送者名称  商户名称
			$SendRedpack->set_re_openid( $openid);						    // 用户在wxappid下的openid

			$SendRedpack->set_total_amount( $amount );  // 付款金额，单位分
			$SendRedpack->set_total_num( $sendNum );		 	   // 红包发放总人数
			$SendRedpack->set_wishing($wishing);	   // 红包祝福语 感谢您参加猜灯谜活动，祝您元宵节快乐！
			$SendRedpack->set_client_ip( walletWeixinUtil::getRealIp() ); //调用接口的机器Ip地址
			$SendRedpack->set_act_name( $act_name );  // 活动名称 猜灯谜抢红包活动
			$SendRedpack->set_remark( $wishing ); 			 // 备注信息 猜越多得越多，快来抢！
			$SendRedpack->set_logo_imgurl('');			     // 商户logo的url
			$SendRedpack->set_share_content('');			 // 分享文案
			$SendRedpack->set_share_url('');				 // 分享链接
			$SendRedpack->set_share_imgurl('');				 // 分享的图片url
			$SendRedpack->set_nonce_str( walletWeixinUtil::getNonceStr() ); // 随机字符串

			$SendRedpack->set_amt_type('ALL_RAND');				 // 红包金额设置方式ALL_RAND—全部随机,商户指定总金额和红包发放总人数，由微信支付随机计算出各红包金额
			$SendRedpack->set_amt_list(''); //各红包具体金额，自定义金额时必须设置，单位分
			$SendRedpack->set_watermark_imgurl(''); //背景水印图片url
			$SendRedpack->set_banner_imgurl(''); //红包详情页面的banner图片url


			// 得到签名和其它设置的 xml 数据
			$getNewData  = $SendRedpack->getSendgroupredpackXml($SendRedpack);
			$data 		 = walletWeixinUtil::curl_post_ssl($getNewData['api_url'], $getNewData['xml_data']);
			$res 		 = @simplexml_load_string($data,NULL,LIBXML_NOCDATA);

            // TODO::红包通知		            
            $callback                  = json_encode($res);
            $callbackArr               = json_decode($callback, true);
            $debugArrayNew['openid']   = $openid;
            $debugArrayNew['状态']     = @$callbackArr['return_code'];
            $debugArrayNew['返回信息'] = @$callbackArr['return_msg'] . "\n";

            $debugArrayNew['活动名称'] = $act_name;
            $debugArrayNew['发放金额'] = $amount."【分】";
            $debugArrayNew['发放类型'] = '裂变红包';
            $debugArrayNew['红包编号'] = $mch_billno . "\n";

            $wxuser = $this->dao_read->getWxuserByopenid($openid, '', 1);
            $wxuser = @$wxuser[0];

            @$debugArrayNew['用户'] =  $wxuser['nickname'];
            @$debugArrayNew['性别'] =  $wxuser['sex'] == 1 ? '男' : '女';
            @$debugArrayNew['头像'] = "<a href='{$wxuser['headimgurl']}'>查看</a>";
			//AppUtil:: debugErrorSendWx('发红包接口返回', $debugArrayNew, true, 4);

			if (!empty($res)){
				echo json_encode($res);
			}else{
				echo json_encode( array('return_code' => 'FAIL', 'return_msg' => 'redpack_接口出错', 'return_ext' => array() ));						
			}

			exit;
		break;
	
	default:


	case "transfers";
			$SendTransfers = $sendWallet_app;  //红包 与 企业付款公用 类
			//$SendTransfers->set_mch_appid( WxPayConf_pub::APPID ); 				// appid  默认已在配置文件中配置
			//$SendTransfers->set_mchid( WxPayConf_pub::MCHID );    				// 商户号 默认已在配置文件中配置

			$SendTransfers->set_nonce_str( walletWeixinUtil::getNonceStr() );    	// 随机字符串
			$SendTransfers->set_partner_trade_no( $mch_billno ); 					// 商户订单号，需保持唯一性
			$SendTransfers->set_openid( $openid );   								// 用户在wxappid下的openid
			$SendTransfers->set_check_name('NO_CHECK');     					    // 是否校验真实姓名
			$SendTransfers->set_re_user_name('sixian');								// 真实姓名
			$SendTransfers->set_amount( $amount );									// 企业付款金额，单位为分
			$SendTransfers->set_desc( $wishing );								    // 企业付款操作说明信息。必填 
			$SendTransfers->set_spbill_create_ip( walletWeixinUtil::getRealIp() );  // 调用接口的机器Ip地址

			// 得到签名和其它设置的 xml 数据
			$getNewData  = $SendTransfers->getSendTransfersXml($SendTransfers);					
			$data = walletWeixinUtil::curl_post_ssl($getNewData['api_url'], $getNewData['xml_data']);
			$res  = @simplexml_load_string($data,NULL,LIBXML_NOCDATA);

            // TODO::红包通知
            $callback                  = json_encode($res);
            $callbackArr               = json_decode($callback, true);
            $debugArrayNew['openid']   = $openid;
            $debugArrayNew['状态']     = @$callbackArr['return_code'];
            $debugArrayNew['返回信息'] = @$callbackArr['return_msg'] . "\n";

            $debugArrayNew['活动名称'] = $act_name;
            $debugArrayNew['发放金额'] = $amount."【分】";
            $debugArrayNew['发放类型'] = '企业付款';
            $debugArrayNew['红包编号'] = $mch_billno . "\n";

            $wxuser = $this->dao_read->getWxuserByopenid($openid, '', 1);
            $wxuser = @$wxuser[0];

            @$debugArrayNew['用户'] =  $wxuser['nickname'];
            @$debugArrayNew['性别'] =  $wxuser['sex'] == 1 ? '男' : '女';
            @$debugArrayNew['头像'] = "<a href='{$wxuser['headimgurl']}'>查看</a>";
			AppUtil:: debugErrorSendWx('发红包接口返回', $debugArrayNew, true, 4);

			if (!empty($res)){
				echo json_encode($res);
			}else{
				echo json_encode( array('return_code' => 'FAIL', 'return_msg' => 'transfers_接口出错', 'return_ext' => array()) );						
			}
			exit;
		break;
		# code...
		break;
}

echo json_encode( array('return_code' => 'FAIL', 'return_msg' => '接口出错', 'return_ext' => array() ));



?>
