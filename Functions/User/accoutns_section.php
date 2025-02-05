<?php
    include "../../config.php";
    $SECTION = "accounts_section";
    $section_data_folder = __DIR__ . "/../";
    $part_menu = "قسم الحسابات";
    $data_folder = __DIR__ . "/../../Data/";
    $orders_folder = __DIR__ . "/../../Data/Orders/";
    $accounts_folder = __DIR__ . "/../../Data/Accounts/";
    $users_folder = __DIR__ . "/../../Data/Users/";
    $images_folder = __DIR__ . "/../../Data/Images/";
    $admins_folder = __DIR__ . "/../../Data/Admins/";

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

    function convertToTelegramKeyboard($array_data){
        $results = [];
        $temp = [];
        
        foreach($array_data as $item){
            if($item['visible'] == "true"){
                $res = ['text' => $item['name'], 'callback_data' => $item['name']];
                $temp[] = $res;
                
                if(count($temp) == 2){
                    $results[] = $temp;
                    $temp = [];
                }
            }
        }
    
        // إذا كان هناك زر واحد متبقي
        if(count($temp) > 0){
            $res = ['text' => BACK_TEXT, 'callback_data' => BACK_TEXT];
            $temp[] = $res;
            $results[] = $temp;
        }else{
            $results[] = [['text' => BACK_TEXT, 'callback_data' => BACK_TEXT]];
        }
        return $results;
    }

    function saveFileWithEncodeArray($file, $menu){
        file_put_contents($file, json_encode($menu));
        return false;
    }

    function getFilesName($dirPath){
		$results = [];
		$files = scandir($dirPath);
		foreach ($files as $file) {
			$filePath = $dirPath . '/' . $file;
			if (is_file($filePath)) {
				$results[] = $file;
			}
		}
		return json_encode($results);
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

    function buyNewAccount($chat_id){
        global $balance, $shared_preferences , $accounts_folder, $orders_folder, $menu_section, $balance_file;
        $folder = $accounts_folder . $shared_preferences['id'] . "/";
        $array = json_decode(getFilesName($folder), true);
        $count = count($array);
        if($shared_preferences['price'] == ""){
            bot('sendMessage', [
                'chat_id'=>$chat_id,
                'text'=>"عذرا السعر غير موجود لا يمكن الشراء حاليا"
            ]);
            setMenu($menu_section, 0);
            return false;
        }
        if($balance >= $shared_preferences['price']){
            if($count > 0){
                
                $new_balance = round($balance - $shared_preferences['price'], 3);
                $file_name = $array[0];
                $file_path = $folder . $file_name;
                $file_data = json_decode(file_get_contents($file_path), true);
                unlink($file_path);

                $shared_preferences['count'] = ($count - 1);
                $shared_preferences['order_name'] = $file_name;

                $array_data = [];
                $array_data['user_id'] = $chat_id;
                $array_data['balance_before'] = $balance;
                $array_data['balance_after'] = $new_balance;
                $array_data['time_purchase'] = TIME_SY;
                $array_data['profits'] = round($shared_preferences['price'] - $file_data['purchase_price'], 3);
                $array_data['selling_price'] = $shared_preferences['price'];
                $array_data['file_data'] = $file_data;
                file_put_contents($orders_folder . '/buy_new_account/' . $file_name, json_encode($array_data));
                
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>'اسم القسم : ' . $shared_preferences['section_name'] . NEW_LINE . 'اسم المنتج : ' . $shared_preferences['name'] . NEW_LINE . "السعر : " . $shared_preferences['price'] . NEW_LINE . "رقم الطلب : " . $file_name . NEW_LINE . 'رصيدك : ' . $balance . DOLLAR_ICON . NEW_LINE .  "رصيدك الجديد : " . $new_balance . DOLLAR_ICON,
                    'reply_markup'=>json_encode([
                        'remove_keyboard'=>true,
                    ])
                ]);

                $balance = $new_balance;
                file_put_contents($balance_file, $balance);

                if($file_data['info_url'] == ""){
                    bot('sendMessage',[
                        'chat_id'=>$chat_id,
                        'text'=>$file_data['account'],
                    ]);
                }else{
                    bot('sendMessage',[
                        'chat_id'=>$chat_id,
                        'text'=>$file_data['account'],
                        'reply_markup'=>json_encode([
                            'resize_keyboard'=>true,
                            'inline_keyboard'=>[
                                [['text'=>'التعليمات' , 'url'=>$file_data['info_url']]],
                            ]
                        ])
                    ]);
                }
                sendForAdmins("buy_new_account", $array_data);
                setMenu($menu_section, 0);
                return false;
            }else{
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>'عذرا لقد نفذت الكمية يرجى المحاولة في وقت لاحق'
                ]);
                sendForAdmins("account_not_found", $balance);
                setMenu($menu_section, 0);
                return false;
            }
        }else{
            $minus = round($shared_preferences['price'] - $balance,3);
            bot('sendMessage',[
                'chat_id'=>$chat_id,
                'text'=>"عذرا ليس لديك رصيد كافي" . NEW_LINE . "رصيدك الحالي هو : " . $balance . DOLLAR_ICON . NEW_LINE . "ينقصك : " . $minus . DOLLAR_ICON,
            ]);
            setMenu($menu_section, 0);
            return false;
        }
        return false;
    }

    function sendForAdmins($type, $array_data){
        global $chat_id, $admins_folder, $shared_preferences;
        
        $isText = true;
        if($type == "buy_new_account"){
            $text = "🎉 عملية شراء جديدة 🎉" . EQUALS . "اسم القسم : " . $shared_preferences['section_name'] . NEW_LINE . "المنتج : " . $shared_preferences['name'] . NEW_LINE . "عدد الحسابات المتبقي : " . $shared_preferences['count'] . NEW_LINE . "الايدي : " . $chat_id . NEW_LINE . "الاسم الأول : " . getUserInformation($chat_id, "first_name") . NEW_LINE . "الاسم الأخير : " . getUserInformation($chat_id, "last_name") . NEW_LINE . "المعرف : @" . getUserInformation($chat_id, "user_name") . NEW_LINE . "الرصيد قبل الشراء : " . $array_data['balance_before'] . DOLLAR_ICON . NEW_LINE . "الرصيد بعد الشراء : " . $array_data['balance_after'] . DOLLAR_ICON . NEW_LINE . "رقم الطلب : " . $shared_preferences['order_name']; 
        }else if($type == "account_not_found"){
            $text = "💢 الطلب غير متوفر 💢" . EQUALS . "اسم القسم : " . $shared_preferences['section_name'] . NEW_LINE . "المنتج : " . $shared_preferences['name'] . NEW_LINE . "الايدي : " . $chat_id . NEW_LINE . "الاسم الأول : " . getUserInformation($chat_id, "first_name") . NEW_LINE . "الاسم الأخير : " . getUserInformation($chat_id, "last_name") . NEW_LINE . "المعرف : @" . getUserInformation($chat_id, "user_name") . NEW_LINE . "الرصيد : " . $array_data . DOLLAR_ICON; 
        }
        
        $Admins = json_decode(getFoldersName($admins_folder), true);
        
        for ($i = 0; $i < count($Admins); $i++) {
            $Admin = $Admins[$i];
            $admin_type_file = $admins_folder . $Admin . '/type';
            $admin_type = json_decode(file_get_contents($admin_type_file), true);
            
            if(!($admin_type["all"] == "true" or $admin_type['accounts_section'] == "true")) continue;
        
            if($isText && $text !== ""){
                bot('sendMessage',[
                    'chat_id'=>$Admin,
                    'text'=>$text,
                ]);
            }
        }
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


    function setMenu($menu_section, $menu_num){
        global $menu_file, $chat_id, $first_name, $user_name, $last_name, $balance, $section_data_folder, $shared_preferences;

        $menu = [];
        $menu['section'] = $menu_section;
        $menu['num'] = $menu_num;
        saveFileWithEncodeArray($menu_file, $menu);

        if($menu_num == 0){
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "الرجاء الاختيار ...",
                'reply_markup' => json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text' => 'بايبالات 🧑‍💻', 'callback_data' => 'حسابات 🧑‍💻'], ['text' => 'استبيانات 🧑‍💻', 'callback_data' => 'استبيانات 🧑‍💻']],
                        [['text' => 'إيميلات 📧', 'callback_data' => 'إيميلات 📧'], ['text' => 'معلومات', 'callback_data' => 'معلومات']],
                        [['text' => BACK_MAIN_TEXT, 'callback_data' => BACK_MAIN_TEXT]]
                    ]
                ])
            ]);
            return false;
        }

        if($menu_num == 1){
            $surveys_section_file = $section_data_folder . $shared_preferences['section_file'] . ".json";
            if(!file_exists($surveys_section_file)) file_put_contents($surveys_section_file, "{}");
            $array_data = json_decode(file_get_contents($surveys_section_file), true);
            $keboard = convertToTelegramKeyboard($array_data);
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "الرجاء الاختيار ...",
                'reply_markup' => json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => $keboard
                ])
            ]);
            return false;
        }

        if($menu_num == 2){
            $surveys_section_file = $section_data_folder . $shared_preferences['section_file'] . ".json";
            if(!file_exists($surveys_section_file)) file_put_contents($surveys_section_file, "{}");
            $array_data = json_decode(file_get_contents($surveys_section_file), true);
            $section_name = $shared_preferences['section_name'];
            bot('sendMessage', [
                'chat_id' => $chat_id,
                'text' => "اسم القسم : " . $section_name . NEW_LINE . "اسم المنتج : " . $shared_preferences['name'] . NEW_LINE . "السعر : " . $shared_preferences['price'] . DOLLAR_ICON . EQUALS . $shared_preferences['details'],
                'reply_markup' => json_encode([
                    'resize_keyboard' => true,
                    'keyboard' => [
                        [['text'=>BACK_TEXT, 'callback_data'=>BACK_TEXT], ['text'=>"تأكيد الشراء ✅", 'callback_data'=>"تأكيد ✅"]],
                    ]
                ])
            ]);
            return false;
        }

        return false;
    }


    if($menu_section == $SECTION){
        if($message){
            if($text){
                if($text == $part_menu) setMenu($menu_section, 0);

                if($menu_num == 0){
                    $shared_preferences['section_name'] = $text;
                    if($text == "استبيانات 🧑‍💻"){
                        $shared_preferences['section_file'] = "surveys";
                        saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                        setMenu($menu_section, 1);
                        return false;
                    }

                    if($text == "بايبالات 🧑‍💻"){
                        $shared_preferences['section_file'] = "paypals";
                        saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                        setMenu($menu_section, 1);
                        return false;
                    }

                    if($text == "إيميلات 📧"){
                        return false;
                        $shared_preferences['section_file'] = "surveys";
                        setMenu($menu_section, 1);
                        return false;
                    }

                    if($text == "معلومات"){
                        $shared_preferences['section_file'] = "informations";
                        saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                        setMenu($menu_section, 1);
                        return false;
                    }
                    
                    return false;
                }

                if($menu_num == 1){
                    if($text == BACK_TEXT){
                        setMenu($menu_section, 0);
                        return false;
                    }

                    $surveys_section_file = $section_data_folder . $shared_preferences['section_file'] . ".json";
                    if(!file_exists($surveys_section_file)) file_put_contents($surveys_section_file, "{}");
                    $array_data = json_decode(file_get_contents($surveys_section_file), true);
                    $found = false;
                    foreach($array_data as $key => $item){
                        if($item['name'] == $text && $item['visible'] == "true"){
                            $found = true;
                            $shared_preferences['name'] = $item['name'];
                            $shared_preferences['id'] = $item['id'];
                            $shared_preferences['position'] = $key;
                            $shared_preferences['details'] = $item['details'];
                            $shared_preferences['price'] = $item['price'];
                            saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                            break;
                        }
                    }
                    if($found){
                        setMenu($menu_section, 2);
                    }
                    return false;
                }

                if($menu_num == 2){
                    if($text == BACK_TEXT){
                        setMenu($menu_section, 1);
                        return false;
                    }

                    if($text == "تأكيد الشراء ✅"){
                        buyNewAccount($chat_id);
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