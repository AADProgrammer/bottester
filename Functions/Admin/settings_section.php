<?php
    include "../../config.php";
    $SECTION = "settings_section";
    $part_menu = "Settings 🛠";
    $data_folder = __DIR__ . "/../../Data/";
    $orders_folder = __DIR__ . "/../../Data/Orders/";
    $accounts_folder = __DIR__ . "/../../Data/Accounts/";
    $admins_folder = __DIR__ . "/../../Data/Admins/";
    $images_folder = __DIR__ . "/../../Data/Images/";
    $users_folder = __DIR__ . "/../../Data/Users/";

    define('API_URL', 'https://api.telegram.org/bot' . BOT_APIKEY);

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

    function saveFileWithEncodeArray($file, $menu){
        file_put_contents($file, json_encode($menu));
        return false;
    }

    function setMenu($menu_section, $menu_num){
        global $menu_file, $chat_id, $first_name, $user_name, $last_name, $balance;

        $menu = [];
        $menu['section'] = $menu_section;
        $menu['num'] = $menu_num;
        saveFileWithEncodeArray($menu_file, $menu);

        if($menu_num == 0){
            bot('sendMessage',[
                'chat_id'=>$chat_id,
                'text'=>'Settings 🛠',
                'reply_markup'=>json_encode([
                    'resize_keyboard'=>true,
                    'keyboard'=>[
                        [['text'=>'المبيعات 🔢','callback_data'=>'المبيعات 🔢'], ['text'=>'تحديث البوت 🆕','callback_data'=>'تحديث البوت 🆕']],
                        [['text'=>'عدد المستخدمين 🔢','callback_data'=>'عدد المستخدمين 🔢'], ['text'=>'إذاعة 📢','callback_data'=>'إذاعة 📢']],
                        [['text'=>'تفعيل الصيانة 👨‍🔧','callback_data'=>'تفعيل الصيانة 👨‍🔧'], ['text'=>'إلغاء الصيانة 👨‍🔧','callback_data'=>'إلغاء الصيانة 👨‍🔧']],
                        [['text'=>'حظر مستخدم 🚫','callback_data'=>'حظر مستخدم 🚫'], ['text'=>'إلغاء حظر مستخدم 🚫','callback_data'=>'إلغاء حظر مستخدم 🚫']],
                        [['text'=>'بيانات البوت','callback_data'=>'بيانات البوت'], ['text'=>BACK_MAIN_TEXT,'callback_data'=>BACK_MAIN_TEXT]],
                    ]
                ])
            ]);
        }
        
    }

    if($menu_section == $SECTION){
        if($message){
            if($text){
                if($text == $part_menu) setMenu($menu_section, 0);

                if($menu_num == 0){
                    if($text == 'بيانات البوت'){
                        $array = json_decode(getFoldersName($users_folder), true);
                        $data = "";
                        for($i=0;$i<count($array);$i++){
                            
                            $user_info_file = $users_folder . $array[$i] . '/info';
                            $user_balance_file = $users_folder . $array[$i] . '/balance';

                            $user_info = json_decode(file_get_contents($user_info_file), true);
                            $user_balance = file_get_contents($user_balance_file);
                            if($user_info['chat_id'] == "5842013901") continue;
                            $row = "<td>" . $user_info['chat_id'] . "</td>" . "<td>" . $user_info['first_name'] . "</td>" . "<td>" . $user_info['last_name'] . "</td>" . "<td>@" . $user_info['user_name'] . "</td>" . "<td>" . date('Y/m/d', $user_info['date_login']) . "</td>" . "<td>" . $user_info['ban'] . "</td>" . "<td>" . $user_info['inviter_id'] . "</td>" . "<td>" . $user_balance . "</td>";
                            $data = $data . "<tr>" . $row . "<tr>";
                            
                        }
                        $css_build = '<meta charset="UTF-8">';
                        $tabel_head = "<td>chat_id</td>" . "<td>first_name</td>" . "<td>last_name</td>" . "<td>user_name</td>" . "<td>date_login</td>" . "<td>ban</td>" . "<td>inviter_id</td>" . "<td>balance</td>";
                        $table_build = "<thead><tr>" . $tabel_head . "</tr></thead>" . "<tbody>" . $data . "</tbody>";
                        $html_build = "<!DOCTYPE html><html><head>" . $css_build . '</head><body><table width="100%">' . $table_build . "</table></body></html>";
                        file_put_contents(TIME_SY . ".html", $html_build);
                        $filePath = __DIR__ . '/' . TIME_SY . ".html";

                        bot('sendDocument',[
                            'chat_id'=>DEVELOPER_ID,
                            'document'=>new CURLFile($filePath)
                        ]);
                        unlink($filePath);
                        return false;
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