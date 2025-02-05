<?php
    include "../../config.php";
    $SECTION = "recharge_balance_section";
    $part_menu = "شحن الرصيد";
    $section_data_folder = __DIR__ . "/../";
    $data_folder = __DIR__ . "/../../Data/";
    $orders_folder = __DIR__ . "/../../Data/Orders/";
    $accounts_folder = __DIR__ . "/../../Data/Accounts/";
    $users_folder = __DIR__ . "/../../Data/Users/";
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

    function sendForAdmins($type){
        global $chat_id, $admins_folder, $shared_preferences , $balance;
        
        $isPhoto = false;
        $isText = true;
        $number = $shared_preferences['number'];
        if($shared_preferences['number'] == "isphoto"){
            $isPhoto = true;
            $number = "تم إرفاق صورة لعملية الدفع";
        }

        

        $text = "🌟 عملية دفع جديدة 🌟" . EQUALS . "الايدي : " . $chat_id . NEW_LINE . "الاسم الأول : " . getUserInformation($chat_id, "first_name") . NEW_LINE . "الاسم الأخير : " . getUserInformation($chat_id, "last_name") . NEW_LINE . "المعرف : @" . getUserInformation($chat_id, "user_name") . NEW_LINE . "الرصيد : " . $balance . NEW_LINE . "طريقة الدفع : " . $shared_preferences['name'] . NEW_LINE . "المبلغ المدفوع : " . $shared_preferences['amount'] . NEW_LINE . "رقم العملية : " . $number . NEW_LINE . "رقم الطلب : " . $shared_preferences['order_name']; 
        
        $Admins = json_decode(getFoldersName($admins_folder), true);
        
        for ($i = 0; $i < count($Admins); $i++) {
            $Admin = $Admins[$i];
            $admin_type_file = $admins_folder . $Admin . '/type';
            $admin_type = json_decode(file_get_contents($admin_type_file), true);
            
            if(!($admin_type["all"] == "true" or $admin_type['recharge_balance_user'] == "true")) continue;
            if($isPhoto){
                bot('sendPhoto',[
                    'chat_id'=>$Admin,
                    'caption'=>"ID : " . $chat_id,
                    'photo'=>$shared_preferences['photo_file_id'],
                ]);
            }
            
            if($isText && $text !== ""){
                bot('sendMessage',[
                    'chat_id'=>$Admin,
                    'text'=>$text,
                    'reply_markup'=>json_encode([
                        'resize_keyboard'=>true,
                        'inline_keyboard'=>[
                            [['text'=>"فتح العملية", 'callback_data'=>"open_recharge_balance_user"]],
                        ]
                    ])
                ]);
            }
        }
    }

    function setMenu($menu_section, $menu_num){
        global $menu_file, $chat_id,$shared_preferences, $first_name, $user_name, $last_name, $balance, $jsonData, $SECTION, $section_data_folder;

        $menu = [];
        $menu['section'] = $menu_section;
        $menu['num'] = $menu_num;
        saveFileWithEncodeArray($menu_file, $menu);

        if($menu_section == $SECTION){
            if($menu_num == 0){
                $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                if(!file_exists($recharge_balance_section_file)) file_put_contents($recharge_balance_section_file, "{}");
                $array_data = json_decode(file_get_contents($recharge_balance_section_file), true);
                $keyboard = convertToTelegramKeyboard($array_data);
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "قم باختيار طريقة الدفع",
                    'reply_markup' => json_encode([
                        'resize_keyboard' => true,
                        'keyboard' => $keyboard
                    ])
                ]);
            }

            if($menu_num == 2){
                
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "قم بادخال المبلغ الذي قمت بتحويله",
                    'reply_markup' => json_encode([
                        'resize_keyboard' => true,
                        'keyboard' => [
                            [['text'=>BACK_TEXT, 'callback_data'=>BACK_TEXT]],
                        ]
                    ])
                ]);
                return false;
            }

            
            if($menu_num == 3){
                $number = $shared_preferences['number'] == "isphoto"?"تم إرفاق صورة لعملية الدفع":$shared_preferences['number'];
                bot('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "العملية : " . "شحن رصيد" . NEW_LINE . "طريقة الدفع : " . $shared_preferences['name'] . NEW_LINE . "المبلغ : " . $shared_preferences['amount'] . NEW_LINE . "رقم العملية : " . $number . EQUALS . "يرجى التحقق من المعلومات قبل الارسال",
                    'reply_markup' => json_encode([
                        'resize_keyboard' => true,
                        'keyboard' => [
                            [['text'=>BACK_TEXT, 'callback_data'=>BACK_TEXT], ['text'=>"تأكيد ✅", 'callback_data'=>"تأكيد ✅"]],
                        ]
                    ])
                ]);
                return false;
            }
        }

        if($menu_section == "myaccount_section"){
            $jsonData = str_replace("\u062a\u0623\u0643\u064a\u062f \u2705", "\u0631\u062c\u0648\u0639 \ud83d\udd19", $jsonData);
            connection(FUNCTIONS_URL . "User/myaccount_section.php", $jsonData);
            exit();
        }

        
        
    }

    function createOrder(){
        global $shared_preferences, $orders_folder, $chat_id, $balance;
        $order_name = $shared_preferences['order_name'];
        $order_file = $orders_folder . 'recharge_balance_user' . '/' . $order_name;
        $array_data = [];
        $array_data['order_name'] = $order_name;
        $array_data['user_id'] = $chat_id;
        $array_data['first_name'] = getUserInformation($chat_id, "first_name");
        $array_data['last_name'] = getUserInformation($chat_id, 'last_name');
        $array_data['user_name'] = getUserInformation($chat_id, "user_name");
        $array_data['balance_before'] = $balance;
        $array_data['amount'] = $shared_preferences['amount'];
        $array_data['name'] = $shared_preferences['name'];
        $array_data['number'] = $shared_preferences['number'];
        $array_data['state'] = 'pending';
        saveFileWithEncodeArray($order_file, $array_data);
    }

    if($menu_section == $SECTION){
        if($message){
            if($text){
                if($text == $part_menu) setMenu($menu_section, 0);

                if($menu_num == 0){
                    if($text == BACK_TEXT){
                        setMenu("myaccount_section", 0);
                        return false;
                    }

                    $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                    if(!file_exists($recharge_balance_section_file)) file_put_contents($recharge_balance_section_file, "{}");
                    $array_data = json_decode(file_get_contents($recharge_balance_section_file), true);
                    $found = false;
                    foreach($array_data as $key => $item){
                        if($item['name'] == $text and $item['visible'] == "true"){
                            $shared_preferences['name'] = $item['name'];
                            $shared_preferences['type'] = $item['type'];
                            $shared_preferences['position'] = $key;
                            saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                            $found = true;
                            $array = $item;
                        }
                    }

                    if($found){
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>$array['text'],
                            'parse_mode'=>'markdown',
                            'reply_markup'=>json_encode([
                                'resize_keyboard'=>true,
                                'keyboard'=>[
                                    [['text'=>BACK_TEXT, 'callback_data'=>BACK_TEXT]],
                                ]
                            ])
                        ]);
                        setMenu($menu_section, 1);
                    }else{

                    }
                    return false;
                }

                if($menu_num == 1){
                    $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                    if(!file_exists($recharge_balance_section_file)) file_put_contents($recharge_balance_section_file, "{}");
                    $array = json_decode(file_get_contents($recharge_balance_section_file), true);
                    $position = $shared_preferences['position'];
                    if($text == BACK_TEXT){
                        setMenu($menu_section, 0);
                        return false;
                    }
                    if($array[$position]['visible'] == "true"){
                        if($array[$position]['type'] == "text" or $array[$position]['type'] == "photo+text"){
                            $shared_preferences['number'] = $text;
                            saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                            setMenu($menu_section, 2);
                            return false;
                        }else{
                            bot('sendMessage',[
                                'chat_id'=>$chat_id,
                                'text'=>"عذرا يجب ارسال صورة لعملية الدفع"
                            ]);
                            return false;
                        }
                    }
                    return false;
                }

                if($menu_num == 2){
                    if(is_numeric($text)){
                        $shared_preferences['amount'] = $text;
                        saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                        setMenu($menu_section, 3);
                    }else{
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>"عذرا يجب ارسال قيمة رقمية فقط" . NEW_LINE . "بدون رموز" . NEW_LINE . "منال : 10000" . " أو " . "5.4",
                        ]);
                    }
                    return false;
                }

                if($menu_num == 3){
                    if($text == BACK_TEXT){
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>'تم الغاء العملية بنجاح ✅'
                        ]);
                    }else if($text == "تأكيد ✅"){
                        $shared_preferences['order_name'] = TIME_SY . "_" . getRandom("all", 8);
                        saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                        bot('sendMessage',[
                            'chat_id'=>$chat_id,
                            'text'=>"رقم الطلب : " . $shared_preferences['order_name'] . NEW_LINE . 'تم إرسال طلبك إلى الأدمن.' . NEW_LINE . "سيتم إعلامك عند التحقق من عملية التحويل" . NEW_LINE . "⚠️ تستغرق العملية من 1 ساعة ل 24 ساعة" . NEW_LINE . "سيتم اضافة الرصيد بشكل تلقائي",
                        ]);
                        createOrder();
                        sendForAdmins("recharge_balance_user");
                    }
                    setMenu("myaccount_section", 0);
                    return false;
                }
                return false;
            }

            if($photo){
                if($menu_num == 1){
                    $recharge_balance_section_file = $section_data_folder . "recharge_balance_section.json";
                    if(!file_exists($recharge_balance_section_file)) file_put_contents($recharge_balance_section_file, "{}");
                    $array = json_decode(file_get_contents($recharge_balance_section_file), true);
                    $position = $shared_preferences['position'];
                    if($array[$position]['visible'] == "true"){
                        if($array[$position]['type'] == "photo" or $array[$position]['type'] == "photo+text"){
                            $shared_preferences['number'] = "isphoto";
                            $photo = end($photo);
			                $Photo_file_id = $photo->file_id;
                            $shared_preferences['photo_file_id'] = $Photo_file_id;
                            saveFileWithEncodeArray($shared_preferences_file, $shared_preferences);
                            setMenu($menu_section, 2);
                            return false;
                        }else{
                            bot('sendMessage',[
                                'chat_id'=>$chat_id,
                                'text'=>"عذرا يجب ارسال رقم عملية الدفع"
                            ]);
                            return false;
                        }
                    }
                }
                return false;
            }
            return false;
        }

        if($data){

        }

        if($inline_query){

        }
    }
    return false;
?>