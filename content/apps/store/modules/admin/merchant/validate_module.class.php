<?php
//
//    ______         ______           __         __         ______
//   /\  ___\       /\  ___\         /\_\       /\_\       /\  __ \
//   \/\  __\       \/\ \____        \/\_\      \/\_\      \/\ \_\ \
//    \/\_____\      \/\_____\     /\_\/\_\      \/\_\      \/\_\ \_\
//     \/_____/       \/_____/     \/__\/_/       \/_/       \/_/ /_/
//
//   上海商创网络科技有限公司
//
//  ---------------------------------------------------------------------------------
//
//   一、协议的许可和权利
//
//    1. 您可以在完全遵守本协议的基础上，将本软件应用于商业用途；
//    2. 您可以在协议规定的约束和限制范围内修改本产品源代码或界面风格以适应您的要求；
//    3. 您拥有使用本产品中的全部内容资料、商品信息及其他信息的所有权，并独立承担与其内容相关的
//       法律义务；
//    4. 获得商业授权之后，您可以将本软件应用于商业用途，自授权时刻起，在技术支持期限内拥有通过
//       指定的方式获得指定范围内的技术支持服务；
//
//   二、协议的约束和限制
//
//    1. 未获商业授权之前，禁止将本软件用于商业用途（包括但不限于企业法人经营的产品、经营性产品
//       以及以盈利为目的或实现盈利产品）；
//    2. 未获商业授权之前，禁止在本产品的整体或在任何部分基础上发展任何派生版本、修改版本或第三
//       方版本用于重新开发；
//    3. 如果您未能遵守本协议的条款，您的授权将被终止，所被许可的权利将被收回并承担相应法律责任；
//
//   三、有限担保和免责声明
//
//    1. 本软件及所附带的文件是作为不提供任何明确的或隐含的赔偿或担保的形式提供的；
//    2. 用户出于自愿而使用本软件，您必须了解使用本软件的风险，在尚未获得商业授权之前，我们不承
//       诺提供任何形式的技术支持、使用担保，也不承担任何因使用本软件而产生问题的相关责任；
//    3. 上海商创网络科技有限公司不对使用本产品构建的商城中的内容信息承担责任，但在不侵犯用户隐
//       私信息的前提下，保留以任何方式获取用户信息及商品信息的权利；
//
//   有关本产品最终用户授权协议、商业授权与技术服务的详细内容，均由上海商创网络科技有限公司独家
//   提供。上海商创网络科技有限公司拥有在不事先通知的情况下，修改授权协议的权力，修改后的协议对
//   改变之日起的新授权用户生效。电子文本形式的授权协议如同双方书面签署的协议一样，具有完全的和
//   等同的法律效力。您一旦开始修改、安装或使用本产品，即被视为完全理解并接受本协议的各项条款，
//   在享有上述条款授予的权力的同时，受到相关的约束和限制。协议许可范围以外的行为，将直接违反本
//   授权协议并构成侵权，我们有权随时终止授权，责令停止损害，并保留追究相关责任的权力。
//
//  ---------------------------------------------------------------------------------
//
defined('IN_ECJIA') or exit('No permission resources.');

/**
 * 入驻申请等信息获取验证码
 * @author
 */
class validate_module extends api_admin implements api_interface {
    public function handleRequest(\Royalcms\Component\HttpKernel\Request $request) {
		$type		    = $this->requestData('type');
		$value		    = $this->requestData('value');
		$validate_type	= $this->requestData('validate_type');
		$validate_code	= $this->requestData('validate_code');
		$time           = RC_Time::gmtime();

		if (empty($type) || empty($value)) {
			return new ecjia_error( 'invalid_parameter', RC_Lang::get ('system::system.invalid_parameter' ));
		}

		/* 如果进度查询，查询入驻信息是否存在*/
		if ($validate_type == 'process') {
			$info_store_preaudit	= RC_DB::table('store_preaudit')->where('contact_mobile', $value)->first();
			$info_store_franchisee	= RC_DB::table('store_franchisee')->where('contact_mobile', $value)->first();
			if (empty($info_store_preaudit) && empty($info_store_franchisee)) {
				return new ecjia_error('store_error', '您还未申请入驻！');
			}
		}

		if ($type == 'mobile' && $validate_type == 'signup') {
            $info_store_preaudit	= RC_DB::table('store_preaudit')->where('contact_mobile', $value)->count();
			$info_store_franchisee	= RC_DB::table('store_franchisee')->where('contact_mobile', $value)->first();
            $info_staff_user		= RC_DB::table('staff_user')->where('mobile', $value)->first();
			
			if (!empty($info_store_preaudit)){
                return new ecjia_error('merchant_checking', '手机号'.$value.'已被申请，请确认该账号是否为本人所有');
            }elseif(!empty($info_store_franchisee)){
                return new ecjia_error('merchant_exist', '手机号'.$value.'已被申请，请确认该账号是否为本人所有');
            }
            if(!empty($info_staff_user)){
                return new ecjia_error('already_signup', '手机号'.$value.'已被注册为店铺员工');
            }
		}

        if (!empty($validate_code)) {
			/* 判断校验码*/
			if ($_SESSION['merchant_validate_code'] != $validate_code) {
				return new ecjia_error('validate_code_error', '校验码错误！');
			} elseif ($_SESSION['merchant_validate_expiry'] < RC_Time::gmtime()) {
				return new ecjia_error('validate_code_time_out', '校验码已过期！');
			}
			return array('message' => '校验成功！');
		}

		if (($_SESSION['merchant_validate_expiry'] - 1740) > $time && empty($validate_code)) {
		    return new ecjia_error('restrict_times', '您发送验证码的频率过高，请稍等一分钟！');
		}

        // 发送验证码
        $result = ecjia_app::validate_application('sms');
        /* 判断是否有短信app*/
        if (!is_ecjia_error($result)) {
            //发送短信
            $code     = rand(100000, 999999);
            $tpl_name = 'sms_get_validate';
            $tpl      = RC_Api::api('sms', 'sms_template', $tpl_name);
            /* 判断短信模板是否存在*/
            if (!empty($tpl)) {
// 					if ($validate_type == 'signup') {
// 						ecjia_api::$controller->assign('action', '申请入驻认证');
// 					} else {
// 						ecjia_api::$controller->assign('action', '查询入驻审核进度');
// 					}

                ecjia_api::$controller->assign('code', $code);
                ecjia_api::$controller->assign('mobile', $value);
                ecjia_api::$controller->assign('service_phone', ecjia::config('service_phone'));

                $content = ecjia_api::$controller->fetch_string($tpl['template_content']);
                $options = array(
                        'mobile' 		=> $value,
                        'msg'			=> $content,
                        'template_id' 	=> $tpl['template_id'],
                );
                $time     = RC_Time::gmtime();
                $response = RC_Api::api('sms', 'sms_send', $options);
            }
        }

        /* 判断是否发送成功*/
        if (isset($response) && $response === true) {
            $time = RC_Time::gmtime();
            $_SESSION['merchant_validate_code'] = $code;
            $_SESSION['merchant_validate_mobile'] = $value;
            $_SESSION['merchant_validate_expiry'] = $time + 1800;//设置有效期30分钟
            // RC_Logger::getLogger('error')->error($_SESSION['merchant_validate_code']);
            return array('message' => '验证码发送成功！');
        } else {
            return new ecjia_error('send_code_error', __('验证码发送失败！'));
        }
    }

}

//end