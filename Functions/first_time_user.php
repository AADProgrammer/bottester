<?php
    include "../config.php";
    $data_folder = __DIR__ . "/../Data/";
    $data_folder = __DIR__ . "/../Data/";
    $orders_folder = __DIR__ . "/../Data/Orders/";
    $accounts_folder = __DIR__ . "/../Data/Accounts/";
    $admins_folder = __DIR__ . "/../Data/Admins/";
    $users_folder = __DIR__ . "/../Data/Users/";
    $images_folder = __DIR__ . "/../Data/Images/";

    function bot($method,$datas=[]){
        $url = "https://api.telegram.org/bot" . BOT_APIKEY . "/".$method;

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url); curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
        $res = curl_exec($ch);
        if(curl_error($ch)){
            var_dump(curl_error($ch));
        }else{
            $datas['reply_markup'] = "";
            $datas['method'] = $method;
            log_users("bot", json_encode($datas));
            return json_decode($res);
        }
    }

    function isUser($chat_id){
        global $data_folder;
        return is_dir($data_folder . 'Users/' . $chat_id);
    }
    
    function getUserInformation($chat_id, $type){
        global $data_folder;
		$data = json_decode(file_get_contents($data_folder . 'Users/' . $chat_id . '/info'), true);
		$result = '';
		if($type == 'user_name'){
			$result = $data['user_name'];
		}
		
		if($type == 'first_name'){
			$result = $data['first_name'];
		}
		
		if($type == 'last_name'){
			$result = $data['last_name'];
		}
		
        if($type == 'inviter_id'){
			$result = $data['inviter_id'];
		}
		return $result;
	}

    function getRandom($type, $length){
        $all = '';
        if ($type == 'all') {
            $all = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890';
        } else if ($type == 'numbers') {
            $all = '1234567890';
        } else if ($type == 'letters') {
            $all = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm';
        }

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $rand = rand(0, strlen($all) - 1);
            $io = substr($all, $rand, 1);
            $result = $result . "" . $io;
        }
        return $result;
    }

    function createDataForNewUser($chat_id, $inviter_id){
        global $user_name, $first_name, $last_name, $data_folder;
        $time = TIME_SY;

        $user_folder = $data_folder . 'Users/' . $chat_id;
        if(is_dir($user_folder)) exit();
        mkdir($user_folder);
        mkdir($user_folder . '/Orders');
        mkdir($user_folder . '/Orders/Deleted');
        mkdir($user_folder . "/images");

        #info
        $array_data = [];
        $array_data['ban'] = 'false';
        $array_data['language'] = "ar";
        $array_data['chat_id'] = $chat_id;
        $array_data['date_login'] = $time;
        $array_data['version'] = BOT_VERSION;
        $array_data['inviter_id'] = $inviter_id;
        $array_data['user_name'] = is_null($user_name) ? "لا يوجد" : $user_name;
        $array_data['first_name'] = is_null($first_name) ? "لا يوجد" : $first_name;
        $array_data['last_name'] = is_null($last_name) ? "لا يوجد" : $last_name;
        file_put_contents($user_folder . '/info', json_encode($array_data));

        #balance
        file_put_contents($user_folder . '/balance', "0.00");
    
        #shared_prefernces
        file_put_contents($user_folder . '/shared_preferences', "{}");

        #notifications
        file_put_contents($user_folder . '/notifications', "{}");
        
        #referrals
        file_put_contents($user_folder . '/referrals', "[]");

        #wallet
        file_put_contents($user_folder . '/wallet', "{}");
        createOrder($array_data);
        return false;
    }

    function saveFileWithEncodeArray($file, $menu){
        file_put_contents($file, json_encode($menu));
        return false;
    }

    function createOrder($array_data){
        global $orders_folder;
        $order_name = $array_data['date_login'] . "_" . getRandom("all", 8);
        $order_file = $orders_folder . "new_user" . '/' . $order_name;
        $array = [];
        $array['operation_type'] = "new_user";
        $array['chat_id'] = $array_data["chat_id"];
        $array['date_login'] = $array_data["date_login"];
        $array['version'] = $array_data["version"];
        $array['inviter_id'] = $array_data["inviter_id"];
        $array['user_name'] = $array_data['user_name'];
        $array['first_name'] = $array_data['first_name'];
        $array['last_name'] = $array_data['last_name'];
        saveFileWithEncodeArray($order_file, $array);
        return false;
    }

    function getFoldersName($dirPath){
        $results = [];
        $files = scandir($dirPath);
        foreach ($files as $file) {
            $filePath = $dirPath . '/' . $file;
            if (is_dir($filePath)) {
                if ($file == '.' or $file == '..') {
                } else {
                    $results[] = $file;
                }
            }
        }
        return json_encode($results);
    }

    function sendForAdmins($type){
        global $chat_id, $users_folder, $admins_folder;

        $reply_markup = "";
        $text = "";
        $isPhoto = false;
        $isText = false;

        if($type == "new_user"){
            $isText = true;
            $count = count(json_decode(getFoldersName($users_folder), true));
            $text = "تم دخول شخص جديد للبوت 😁" . EQUALS . "رقم : " . $count . NEW_LINE . "الايدي : " . $chat_id . NEW_LINE . "الاسم الأول : " . getUserInformation($chat_id, "first_name") . NEW_LINE . "الاسم الأخير : " . getUserInformation($chat_id, "last_name") . NEW_LINE . "المعرف : @" . getUserInformation($chat_id, "user_name") . NEW_LINE . "الدعوة : " . getUserInformation($chat_id, "inviter_id"); 
        }

        $Admins = json_decode(getFoldersName($admins_folder), true);
        
        for ($i = 0; $i < count($Admins); $i++) {
            $Admin = $Admins[$i];
            $admin_type_file = $admins_folder . $Admin . '/type';
            $admin_type = json_decode(file_get_contents($admin_type_file), true);
            if(!($admin_type["all"] == "true" or $admin_type['new_user'] == "true")) continue;
            if($isPhoto){

            }else if($isText && $text !== ""){
                if ($reply_markup == "") {
                    bot('sendMessage', [
                        'chat_id' => $Admin,
                        'text' => $text,
                    ]);
                } else {
                    bot('sendMessage', [
                        'chat_id' => $Admin,
                        'text' => $text,
                        'reply_markup' => $reply_markup
                    ]);
                }
            }
        }
    }

    function connection($url, $jsonData){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_exec($ch);
        curl_close($ch);
        return false;
    }

    function log_users($sender_type, $msg){
        global $data_folder, $chat_id, $message_id, $php_input, $data;
        $log_user_file = $data_folder . 'Log_users/' . $chat_id . '.log';
        if(!file_exists($log_user_file)) file_put_contents($log_user_file, "{}");
        $array_data = json_decode(file_get_contents($log_user_file), true);

        $array = [];
        $array['sender_type'] = $sender_type;
        $array['date'] = TIME_SY;
        $update = json_decode($php_input, true);
        if($data) $update['callback_query']['message']['reply_markup'] = "";

        if($sender_type == "bot"){
            $array['text'] = $msg;
        }else{
            $array['update'] = $update;
            $array['message_id'] = $message_id;
        }
        $array_data[] = $array;
        file_put_contents($log_user_file, json_encode($array_data));
        return false;
    }

    $php_input = file_get_contents('php://input');

    $update = json_decode($php_input);
    $jsonData = json_encode($update);
    $message = $update->message;
    $data = $update->callback_query->data;
    $inline_query = $update->inline_query;
    if ($message) {
        $text = $message->text;
        $photo = $message->photo;
        $contact = $message->contact;
        $message_id = $update->message->message_id;
        $chat_id = $message->chat->id;
        $from_id = $message->from->id;
        $first_name = $message->from->first_name;
        $last_name = $message->from->last_name;
        $user_name = $message->from->username;
    }else if ($data) {
        $callback_query_id = $update->callback_query->id;
        $data_text = $update->callback_query->message->text;
        $message_id = $update->callback_query->message->message_id;
        $chat_id = $update->callback_query->message->chat->id;
        $first_name = $update->callback_query->from->first_name;
        $last_name = $update->callback_query->from->last_name;
        $user_name = $update->callback_query->from->username;
    }else if ($inline_query) {
        $chat_id = $update->inline_query->from->id;
        $message_id = $update->inline_query->message->message_id;
    }
    log_users("user", '');

    if($message){
        if($text){
            if(strpos($text, '/start') !== false){
                if(preg_match("/invite_(\d+)/", $text, $matches)){
                    $inviter_id = $matches[1];
                    if($inviter_id == $chat_id){
                        $inviter_id = 'self';
                    }else{
                        if(isUser($inviter_id)){
                            $array_data = json_decode(file_get_contents($data_folder . 'Users/' . $inviter_id . '/referrals'),true);
                            if(!in_array($chat_id, $array_data)){
                                array_push($array_data, $chat_id);
                                file_put_contents($data_folder . 'Users/' . $inviter_id . '/referrals', json_encode($array_data));
                            }
                            bot('sendMessage',[
                                'chat_id'=>$chat_id,
                                'text'=>"تمت دعوتك الى البوت عن طريق" . "\n" . "الاسم الأول : " . getUserInformation($inviter_id, 'first_name') . "\n" . "الاسم الأخير : " . getUserInformation($inviter_id, 'last_name') . "\n" . "المعرف : @" . getUserInformation($inviter_id, 'user_name')
                            ]);
                        }else{
                            $inviter_id = 'error_id';
                        }
                    }
                }else{
                    $inviter_id = (($text == '/start')?'none':str_replace("/start ", "", $text));
                }

                $bot = bot('sendMessage', [
                    'chat_id'=>$chat_id,
                    'text'=>"أهلا وسهلا بكم في " . BOT_NAME . "\n" . "يتم إنشاء حساب خاص بك الرجاء الانتظار",
                ]);

                createDataForNewUser($chat_id, $inviter_id);
                sleep(6);

                bot('editMessageText', [
                    'message_id'=>$bot->result->message_id,
                    'chat_id'=>$chat_id,
                    'text'=>"تم إنشاء حساب خاص بك بنجاح ✅",
                ]);

                sendForAdmins("new_user");
                connection(FUNCTIONS_URL . "User/main.php", $jsonData);
            }
        }
    }
    
    return false;
?>