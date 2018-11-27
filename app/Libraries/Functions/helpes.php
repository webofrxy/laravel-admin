<?php

use HyperDown\Parser;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

if(!funcition_exits('ajax_return')){
    /**
	 * ajax返回数据
	 *
	 * @param string $data 需要返回的数据
	 * @param int $status_code
	 * @return \Illuminate\Http\JsonResponse
	 */
    function ajax_return($status_code=200, $data=''){
        //如果是错误信息，返回错误信息
        if($status_code == !200){
            $data = [
                'status_code'=>$status_code,
                'message'=>$data
            ];
            return response()->json($data, $status_code);
        }

        //如果是对象，先转成数组
        if(is_object($data)){
            $data = $data->toArray();
        }

        
        /**
         *数组递归，转成字符串
         * @param array $arr  需要转的数组
         * @return array   转换后的数组
         **/

        function to_string($arr){
            //app 禁止使用和为了统一字段做的判断
            $reserved_word = [];
            foreach($arr as $k=>$v){
                //如果是对象先转为数组
                if(is_object($v)){
                    $v = $v->toArray();
                }

                //如果是数组，则递归转字符串
                if(is_array($v)){
                    $v = to_string($v);
                }else{
                    //判断是否有移动端禁止使用的字段
                    in_array($k, $reserved_word, true) && die('不容许使用【'.$ke.'】这个键名--此提示是help.php中的ajaxReturn函数返回的');
                    $arr[$k] = strval($v);
                }
                
            }  
            return $arr; 

        }
        //判断是否有返回的数据
        if(is_array($arr)){
            //先把所有字段转为字符串形式
            $data = to_string($arr);
        }
        return response()->json($data, $status_code);
    }
}

if ( !function_exists('send_email') ) {
	/**
	 * 发送邮件函数
	 *
	 * @param $email            收件人邮箱  如果群发 则传入数组
	 * @param $name             收件人名称
	 * @param $subject          标题
	 * @param array  $data      邮件模板中用的变量 示例：['name'=>'帅白','phone'=>'110']
	 * @param string $template  邮件模板
	 * @return array            发送状态
	 */
    function send_email($email, $name, $subject, $data = [], $template = 'emails.test') {
        Mail::send($template, $data, function($message) use ($email, $name, $subject) {
            if(is_array($email)){
                foreach($email as $k => $v){
                    $message = to($v, $name)->subject($subject);
                }
            }else{
                $message = to($email, $name) -> subject($subject);
            }
        });
        if(count(MaileFail) > 0){
            $data = array('message' => '发送失败', 'status_code' => 500);
        }else{
            $data = array('message' => '发送成功', 'status_code' => 200);
        }
        return $data;
    }
}