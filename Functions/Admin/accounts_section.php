<?php
    include "../../config.php";
    $SECTION = "accounts_section";
    $part_menu = "قسم الحسابات";
    $section_data_folder = __DIR__ . "/../";
    $data_folder = __DIR__ . "/../../Data/";
    $orders_folder = __DIR__ . "/../../Data/Orders/";
    $accounts_folder = __DIR__ . "/../../Data/Accounts/";
    $admins_folder = __DIR__ . "/../../Data/Admins/";
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

    function saveFileWithEncodeArray($file, $menu){
        file_put_contents($file, json_encode($menu));
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
        $shared_preferences_file = $admins_folder . $chat_id . "/shared_preferences";
        $shared_preferences = json_decode(file_get_contents($shared_preferences_file), true);

        #Menu
        $menu_file = $admins_folder . $chat_id . "/menu";
        $menu = json_decode(file_get_contents($menu_file), true);
        $menu_section = $menu['section'];
        $menu_num = $menu['num'];


    }

    function setMenu($menu_section, $menu_num){
        global $menu_file, $chat_id,$jsonData, $SECTION;

        $menu = [];
        $menu['section'] = $menu_section;
        $menu['num'] = $menu_num;
        saveFileWithEncodeArray($menu_file, $menu);

        if($menu_section == $SECTION){
            if($menu_num == 1){
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>' hgقم باختيار القسم',
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>'قسم البايبالات','callback_data'=>'قسم البايبالات'], ['text'=>'قسم الاستبيانات','callback_data'=>'قسم الاستبيانات']],
                            [['text'=>'قسم الايميلات','callback_data'=>'قسم الايميلات'], ['text'=>'قسم المعلومات','callback_data'=>'قسم المعلومات']],
                            [['text'=>BACK_TEXT,'callback_data'=>BACK_TEXT]],
                        ]
                    ])
                ]);
                exit();
            }
        }

        if($menu_section == "sections_section"){
            connection(FUNCTIONS_URL . "Admin/sections_section.php", $jsonData);
            exit();
        }

        if($menu_section == "surveys_section"){
            connection(FUNCTIONS_URL . "Admin/surveys_section.php", $jsonData);
            exit();
        }
        
        if($menu_section == "paypals_section"){
            connection(FUNCTIONS_URL . "Admin/paypals_section.php", $jsonData);
            exit();
        }

        if($menu_section == "informations_section"){
            connection(FUNCTIONS_URL . "Admin/informations_section.php", $jsonData);
            exit();
        }
        
    }

    if($menu_section == $SECTION){
        if($message){
            if($text){
                if($menu_num == 0 and ($text == BACK_TEXT or $text == $part_menu)) setMenu($menu_section, 1);
                
                if($menu_num == 1){
                    if($text == BACK_TEXT){
                        setMenu("sections_section", 0);
                        return false;
                    }

                    if($text == 'قسم الاستبيانات'){
                        setMenu("surveys_section", 0);
                        return false;
                    }

                    if($text == 'قسم البايبالات'){
                        setMenu("paypals_section", 0);
                        return false;
                    }

                    if($text == 'قسم الايميلات'){
                        setMenu($menu_section, 50);
                        return false;
                    }

                    if($text == 'قسم المعلومات'){
                        setMenu("informations_section", 0);
                        return false;
                    }
                    return false;
                }
                return false;
            }
        }

        if($data){
            
        }

        if($inline_query){

        }
    }
    return false;
?>