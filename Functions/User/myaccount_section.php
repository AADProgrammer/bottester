<?php
    include "../../config.php";
    $SECTION = "myaccount_section";
    $part_menu = "حسابي 🧑‍💻";
    $data_folder = __DIR__ . "/../../Data/";
    $orders_folder = __DIR__ . "/../../Data/Orders/";
    $accounts_folder = __DIR__ . "/../../Data/Accounts/";
    $users_folder = __DIR__ . "/../../Data/Users/";
    $images_folder = __DIR__ . "/../../Data/Images/";

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

    if($message or $data){
        $shared_preferences_file = $users_folder . $chat_id . "/shared_preferences";
        $shared_preferences = json_decode(file_get_contents($shared_preferences_file), true);

        #Menu
        $menu_file = $users_folder . $chat_id . "/menu";
        $menu = json_decode(file_get_contents($menu_file), true);
        $menu_section = $menu['section'];
        $menu_num = $menu['num'];

        #Balance
        $balance_file = $users_folder . $chat_id . "/balance";
        $balance = file_get_contents($balance_file);


    }

    function saveFileWithEncodeArray($file, $menu){
        file_put_contents($file, json_encode($menu));
        return false;
    }

    function setMenu($menu_section, $menu_num){
        global $menu_file, $chat_id, $first_name, $user_name, $last_name, $balance, $jsonData, $SECTION;

        $menu = [];
        $menu['section'] = $menu_section;
        $menu['num'] = $menu_num;
        saveFileWithEncodeArray($menu_file, $menu);

        if($menu_section == $SECTION){
            if($menu_num == 0){
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "الاسم الأول : " . $first_name . NEW_LINE . "الاسم الأخير : " . $last_name . NEW_LINE . "المعرف : @ً" . $user_name . NEW_LINE . "الايدي : " . $chat_id . NEW_LINE . "الرصيد : " . $balance . DOLLAR_ICON,
                    'reply_markup' => json_encode([
                        'resize_keyboard' => true,
                        'keyboard' => [
                            [['text' => 'الإشعارات', 'callback_data' => 'استهلاكي'], ['text' => 'شحن الرصيد', 'callback_data' => 'ستاتيك']],
                            [['text' => 'الطلبات المعلقة', 'callback_data' => 'روتيت'], ['text' => 'طلباتي', 'callback_data' => 'طلباتي']],
                            [['text' => BACK_MAIN_TEXT, 'callback_data' => BACK_MAIN_TEXT]],
                        ]
                    ])
                ]);
            }
        }

        if($menu_section == "recharge_balance_section"){
            connection(FUNCTIONS_URL . "User/recharge_balance_section.php", $jsonData);
            exit();
        }

        if($menu_section == "notifications_section"){
            connection(FUNCTIONS_URL . "User/notifications_section.php", $jsonData);
            exit();
        }
        
    }

    if($menu_section == $SECTION){
        if($message){
            if($text){
                if($text == $part_menu or $text == BACK_TEXT) setMenu($menu_section, 0);

                if($menu_num == 0){
                    if($text == "الإشعارات"){
                        setMenu("notifications_section", 1);
                    }

                    if($text == "شحن الرصيد"){
                        setMenu("recharge_balance_section", 0);
                    }
                }
            }
        }

        if($data){

        }

        if($inline_query){

        }
    }
    return false;
?>