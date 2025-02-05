<?php
    include "../../config.php";
    $data_folder = __DIR__ . "/../../Data/";
    $orders_folder = __DIR__ . "/../../Data/Orders/";
    $accounts_folder = __DIR__ . "/../../Data/Accounts/";
    $admins_folder = __DIR__ . "/../../Data/Admins/";

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
    log_users("admin", '');
    
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

    function checkJoinedChannel($chat_id){
        $join = file_get_contents("https://api.telegram.org/bot" . BOT_APIKEY . "/getChatMember?chat_id=" . CHANNEL_ID . "&user_id=" . $chat_id);
        if((strpos($join,'"status":"left"') or strpos($join,'"Bad Request: USER_ID_INVALID"') or strpos($join,'"status":"kicked"'))!== false){
            return false;
        }
        return true;
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

    function saveFileWithEncodeArray($file, $menu){
        file_put_contents($file, json_encode($menu));
        return false;
    }

    function setMenu($menu_section, $menu_num){
        global $menu_file, $jsonData, $chat_id;

        $menu = [];
        $menu['section'] = $menu_section;
        $menu['num'] = $menu_num;
        saveFileWithEncodeArray($menu_file, $menu);

        if($menu_section == "main"){
            if($menu_num == 0){
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "💠 القائمة الرئيسية",
                    'reply_markup' => json_encode([
                        'resize_keyboard' => true,
                        'keyboard' => [
                            [['text' => 'Settings 🛠', 'callback_data' => 'Settings 🛠'], ['text' => 'الاقسام', 'callback_data' => '']],
                            [['text'=> "شحن الرصيد", "callback_data"=>"شحن الرصيد"]],
                            
                        ]
                    ])
                ]);
                exit();
            }
        }

        if($menu_section == "settings_section"){
            connection(FUNCTIONS_URL . "Admin/settings_section.php", $jsonData);
            exit();
        }

        if($menu_section == "sections_section"){
            connection(FUNCTIONS_URL . "Admin/sections_section.php", $jsonData);
            exit();
        }

        if($menu_section == "recharge_balance_section"){
            connection(FUNCTIONS_URL . "Admin/recharge_balance_section.php", $jsonData);
            exit();
        }
        return false;
        
    }

    if(!($data and $data == "check_joined_channel")) {
        if(!checkJoinedChannel($chat_id)) {
            bot('sendMessage', [
                'chat_id'=>$chat_id,
                'text'=>"عذرا يجب عليك الاشتراك بالقناة للمتابعة",
                'reply_markup'=>json_encode([
                    'resize_keyboard'=>true,
                    'inline_keyboard'=>[
                        [['text'=>'الاشتراك بالقناة', 'url'=>'https://t.me/' . CHANNEL_USERNAME]],
                        [['text'=>'التحقق من الاشتراك والمتابعة', 'callback_data'=>'check_joined_channel']]
                    ]
                ])
            ]);
            exit();
        }
    }

    if($message or $data){
        $shared_preferences_file = $admins_folder . $chat_id . "/shared_preferences";
        $shared_preferences = json_decode(file_get_contents($shared_preferences_file), true);

        #Menu
        $menu_file = $admins_folder . $chat_id . "/menu";
        $menu = json_decode(file_get_contents($menu_file), true);
        $menu_section = $menu['section'];
        $menu_num = $menu['num'];

        if($menu_num == 100){
            setMenu("main", 0);
            return false;
        }

    }

    if($message){
        if($text){
            if($text == BACK_MAIN_TEXT) {
                setMenu("main", "0");
                exit();
            }

            if(strpos($text, '/start') !== false){
                if($text == "/start"){
                    setMenu("main", "0");
                    exit();
                }
            }

            if($menu_section == "main"){
                if($text == "Settings 🛠"){
                    setMenu("settings_section", 0);
                    return false;
                }

                if($text == "الاقسام"){
                    setMenu("sections_section", 0);
                    return false;
                }

                if($text == "شحن الرصيد"){
                    setMenu("recharge_balance_section", 0);
                    return false;
                }

            }else{
                connection(FUNCTIONS_URL . "Admin/" . $menu_section . ".php", $jsonData);
                return false;
            }
        }
    }

    if($data){
        if($data == "open_recharge_balance_user"){
            setMenu("recharge_balance_section", 0);
            return false;  
        }

        if($menu_section == "main"){
            if($data == "القائمة الرئيسية") {
                bot('deleteMessage',[
                    'chat_id'=>$chat_id,
                    'message_id'=>$message_id
                ]);
                setMenu("main", 0);
            }
    
            if($data == "check_joined_channel"){
                if(checkJoinedChannel($chat_id)){
                    bot('editMessageText',[
                        'message_id'=>$message_id,
                        'chat_id'=>$chat_id,
                        'text'=>'تم التحقق من اشتراكك بالقناة بنجاح ✅'
                    ]);
                    setMenu("main", 0);
                }else{
                    bot('answerCallbackQuery',[
                        'callback_query_id'=> $callback_query_id,
                        'text'=>'عذرا لم يتم الاشتراك بعد ...',
                        'show_alert'=>true,
                    ]);
                    exit();
                }
            }
            return false;
        }

        connection(FUNCTIONS_URL . "Admin/" . $menu_section . ".php", $jsonData);
        exit();
        
    }

    return false;
?>