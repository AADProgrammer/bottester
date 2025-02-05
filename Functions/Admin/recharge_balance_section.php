<?php
    include "../../config.php";
    $SECTION = "recharge_balance_section";
    $part_menu = "شحن الرصيد";
    $section_data_folder = __DIR__ . "/../";
    $data_folder = __DIR__ . "/../../Data/";
    $orders_folder = __DIR__ . "/../../Data/Orders/";
    $accounts_folder = __DIR__ . "/../../Data/Accounts/";
    $admins_folder = __DIR__ . "/../../Data/Admins/";
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

    function convertToTelegramKeyboard($array_data){
        $results = [];
        $temp = [];
        
        foreach($array_data as $item){
            $res = ['text' => $item['name'], 'callback_data' => $item['name']];
            $temp[] = $res;
            
            if(count($temp) == 2){
                $results[] = $temp;
                $temp = [];
            }
        }
        
        if(count($temp) > 0){
            $res = ['text' => '+', 'callback_data' => '+'];
            $temp[] = $res;
            $results[] = $temp;
            $temp = [];
        }else{
            $temp[] = ['text' => '+', 'callback_data' => '+'];
        }

        // إذا كان هناك زر واحد متبقي
        if(count($temp) > 0){
            $res = ['text' => BACK_MAIN_TEXT, 'callback_data' => BACK_MAIN_TEXT];
            $temp[] = $res;
            $results[] = $temp;
        }else{
            $results[] = [['text' => BACK_MAIN_TEXT, 'callback_data' => BACK_MAIN_TEXT]];
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
        $shared_preferences_file = $admins_folder . $chat_id . "/shared_preferences";
        $shared_preferences = json_decode(file_get_contents($shared_preferences_file), true);

        #Menu
        $menu_file = $admins_folder . $chat_id . "/menu";
        $menu = json_decode(file_get_contents($menu_file), true);
        $menu_section = $menu['section'];
        $menu_num = $menu['num'];


    }

    function setMenu($menu_section, $menu_num){
        global $menu_file, $chat_id, $shared_preferences, $SECTION, $section_data_folder, $jsonData;

        $menu = [];
        $menu['section'] = $menu_section;
        $menu['num'] = $menu_num;
        saveFileWithEncodeArray($menu_file, $menu);

        if($menu_section == $SECTION){
            if($menu_num == 0){
                $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                if(!file_exists($recharge_balance_section_file)) file_put_contents($recharge_balance_section_file, "{}");
                $array_data = json_decode(file_get_contents($recharge_balance_section_file), true);
                $keboard = convertToTelegramKeyboard($array_data);
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "الرجاء الاختيار ...",
                    'reply_markup' => json_encode([
                        'resize_keyboard' => true,
                        'keyboard' => $keboard
                    ])
                ]);
                exit();
            }

            if($menu_num == 1){
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>'قم بارسال الاسم لطريقة الدفع المراد اضافتها',
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>BACK_TEXT, 'callback_data'=>BACK_TEXT]],
                        ]
                    ])
                ]);
            }

            if($menu_num == 2){
                $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                if(!file_exists($recharge_balance_section_file)) file_put_contents($recharge_balance_section_file, "{}");
                $array = json_decode(file_get_contents($recharge_balance_section_file), true);
                $found = false;
                foreach ($array as $item) {
                    if ($item['name'] == $shared_preferences['name']) {
                        $found = true;
                        $array = $item;
                        break;
                    }
                }
                if($found){
                    bot('sendMessage',[
                        'chat_id'=>$chat_id,
                        'text'=>"اسم الطريقة : " . $array['name'] . NEW_LINE . "ايدي الطريقة : " . $array['id'] . NEW_LINE . "النوع : " . ($array['type'] == "photo+text"?"صورة أو نص":($array['type']=="text"?"نص فقط":"صورة فقط")) . NEW_LINE . "حالة الاظهار : " . ($array['visible']=="true"?"فعال":"غير فعال"),
                        'reply_markup'=>json_encode([
                            'resize_keyboard'=>true,
                            'keyboard'=>[
                                [['text'=>"تعديل النوع", "callback_data"=>""], ['text'=>"تعديل الاسم", "callback_data"=>""]],
                                [['text'=>"تعديل النص", "callback_data"=>""], ['text'=>"عرض النص", "callback_data"=>""]],
                                [['text'=>"الغاء التفعيل", "callback_data"=>""], ['text'=>"تفعيل", "callback_data"=>""]],
                                [['text'=>"حذف الطريقة", "callback_data"=>"حذف الطريقة"], ['text'=>BACK_TEXT, "callback_data"=>BACK_TEXT]],
                            ]
                        ])
                    ]);
                }
                
            }

            if($menu_num == 3){
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>"قم بارسال النص المراد عرضه",
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>BACK_TEXT, "callback_data"=>BACK_TEXT]],
                        ]
                    ])
                ]);
            }

            if($menu_num == 4){
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>"قم باختيار نوع التاكيد للعملية",
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>'صورة فقط', "callback_data"=>'صورة فقط'], ['text'=>'نص فقط', "callback_data"=>'نص فقط']],
                            [['text'=>'صورة أو نص', "callback_data"=>'صورة + نص'], ['text'=>BACK_TEXT, "callback_data"=>BACK_TEXT]],
                        ]
                    ])
                ]);
            }

            if($menu_num == 5){
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>"قم بارسال الاسم الجديد لتعديله",
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>BACK_TEXT, "callback_data"=>BACK_TEXT]],
                        ]
                    ])
                ]);
            }

            if($menu_num == 6){
                global $data_text, $message_id;
                bot('editMessageText',[
                    'message_id'=>$message_id,
                    'text'=>$data_text,
                    'chat_id'=>$chat_id,
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'inline_keyboard'=>[
                            [['text'=>'الغاء ❌', 'callback_data'=>'الغاء ❌'], ['text'=>'تأكيد ✅', 'callback_data'=>'تأكيد ✅']],
                        ]
                    ])
                ]);
                return false;
            }

            if($menu_num == 7){
                bot('sendMessage',[
                    'text'=>"⚠️ يرجى الانتباه الى المبلغ وعملية التحويل والتحقق منهم ⚠️",
                    'chat_id'=>$chat_id,
                ]);
                bot('sendMessage',[
                    'text'=>"أرسل الرصيد (Credit) لاضافته للمستخدم",
                    'chat_id'=>$chat_id,
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>BACK_TEXT, 'callback_data'=>BACK_TEXT]],
                        ]
                    ])
                ]);
                return false;
            }

            return false;
        }

        if($menu_section == "main"){
            connection(FUNCTIONS_URL . "Admin/main.php", $jsonData);
            exit();
        }
        return false;
        
    }

    if($menu_section == $SECTION){
        if($message){
            if($text){
                if($text == $part_menu) setMenu($menu_section, 0);

                if($menu_num == 0){
                    if($text == '+'){
                        setMenu($menu_section, 1);
                        return false;
                    }

                    $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                    if(!file_exists($recharge_balance_section_file)) file_put_contents($recharge_balance_section_file, "{}");
                    $array = json_decode(file_get_contents($recharge_balance_section_file), true);
                    $found = false;
                    foreach ($array as $item) {
                        if ($item['name'] == $text) {
                            $found = true;
                            $array = $item;
                            break;
                        }
                    }
                    if($found){
                        $shared_preferences['name'] = $text;
                        saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                        setMenu($menu_section, 2);
                        return false;
                    }else{
                        bot('sendMessage', [
                            'chat_id'=>$chat_id,
                            'text'=>'عذرا المنتج غير موجود قم باستعمال الكيبورد فقط'
                        ]);
                        return false;
                    }
                }

                if($menu_num == 1){
                    if($text == BACK_TEXT){
                        setMenu($menu_section, 0);
                        return false;
                    }else{
                        $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                        
                        if(!file_exists($recharge_balance_section_file)) file_put_contents($recharge_balance_section_file, "{}");
                        $array = json_decode(file_get_contents($recharge_balance_section_file), true);
                        $array_data = [];
                        $array_data['name'] = $text;
                        $array_data['id'] = getRandom("all", 10);
                        $array_data['visible'] = "false";
                        $array_data['type'] = "text";
                        $array_data['text'] = "لا يوجد";
                        $array[] = $array_data;
                        saveFileWithEncodeArray($recharge_balance_section_file, $array);
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>"تمت الاضافة بنجاح"
                        ]);
                        setMenu($menu_section, 0);
                        return false;
                    }
                }

                if($menu_num == 2){
                    if($text == BACK_TEXT){
                        setMenu($menu_section, 0);
                    }

                    $name = $shared_preferences['name'];
                    $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                    $array = json_decode(file_get_contents($recharge_balance_section_file), true);
                    $position = -1;
                    foreach($array as $key => $item){
                        if($item['name'] == $name){
                            $position = $key;
                        }
                    }

                    if($text == "تفعيل"){
                        if($array[$position]['visible'] == "true"){
                            bot('sendMessage', [
                                'chat_id'=>$chat_id,
                                'text'=>"هذه الطريقة مفعلة بالفعل"
                            ]);
                        }else{
                            $array[$position]['visible'] = "true";
                            saveFileWithEncodeArray($recharge_balance_section_file, $array);
                            bot('sendMessage', [
                                'chat_id'=>$chat_id,
                                'text'=>"تم التفعيل بنجاح"
                            ]);
                            setMenu($menu_section, 2);
                        }
                        
                    }

                    if($text == "الغاء التفعيل"){
                        if($array[$position]['visible'] == "false"){
                            bot('sendMessage', [
                                'chat_id'=>$chat_id,
                                'text'=>"هذه الطريقة غير مفعلة بالفعل"
                            ]);
                        }else{
                            $array[$position]['visible'] = "false";
                            saveFileWithEncodeArray($recharge_balance_section_file, $array);
                            bot('sendMessage', [
                                'chat_id'=>$chat_id,
                                'text'=>"تم الغاء التفعيل بنجاح"
                            ]);
                            setMenu($menu_section, 2);
                        }
                    }

                    if($text == 'عرض النص'){
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>$array[$position]['text'],
                            'parse_mode'=>"markdown",
                        ]);
                    }

                    if($text == "تعديل النص"){
                        setMenu($menu_section, 3);
                    }
                    
                    if($text == "تعديل النوع"){
                        setMenu($menu_section, 4);
                    }

                    if($text == "تعديل الاسم"){
                        setMenu($menu_section, 5);
                    }

                    if($text == "حذف الطريقة"){
                        unset($array[$position]);
                        saveFileWithEncodeArray($recharge_balance_section_file, $array);
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>'تم حذف الطريقة بنجاح',
                        ]);
                        setMenu($menu_section , 0);
                        return false;
                    }

                    return false;
                }

                if($menu_num == 3){
                    if($text == BACK_TEXT){
                        setMenu($menu_section, 2);
                        return false;
                    }
                    $name = $shared_preferences['name'];
                    $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                    $array = json_decode(file_get_contents($recharge_balance_section_file), true);
                    $position = -1;
                    foreach($array as $key => $item){
                        if($item['name'] == $name){
                            $position = $key;
                        }
                    }

                    $array[$position]['text'] = $text;
                    saveFileWithEncodeArray($recharge_balance_section_file, $array);
                    bot('sendMessage', [
                        'chat_id'=>$chat_id,
                        'text'=>"تم تعديل النص بنجاح"
                    ]);
                    setMenu($menu_section, 2);
                }

                if($menu_num == 4){
                    if($text == BACK_TEXT){
                        setMenu($menu_section , 2);
                        return false;
                    }

                    $name = $shared_preferences['name'];
                    $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                    $array = json_decode(file_get_contents($recharge_balance_section_file), true);
                    $position = -1;
                    foreach($array as $key => $item){
                        if($item['name'] == $name){
                            $position = $key;
                        }
                    }

                    
                    if($text == "نص فقط"){
                        $array[$position]['type'] = 'text';
                        saveFileWithEncodeArray($recharge_balance_section_file, $array);
                        bot('sendMessage', [
                            'chat_id'=>$chat_id,
                            'text'=>"تم تعديل النوع بنجاح"
                        ]);
                    }

                    if($text == "صورة فقط"){
                        $array[$position]['type'] = 'photo';
                        saveFileWithEncodeArray($recharge_balance_section_file, $array);
                        bot('sendMessage', [
                            'chat_id'=>$chat_id,
                            'text'=>"تم تعديل النوع بنجاح"
                        ]);
                    }

                    if($text == "صورة أو نص"){
                        $array[$position]['type'] = "photo+text";
                        saveFileWithEncodeArray($recharge_balance_section_file, $array);
                        bot('sendMessage', [
                            'chat_id'=>$chat_id,
                            'text'=>"تم تعديل النوع بنجاح"
                        ]);
                    }
                    setMenu($menu_section, 2);
                    return false;
                }

                if($menu_num == 5){
                    if($text == BACK_TEXT){
                        setMenu($menu_section , 2);
                        return false;
                    }

                    $name = $shared_preferences['name'];
                    $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                    $array = json_decode(file_get_contents($recharge_balance_section_file), true);
                    $position = -1;
                    foreach($array as $key => $item){
                        if($item['name'] == $name){
                            $position = $key;
                        }
                    }
                    $array[$position]['name'] = $text;
                    saveFileWithEncodeArray($recharge_balance_section_file, $array);
                    $shared_preferences['name'] = $text;
                    saveFileWithEncodeArray($shared_preferences_file , $shared_preferences);
                    bot('sendMessage', [
                        'chat_id'=>$chat_id,
                        'text'=>"تم تعديل الاسم بنجاح"
                    ]);
                    setMenu($menu_section , 2);
                }

                if($menu_num == 6){
                    return false;
                }

                if($menu_num == 7){
                    $order_file = $shared_preferences['order_file'];
                    $order = json_decode(file_get_contents($order_file), true);

                    if($text == BACK_TEXT){
                        $order['state'] = 'pending';
                        $order['admin_id'] = "";
                        $order['admin_name'] = "";
                        saveFileWithEncodeArray($order_file, $order);
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>"تم الغاء العملية بنجاح ✅"
                        ]);
                        setMenu("main", 100);
                        return false;
                    }

                    if(is_numeric($text)){
                        $balance_file_user = $users_folder . $order['user_id'] . '/balance';
                        $balance_user = file_get_contents($balance_file_user);
                        $balance_user = round($balance_user + $text, 3);
                        file_put_contents($balance_file_user, $balance_user);

                        $order['state'] = 'approved';
                        $order['balance_added'] = $text;
                        $order['balance_after'] = $balance_user;
                        saveFileWithEncodeArray($order_file, $order);
                        bot('sendMessage',[
                            'chat_id'=>$order['user_id'],
                            'text'=>"تم إضافة " . round($text, 3) . DOLLAR_ICON . " الى رصيدك" . NEW_LINE . "رصيدك الجديد هو : " . $balance_user . DOLLAR_ICON . NEW_LINE . "شكرا لاختياركم خدماتنا ❤️❤️"
                        ]);
                        bot('editMessageText',[
                            'message_id'=>$shared_preferences['message_id'],
                            'chat_id'=>$chat_id,
                            'text'=>$shared_preferences['message_text'] . EQUALS . "تم تأكيد العملية بنجاح ✅" . NEW_LINE . "المبلغ : " . $text . DOLLAR_ICON,
                        ]);
                        setMenu("main", 100);
                        return false;
                    }else{
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>"يجب ارسال قيمة رقمية"
                        ]);
                        return false;
                    }
                    return false;
                }
                return false;
 
            }
        }

        if($data){
            if($data == "open_recharge_balance_user"){
                setMenu($menu_section, 6);
                return false;
            }

            if($menu_num == 6){
                $order = explode("\n", $data_text);
                $order_name = end($order);
                $order_name = explode(":", $order_name);
                $order_name = trim($order_name[1]);
                $order_file = $orders_folder . "recharge_balance_user" . '/' . $order_name;
                $order = json_decode(file_get_contents($order_file), true);

                if($data == "تأكيد ✅"){
                    if($order['state'] == "pending"){
                        $order['state'] = "working";
                        $order['admin_id'] = $chat_id;
                        $order['admin_name'] = $first_name;
                        $order['time_approved'] = TIME_SY;
                        saveFileWithEncodeArray($order_file, $order);

                        $shared_preferences['message_id'] = $message_id;
                        $shared_preferences['message_text'] = $data_text;
                        $shared_preferences['order_name'] = $order_name;
                        $shared_preferences['order_file'] = $order_file;
                        saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);

                        setMenu($menu_section, 7);
                        return false;
                    }else{
                        if($order['state'] == "rejected"){
                            bot('answerCallbackQuery',[
                                'callback_query_id'=>$callback_query_id,
                                'text'=>"هذه العملية تم رفضها من قبل " . $order['admin_name'],
                                'show_alert'=>true,
                            ]);
                            bot('editMessageText',[
                               'message_id'=>$message_id,
                               'text'=>$data_text . EQUALS . "هذه العملية تم رفضها من قبل " . $order['admin_name'],
                               'chat_id'=>$chat_id,
                            ]);
                            return false;
                        }

                        if($order['state'] == 'approved'){
                            bot('answerCallbackQuery',[
                                'callback_query_id'=>$callback_query_id,
                                'text'=>"هذه العملية تم تأكيدها من قبل " . $order['admin_name'],
                                'show_alert'=>true,
                            ]);
                            bot('editMessageText',[
                               'message_id'=>$message_id,
                               'text'=>$data_text . EQUALS . "هذه العملية تم تأكيدها من قبل " . $order['admin_name'] . NEW_LINE . "المبلغ : " . $order['balance_added'],
                               'chat_id'=>$chat_id,
                            ]);
                            return false;
                        }

                        if($order['state'] == 'working'){
                            bot('answerCallbackQuery',[
                                'callback_query_id'=>$callback_query_id,
                                'text'=>"يتم تنفيذ هذه العملية من قبل ادمن اخر (" . $order['admin_name'] . ")",
                                'show_alert'=>true,
                            ]);
                            setMenu("main", 100);
                            return false;
                        }
                    }
                }

                if($data == "الغاء ❌"){
                    if($order['state'] == "pending"){
                        $order['state'] = "rejected";
                        $order['admin_id'] = $chat_id;
                        $order['admin_name'] = $first_name;
                        $order['time_rejected'] = TIME_SY;
                        saveFileWithEncodeArray($order_file, $order);
                        bot('sendMessage', [
                            'chat_id'=>$order['user_id'],
                            'text'=>"رقم الطلب : " . $order['order_name'] . NEW_LINE . "تم رفض الطلب يرجى التحقق من المعومات بدقة"
                        ]);
                        bot('editMessageText',[
                            'message_id'=>$message_id,
                            'chat_id'=>$chat_id,
                            'text'=>$data_text . EQUALS . "تم رفض هذه العملية بنجاح ✅"
                        ]);
                        setMenu("main", 100);
                        return false;
                    }else{
                        if($order['state'] == "rejected"){
                            bot('answerCallbackQuery',[
                                'callback_query_id'=>$callback_query_id,
                                'text'=>"هذه العملية تم رفضها من قبل " . $order['admin_name'],
                                'show_alert'=>true,
                            ]);
                            bot('editMessageText',[
                               'message_id'=>$message_id,
                               'text'=>$data_text . EQUALS . "هذه العملية تم رفضها من قبل " . $order['admin_name'],
                               'chat_id'=>$chat_id,
                            ]);
                            return false;
                        }

                        if($order['state'] == 'approved'){
                            bot('answerCallbackQuery',[
                                'callback_query_id'=>$callback_query_id,
                                'text'=>"هذه العملية تم تأكيدها من قبل " . $order['admin_name'],
                                'show_alert'=>true,
                            ]);
                            bot('editMessageText',[
                               'message_id'=>$message_id,
                               'text'=>$data_text . EQUALS . "هذه العملية تم تأكيدها من قبل " . $order['admin_name'] . NEW_LINE . "المبلغ : " . $order['balance_added'],
                               'chat_id'=>$chat_id,
                            ]);
                            return false;
                        }

                        if($order['state'] == 'working'){
                            bot('answerCallbackQuery',[
                                'callback_query_id'=>$callback_query_id,
                                'text'=>"يتم تنفيذ هذه العملية من قبل ادمن اخر (" . $order['admin_name'] . ")",
                                'show_alert'=>true,
                            ]);
                            setMenu("main", 100);
                            return false;
                        }
                    }
                }
                return false;
            }
            return false;
        }

        if($inline_query){

        }
    }
    return false;
?>