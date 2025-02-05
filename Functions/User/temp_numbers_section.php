<?php
    include "../../config.php";
    $SECTION = "temp_numbers_section";
    $part_menu = "رقم مؤقت";
    $data_folder = __DIR__ . "/../../Data/";
    $orders_folder = __DIR__ . "/../../Data/Orders/";
    $accounts_folder = __DIR__ . "/../../Data/Accounts/";
    $users_folder = __DIR__ . "/../../Data/Users/";
    $images_folder = __DIR__ . "/../../Data/Images/";
    $section_data_folder = __DIR__ . "/../";

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

    if($message or $data or $inline_query){
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

    function getPhotoPathUrl($path){
        $url_image = SERVER_URL . BOT_FOLDER . "Data/Images/" . $path;
        return $url_image;
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
        global $menu_file, $chat_id, $SECTION, $jsonData, $section_data_folder;

        $menu = [];
        $menu['section'] = $menu_section;
        $menu['num'] = $menu_num;
        saveFileWithEncodeArray($menu_file, $menu);

        if($SECTION == $menu_section){
            if($menu_num == 0){
                /*
                $file_last_used_of_countries_temp = $section_data_folder . 'last_used_of_countries_temp.json';
                if (file_exists($file_last_used_of_countries_temp)) {
                    $list = json_decode(file_get_contents($file_last_used_of_countries_temp), true);
                    $list = array_keys($list);
                }else{
                    $list = [];
                }

                $keyboard = convertToTelegramKeyboard($list, "");
                */
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "قم باختيار الدولة",
                    'reply_markup' => json_encode([
                        'resize_keyboard' => true,
                        'keyboard' => [
                            [['text'=>BACK_TEXT, 'callback_data'=>BACK_TEXT]],
                        ],
                    ])
                ]);
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "يمكنك العثور على المزيد من البلدان عن طريق هذا الزر 👇🏻",
                    'reply_markup' => json_encode([
                        'resize_keyboard' => true,
                        'inline_keyboard' => [
                            [['text' => 'البحث عن بلد', 'switch_inline_query_current_chat' => 'country:']],
                        ]
                    ])
                ]);
                return false;
            }
            return false;
        }
        
    }

    if($menu_section == $SECTION){
        if($message){
            if($text){
                if($text == $part_menu) setMenu($menu_section, "0");

                if($menu_num == 0){
                    if($text == BACK_TEXT){
                        setMenu("main", 100);
                        return false;
                    }
                    return false;
                }
            }
        }

        if($data){

        }

        if($inline_query){
            if ($menu_num == 0) {
                return false;
                $file_search_country_list = $section_data_folder . 'search_country_list.json';
                if(! file_exists($file_search_country_list)) return false;
                bot('sendMessage',[
                    'chat_id'=>DEVELOPER_ID,
                    'text'=>"inline work",
                ]);
                return false;
                $counties = json_decode(file_get_contents($file_search_country_list), true);

                $query = $inline_query->query;
                $searchTerm = str_replace("country:", "", $query);

                $offset = isset($update->inline_query->offset) ? $update->inline_query->offset : 0;
                $pageSize = 50;
                $offset = (int)$offset;
                $results = [];

                if ($searchTerm == "") {
                    for ($i = $offset; $i < min($offset + $pageSize, count($counties)); $i++) {
                        $input_message_content['message_text'] = $counties[$i]['name'];
                        $result = [];
                        $result['type'] = 'article';
                        $result['input_message_content'] = $input_message_content;
                        $result['title'] = $counties[$i]['name'];
                        $result['thumbnail_url'] = $counties[$i]['img_url'];
                        $result['id'] = base64_encode(rand(5, 98888855));
                        $results[] = $result;
                    }

                    $nextOffset = $offset + $pageSize;

                    bot('answerInlineQuery', [
                        'inline_query_id' => $update->inline_query->id,
                        'results' => json_encode($results),
                        'next_offset' => $nextOffset,
                        'cache_time' => 1
                    ]);
                } else {
                    for ($i = 0; $i < count($counties); $i++) {
                        if (stripos($counties[$i]["name"], $searchTerm) !== false) {
                            $input_message_content['message_text'] = $counties[$i]['name'];
                            $result = [];
                            $result['type'] = 'article';
                            $result['input_message_content'] = $input_message_content;
                            $result['title'] = $counties[$i]['name'];
                            $result['thumbnail_url'] = $counties[$i]['img_url'];
                            $result['id'] = base64_encode(rand(5, 98888855));
                            $results[] = $result;
                        }
                    }

                    bot('answerInlineQuery', [
                        'inline_query_id' => $update->inline_query->id,
                        'results' => json_encode($results),
                        'cache_time' => 1
                    ]);
                }

                return false;
            }
        }
    }
    return false;
?>