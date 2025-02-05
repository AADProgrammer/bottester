<?php
    include "../../config.php";
    $SECTION = "paypals_section";
    $part_menu = "قسم البايبالات";
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

    function convertToKeyboardNumbers($array_data){
        $results = [];
        $temp = [];
        
        for($i=0; $i<count($array_data); $i++){
            $res = ['text' => $i + 1, 'callback_data' => "show_product_" . $array_data[$i]];
            $temp[] = $res;

            if(count($temp) == 5){
                $results[] = $temp;
                $temp = [];
            }
        }
        if(count($temp) > 0){
            $results[] = $temp;
        }
        // إذا كان هناك زر واحد متبقي
        $results[] = [['text' => BACK_TEXT, 'callback_data' => BACK_TEXT]];
        return $results;
    }

    function showPayPalsAccounts($folder){
        global $accounts_folder, $chat_id, $menu_section;
        $array_data = json_decode(getFilesName($accounts_folder . $folder), true);
        if(count($array_data) == 0){
            bot('sendMessage',[
                'chat_id'=>$chat_id,
                'text'=>"لا يوجد حسابات لعرضها ...",
            ]);
            setMenu($menu_section, 2);
            return false;
        }
        $keyboard = convertToKeyboardNumbers($array_data);
        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'text'=>"الحسابات المتوفرة حاليا",
            'reply_markup'=>json_encode([
                'resize_keyboard'=>true,
                'remove_keyboard'=>true
            ])
        ]);
        bot('sendMessage',[
            'chat_id'=>$chat_id,
            'text'=>"معلومات الحسابات",
            'reply_markup'=>json_encode([
                'resize_keyboard'=>true,
                'inline_keyboard'=>$keyboard,
            ])
        ]);
    }

    function addNewAccount($text){
        global $shared_preferences, $temp_folder, $chat_id, $first_name, $SECTION;

        $array = [];
        $array['account'] = $text;
        $array['section_id'] = $shared_preferences['id'];
        $array['section'] = $SECTION;
        $array['section_name'] = $shared_preferences['name'];
        $array['time_added'] = TIME_SY;
        $array['admin_id'] = $chat_id;
        $array['admin_name'] = $first_name;
        $array["purchase_price"] = $shared_preferences['purchase_price'];
        $array['order_name'] = TIME_SY . "_" . getRandom("all", 8);
        $file_path = $temp_folder . $array['order_name'];
        saveFileWithEncodeArray($file_path, $array);
        return false;
    }

    function sendForAdmins($type, $count){
        global $chat_id, $admins_folder, $shared_preferences, $first_name;
        $isText = true;
        $text = "تم إضافة حسابات جديدة بنجاح ✅" . EQUALS . "عدد الحسابات المضافة : " . $count . NEW_LINE . "القسم : " . "بايبالات" . NEW_LINE . "المنتج : ". $shared_preferences['name'] . NEW_LINE . "راس المال : " . $shared_preferences['purchase_price'] . NEW_LINE . "اسم الادمن : " . $first_name . NEW_LINE . "ايدي الادمن : " . $chat_id;
        $Admins = json_decode(getFoldersName($admins_folder), true);
        for ($i = 0; $i < count($Admins); $i++) {
            $Admin = $Admins[$i];
            $admin_type_file = $admins_folder . $Admin . '/type';
            $admin_type = json_decode(file_get_contents($admin_type_file), true);
            
            if(!($admin_type["all"] == "true" or $admin_type['surveys_section'] == "true")) continue;
            if($Admin == $chat_id) continue;
            
            if($isText && $text !== ""){
                bot('sendMessage',[
                    'chat_id'=>$Admin,
                    'text'=>$text,
                ]);
            }
        }
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

        #Temp
        $temp_folder = $admins_folder . $chat_id . "/temp/";

    }

    function setMenu($menu_section, $menu_num){
        global $temp_folder, $menu_file, $chat_id, $shared_preferences, $shared_preferences_file, $accounts_folder,$jsonData, $first_name, $user_name, $last_name, $balance, $SECTION, $section_data_folder;

        $menu = [];
        $menu['section'] = $menu_section;
        $menu['num'] = $menu_num;
        saveFileWithEncodeArray($menu_file, $menu);

        if($menu_section == $SECTION){

            if($menu_num == 0){
                $paypals_section_file = $section_data_folder . "paypals.json";
                if(!file_exists($paypals_section_file)) file_put_contents($paypals_section_file, "{}");
                $array_data = json_decode(file_get_contents($paypals_section_file), true);
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
                    'text'=>'قم بارسال الاسم للمنتج المراد اضافته',
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>BACK_TEXT, 'callback_data'=>BACK_TEXT]],
                        ]
                    ])
                ]);
            }
    
            if($menu_num == 2){
                $name = $shared_preferences['name'];
                $paypals_section_file = $section_data_folder . "paypals.json";
                if(!file_exists($paypals_section_file)) file_put_contents($paypals_section_file, "{}");
                $array = json_decode(file_get_contents($paypals_section_file), true);
                $array = $array[$shared_preferences['position']];
                $shared_preferences['count'] = count(json_decode(getFilesName($accounts_folder . $array['id']), true));
                saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>"اسم المنتج : " . $array['name'] . NEW_LINE . "ايدي المنتج : " . $array['id'] . NEW_LINE . "عدد الحسابات الموجودة : " . $shared_preferences['count'] . NEW_LINE . "سعر المبيع : " . ($array['price']==""?"0.0":$array['price']) . DOLLAR_ICON . NEW_LINE . "حالة الاظهار : " . ($array['visible']=="true"?"فعال":"غير فعال"),
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>"تعديل السعر", "callback_data"=>""], ['text'=>"تعديل الاسم", "callback_data"=>""]],
                            [['text'=>"اضافة حسابات", "callback_data"=>""], ['text'=>"عرض الحسابات", "callback_data"=>""]],
                            [['text'=>"الغاء تفعيل", "callback_data"=>""], ['text'=>"تفعيل", "callback_data"=>""]],
                            [['text'=>"حذف المنتج", "callback_data"=>""], ['text'=>BACK_TEXT, "callback_data"=>BACK_TEXT]],
                        ]
                    ])
                ]);
                return false;
            }
    
            if($menu_num == 3){
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>"قم بارسال السعر الجديد",
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
                    'text'=>"قم بارسال الاسم الجديد",
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>BACK_TEXT, "callback_data"=>BACK_TEXT]],
                        ]
                    ])
                ]);
            }

            if($menu_num == 5){
                showPayPalsAccounts($shared_preferences['id']);
                return false;
            }

            if($menu_num == 8){
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>"قم بإرسال رأس المال للحسابات",
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>BACK_TEXT, "callback_data"=>BACK_TEXT]],
                        ]
                    ])
                ]);
                return false;
            }

            if($menu_num == 9){
                $count = count(json_decode(getFilesName($temp_folder), true));
                if($count > 0){
                    deleteFolder($temp_folder);
                    mkdir($temp_folder);
                }
                bot('sendMessage',[
                    'chat_id'=>$chat_id,
                    'text'=>"قم بإرسال الحسابات كل حساب برسالة" . NEW_LINE . "ثم اضغط على تم ✅" . EQUALS . "⚠️ الرسالة نفسها سيتم ارسالها للزبون ⚠️",
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'keyboard'=>[
                            [['text'=>"تم ✅", "callback_data"=>"تم ✅"]],
                        ]
                    ])
                ]);
                return false;
            }
    
        }

        if($menu_section == "accounts_section"){
            connection(FUNCTIONS_URL . "Admin/accounts_section.php", $jsonData);
            exit();
        }
        
    }

    if($menu_section == $SECTION){
        if($message){
            if($text){
                if($text == $part_menu) setMenu($menu_section, 0);

                if($menu_num == 0){
                    if($text == BACK_TEXT){
                        setMenu("accounts_section", 0);
                        return false;
                    }

                    if($text == "+"){
                        setMenu($menu_section, 1);
                        return false;
                    }
                    $paypals_section_file = $section_data_folder . "paypals.json";
                    if(!file_exists($paypals_section_file)) file_put_contents($paypals_section_file, "{}");
                    $array = json_decode(file_get_contents($paypals_section_file), true);
                    $found = false;
                    foreach ($array as $key => $item) {
                        if ($item['name'] == $text) {
                            $shared_preferences['name'] = $text;
                            $shared_preferences['position'] = $key;
                            saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                            $found = true;
                            $array = $item;
                            break;
                        }
                    }
                    if($found){
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
                        $paypals_section_file = $section_data_folder . "paypals.json";
                        
                        if(!file_exists($paypals_section_file)) file_put_contents($paypals_section_file, "{}");
                        $array = json_decode(file_get_contents($paypals_section_file), true);
                        $array_data = [];
                        $array_data['name'] = $text;
                        $array_data['id'] = getRandom("all", 10);
                        while(is_dir($accounts_folder . $array_data['id'])){
                            $array_data['id'] = getRandom("all", 10);
                        }
                        $array[] = $array_data;
                        mkdir($accounts_folder . $array_data['id']);
                        saveFileWithEncodeArray($paypals_section_file, $array);
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
                    $id = "";
                    $paypals_section_file = $section_data_folder . "paypals.json";
                    $array = json_decode(file_get_contents($paypals_section_file), true);
                    $position = -1;
                    foreach($array as $key => $item){
                        if($item['name'] == $name){
                            $position = $key;
                            $shared_preferences['id'] = $item['id'];
                            saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
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
                            saveFileWithEncodeArray($paypals_section_file, $array);
                            bot('sendMessage', [
                                'chat_id'=>$chat_id,
                                'text'=>"تم التفعيل بنجاح"
                            ]);
                            setMenu($menu_section, 2);
                        }
                    }

                    if($text == "الغاء تفعيل"){
                        if($array[$position]['visible'] == "false"){
                            bot('sendMessage', [
                                'chat_id'=>$chat_id,
                                'text'=>"هذه الطريقة غير مفعلة بالفعل"
                            ]);
                        }else{
                            $array[$position]['visible'] = "false";
                            saveFileWithEncodeArray($paypals_section_file, $array);
                            bot('sendMessage', [
                                'chat_id'=>$chat_id,
                                'text'=>"تم الغاء التفعيل بنجاح"
                            ]);
                            setMenu($menu_section, 2);
                        }
                    }
                    
                    if($text == "تعديل السعر"){
                        setMenu($menu_section, 3);
                    }

                    if($text == "تعديل الاسم"){
                        setMenu($menu_section, 4);
                    }

                    if($text == "عرض الحسابات"){
                        setMenu($menu_section, 5);
                        return false;
                    }

                    if($text == "اضافة حسابات"){
                        setMenu($menu_section, 8);
                        return false;
                    }

                    if($text == "حذف المنتج"){
                        if($shared_preferences['count'] > 0){
                            bot('sendMessage',[
                                'chat_id'=>$chat_id,
                                'text'=>"عذرا القسم يحوي على منتجات بالفعل لا يمكن حذفه"
                            ]);
                            return false;
                        }

                        $id = $array[$position]['id'];
                        deleteFolder($accounts_folder . $id);
                        unset($array[$position]);
                        saveFileWithEncodeArray($paypals_section_file, $array);
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>"id : $id
                            تم حذف المنتج بنجاح"
                        ]);
                        setMenu($menu_section, 0);
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
                    $paypals_section_file = $section_data_folder . "paypals.json";
                    $array = json_decode(file_get_contents($paypals_section_file), true);
                    $position = -1;
                    foreach($array as $key => $item){
                        if($item['name'] == $name){
                            $position = $key;
                        }
                    }

                    $price = $array[$position]['price'];
                    $array[$position]['price'] = $text;
                    saveFileWithEncodeArray($paypals_section_file, $array);
                    bot('sendMessage',[
                        'chat_id'=>$chat_id,
                        'text'=>"تم تعديل السعر من " . $price . DOLLAR_ICON . " الى " . $text . DOLLAR_ICON . " بنجاح",
                    ]);
                    setMenu($menu_section, 2);
                    return false;
                }

                if($menu_num == 4){
                    if($text == BACK_TEXT){
                        setMenu($menu_section, 2);
                        return false;
                    }

                    $name = $shared_preferences['name'];
                    $paypals_section_file = $section_data_folder . "paypals.json";
                    $array = json_decode(file_get_contents($paypals_section_file), true);
                    $position = -1;
                    foreach($array as $key => $item){
                        if($item['name'] == $name){
                            $position = $key;
                        }
                    }

                    $array[$position]['name'] = $text;
                    saveFileWithEncodeArray($paypals_section_file, $array);
                    $shared_preferences['name'] = $text;
                    saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                    bot('sendMessage',[
                        'chat_id'=>$chat_id,
                        'text'=>"تم تعديل الاسم بنجاح",
                    ]);
                    setMenu($menu_section, 2);
                    return false;
                }
                
                if($menu_num == 5){
                    return false;
                }

                if($menu_num == 6){
                    return false;
                }

                if($menu_num == 7){
                    return false;
                }

                if($menu_num == 8){
                    if($text == BACK_TEXT){
                        setMenu($menu_section, 2);
                        return false;
                    }

                    if(is_numeric($text)){
                        $shared_preferences['purchase_price'] = $text;
                        saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                        setMenu($menu_section, 9);
                        return false;
                    }else{
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>"عذرا يجب ارسال قيمة رقمية",
                        ]);
                        return false;
                    }
                    return false;   
                }

                if($menu_num == 9){
                    if($text == "تم ✅"){
                        $array = json_decode(getFilesName($temp_folder),true);
                        $count = count($array);
                        if($count == 0){
                            setMenu($menu_section, 2);
                            return false;
                        }
                        $bot = bot('sendMessage',[
                            'text'=>"الرجاء الانتظار قليلا ...",
                            'chat_id'=>$chat_id,
                        ]);
                        for($i=0; $i<$count; $i++){
                            $file_name = $temp_folder . $array[$i];
                            $order = file_get_contents($file_name);
                            unlink($file_name);
                            $file_name = $accounts_folder . $shared_preferences['id'] . "/" . $array[$i];
                            file_put_contents($file_name, $order);
                        }
                        bot('editMessageText',[
                            'message_id'=>$bot->result->message_id,
                            'chat_id'=>$chat_id,
                            'text'=>"تمت الإضافة بنجاح ✅" . EQUALS . "عدد الحسابات المضافة : " . $count . NEW_LINE . "القسم : " . $shared_preferences['name'] . NEW_LINE . "راس المال : " . $shared_preferences['purchase_price'],
                        ]);
                        sendForAdmins("accounts_added", $count);
                        setMenu($menu_section, 2);
                    }else{
                        addNewAccount($text);
                    }
                    return false;
                }

            }
        }

        if($data){
            if($menu_num == 5){
                if($data == BACK_TEXT){
                    setMenu($menu_section, 2);
                    return false;
                }

                $file_name = str_replace("show_product_", "", $data);
                $file_path = $accounts_folder . $shared_preferences['id'] . '/' . $file_name;
                $file_data = json_decode(file_get_contents($file_path), true);

                bot('editMessageText',[
                    'message_id'=>$message_id,
                    'chat_id'=>$chat_id,
                    'text'=>'رقم المنتج : 1' . NEW_LINE . "القسم : بايبالات" . NEW_LINE . "المنتج : " . $file_data['section_name'] . NEW_LINE . "اسم الادمن : " . $file_data['admin_name'] . NEW_LINE . "ايدي الادمن : " . $file_data['admin_id'] . NEW_LINE . "رأس المال : " . $file_data['purchase_price'] . NEW_LINE . "رقم الطلب : " . $file_data['order_name'] . EQUALS . $file_data['account'],
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'inline_keyboard'=>[
                            [['text'=>"حذف", "callback_data"=>"delete_file_" . $file_name]],
                            [['text'=>BACK_TEXT, "callback_data"=>BACK_TEXT]],
                        ]
                    ])
                ]);
                setMenu($menu_section, 6);
                return false;
            }

            if($menu_num == 6){
                if($data == BACK_TEXT){
                    bot('deleteMessage',[
                        'chat_id'=>$chat_id,
                        'message_id'=>$message_id
                    ]);
                    setMenu($menu_section, 5);
                    return false;
                }

                $file_name = str_replace("delete_file_", "", $data);
                $shared_preferences['file_name'] = $file_name;
                saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                $file_path = $accounts_folder . $shared_preferences['id'] . '/' . $file_name;
                $file_data = file_get_contents($file_path);
                bot('editMessageText',[
                    'message_id'=>$message_id,
                    'chat_id'=>$chat_id,
                    'text'=>'رقم المنتج : 1' . NEW_LINE . $file_data . EQUALS . "هل انت متأكد من حذف الحساب",
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'inline_keyboard'=>[
                            [['text'=>BACK_TEXT, "callback_data"=>BACK_TEXT], ['text'=>"نعم متأكد", "callback_data"=>"نعم متأكد من الحذف"]],
                            
                        ]
                    ])
                ]);
                setMenu($menu_section, 7);
                return false;
            }

            if($menu_num == 7){
                if($data == BACK_TEXT){
                    bot('deleteMessage',[
                        'chat_id'=>$chat_id,
                        'message_id'=>$message_id
                    ]);
                    setMenu($menu_section, 5);
                    return false;
                }else if($data == "نعم متأكد من الحذف"){
                    $file_name = $shared_preferences['file_name'];
                    $file_path = $accounts_folder . $shared_preferences['id'] . '/' . $file_name;
                    $file_data = file_get_contents($file_path);
                    unlink($file_path);
                    $array_data = [];
                    $array_data['file_data'] = $file_data;
                    $array_data['time_deleted'] = TIME_SY;
                    $array_data['who_deleted'] = $chat_id;
                    $array_data['file_name'] = $file_name;
                    file_put_contents($orders_folder . "deleted_files/" . TIME_SY . "_" . getRandom("all", 10), json_encode($array_data));
                    bot('editMessageText',[
                        'message_id'=>$message_id,
                        'chat_id'=>$chat_id,
                        'text'=>"id : " . $shared_preferences['id'] . NEW_LINE . "order : " . $file_name,
                    ]);
                    setMenu($menu_section, 5);
                    return false;
                }
                return false;
            }
        }

        if($inline_query){

        }
    }
    return false;
?>