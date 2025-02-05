<?php
    include "config.php";
    $data_folder = __DIR__ . "/Data/";

    function bot($method,$datas=[]){
        $url = "https://api.telegram.org/bot" . BOT_APIKEY . "/".$method;

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url); curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
        $res = curl_exec($ch);
        if(curl_error($ch)){
            var_dump(curl_error($ch));
        }else{
            return json_decode($res);
        }
    }
    
    function checkManifest(){
        if(!file_exists("Data/wallet.json")){
            $wallet = [];
            $wallet['payeer'] = "0.0";
            $wallet['syriatel_cash'] = "0.0";
            file_put_contents("Data/wallet.json", json_encode($wallet));
        }

        if(file_exists("Manifest")) return false;
        $Manifest = [];

        $Manifest['maintanence'] = false;
        $Manifest['maintanence_text'] = "البوت في وضع الصيانة";

        file_put_contents("Manifest", json_encode($Manifest));
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

    function log_data($type){
        global $php_input, $isAdmin, $isUser;
        $data = json_decode($php_input, true);
        if($type == "data") $data['callback_query']['message']['reply_markup'] = "";
        $log_folder = __DIR__ . '/Data/Logs/';
        $file_name = date("y_m_d", TIME_SY) . ".log";
        $file_path = $log_folder . $file_name;
        
        if(file_exists($file_path)) $log_array = json_decode(file_get_contents($file_path), true);
        else $log_array = [];
        $array = [];
        $array['type'] = $type;
        $array['array'] = $data;
        if($isAdmin){
            $array['user_type'] = "admin";
        }else if($isUser){
            $array['user_type'] = "user";
        }else{
            $array['user_type'] = "first_time";
        }
        $log_array[] = $array;
        file_put_contents($file_path, json_encode($log_array));

        return false;
    }

    function deleteFolder($folderPath) {
        if (is_dir($folderPath)) {
            $files = array_diff(scandir($folderPath), array('.', '..'));
            foreach ($files as $file) {
                $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
                is_dir($filePath) ? deleteFolder($filePath) : unlink($filePath);
            }
            rmdir($folderPath);
        }
        return false;
    }

    function checkFolders(){
        if(!is_dir("Data")) mkdir("Data");
        if(!is_dir("Data/Accounts")) mkdir("Data/Accounts");
        if(!is_dir("Data/Admins")) mkdir("Data/Admins");
        if(!is_dir("Data/Log_users")) mkdir("Data/Log_users");
        if(!is_dir("Data/Logs")) mkdir("Data/Logs");
        if(!is_dir("Data/Orders")) mkdir("Data/Orders");
        if(!is_dir("Data/Orders/new_user")) mkdir("Data/Orders/new_user");
        if(!is_dir("Data/Orders/recharge_balance_user")) mkdir("Data/Orders/recharge_balance_user");
        if(!is_dir("Data/Orders/buy_new_account")) mkdir("Data/Orders/buy_new_account");
        if(!is_dir("Data/Users")) mkdir("Data/Users");
        if(!is_dir("Data/Images")) mkdir("Data/Images");
        if(!is_dir("Functions")) mkdir("Functions");
        return false;
    }
    
    $php_input = file_get_contents('php://input');
    
    #Bot Update
    $update = json_decode($php_input);
    $jsonData = json_encode($update);
    $message = $update->message;
    $data = $update->callback_query->data;
    $inline_query = $update->inline_query;
    if($message){
        $text = $message->text;
        $photo = $message->photo;
        $contact = $message->contact;
        $message_id = $update->message->message_id;
        $chat_id = $message->chat->id;
        $from_id = $message->from->id;
        $first_name = $message->from->first_name;
        $last_name = $message->from->last_name;
        $user_name = $message->from->username;
        if($text and $text == "/version"){
            bot('sendMessage',[
                'chat_id'=>$chat_id,
                'text'=>"Version : V" . BOT_VERSION,
            ]);
        }
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
    }
    
    if($text == "/test"){
        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'text'=>"حساب اللايف مثلا",
            'reply_markup'=>json_encode([
                'resize_keyboard'=>true,
                'inline_keyboard'=>[
                    [['text'=>"التعليمات", "url"=>'https://telegra.ph/تتعليمات-حساب-لايف-01-29']]
                ]
            ])
        ]);
    }

    #Start
    checkFolders();
    checkManifest();

    #Variables
    $isAdmin = (is_dir($data_folder . 'Admins/' . $chat_id) ? true : false);
    $isUser = (is_dir($data_folder . 'Users/' . $chat_id) ? true : false);
    $isFirstTime = (!($isAdmin) and !($isUser));

    if(DEVELOPER_ID == $chat_id or OWNER_ID == $chat_id){
        if(strpos($text, '/setAdmin') !== false){
            log_data("developerORowner");
            $admin_id = explode(" ", $text);
            $admin_id = $admin_id[1];
            if($admin_id == "me") $admin_id = $chat_id;
            if(!is_dir($data_folder . "Admins/" . $admin_id)){
                if(is_dir($data_folder . "Users/" . $admin_id)){
                    mkdir($data_folder . "Admins/" . $admin_id);
                    mkdir($data_folder . "Admins/" . $admin_id . "/temp");
                    file_put_contents($data_folder . "Admins/" . $admin_id . "/info", file_get_contents("Data/Users/" . $admin_id . "/info"));
                    file_put_contents($data_folder . "Admins/" . $admin_id . "/menu", '{"section":"main", "num":0}');
                    file_put_contents($data_folder . "Admins/" . $admin_id . "/shared_preferences", "{}");
                    file_put_contents($data_folder . "Admins/" . $admin_id . "/type", '{"all":"false"}');
                    deleteFolder($data_folder . "Users/" . $admin_id);
                    bot('sendMessage', [
                        'chat_id'=>$chat_id,
                        "text"=>"تمت إضافة الادمن بنجاح",
                    ]);
                }else{
                    bot('sendMessage', [
                        'chat_id'=>$chat_id,
                        "text"=>"عذرا المستخدم غير موجود بالبوت لا يمكن رفعه الى ادمن",
                    ]);
                }
                exit();
            }else{
                bot('sendMessage', [
                    'chat_id'=>$chat_id,
                    "text"=>"الادمن موجود بالفعل",
                ]);
            }
            exit();
        }
    }

    if($isAdmin){
        if($message){ 
            if($text) log_data("message_text");
            if($photo) log_data("message_photo");
            if($contact) log_data("message_contact");
        }
        if($data) log_data("data");
        if($inline_query) log_data("inline_query");

        $url = FUNCTIONS_URL . 'Admin/main.php';
        connection($url, $jsonData);
    }

    if($isUser){
        if($message){ 
            if($text) log_data("message_text");
            if($photo) log_data("message_photo");
            if($contact) log_data("message_contact");
        }
        if($data) log_data("data");
        if($inline_query) log_data("inline_query");

        $url = FUNCTIONS_URL . 'User/main.php';
        connection($url, $jsonData);
    }

    if($isFirstTime){
        if($message){ 
            if($text) log_data("message_text");
            if($photo) log_data("message_photo");
            if($contact) log_data("message_contact");
        }
        if($data) log_data("data");
        if($inline_query) log_data("inline_query");

        $url = FUNCTIONS_URL . 'first_time_user.php';
        connection($url, $jsonData);
    }

    return false;
?>
