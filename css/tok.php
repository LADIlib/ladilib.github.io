<?php
if (!isset($_REQUEST)) die('Nothing was sent');
 
$settings = array(
    // ID вашей страницы ВК
    'id' => 278186707,
    // Токен от API Чат-менеджера
    'token' => 'c5fe1bc81e1f2a173b15f66185259c51',
    // Токен от API VK
    'access_token' => '4668d2595fc7715f2a8c88e5c9110bd5c5f75a5f6fcddc4ae12f55d93f39698e5bda8fcff0730ae818cfc'
);
 
// Список ваших чатов. Строка слева - UID. Число справа - id чата на вашей странице.
$chats = array(
    'DDe' => 1,
    'ecADD' => 3
);
 
// Получаем и декодируем Callback запрос
$rawjson = file_get_contents('php://input');
if (!$rawjson) die('Пустой запрос!');
$json = json_decode(utf8_encode($rawjson), true);
 
//Строка для подтверждения при первом запросе
$confirmation_token = md5($settings['id'].$settings['token']);
 
// Определение типа события и обработка данных
switch ($json['type']){
  case 'confirm':
    die($confirmation_token);
    break;
  case 'invite':
    // ID пользователя которого надо пригласить
    $user = $json['data']['user'];
    // UID чата
    $chat = $json['data']['chat'];
    // Вызов VK API.
    if (isset($chats[$chat])) file_get_contents("https://api.vk.com/method/execute?v=5.100&access_token={$settings['access_token']}&code=".urlencode("if (API.friends.areFriends({user_ids: $user})[0].friend_status == 3){return API.messages.addChatUser({chat_id: {$chats[$chat]}, user_id: $user});}return 0;"));
    break;
  case 'ban_expired':
    // ID пользователя у которого истек бан
    $user = $json['data']['user'];
    // UID чата
    $chat = $json['data']['chat'];
    // Вызов VK API.
    if (isset($chats[$chat])) file_get_contents("https://api.vk.com/method/execute?v=5.100&access_token={$settings['access_token']}&code=".urlencode("if (API.friends.areFriends({user_ids: $user})[0].friend_status == 3){return API.messages.addChatUser({chat_id: {$chats[$chat]}, user_id: $user});}return 0;"));
    break;
  case 'delete_for_all':
    // Список сообщений, которые нужно удалять (массив)
    $ids = $json['data']['conversation_message_ids'];
    // UID чата
    $chat = $json['data']['chat'];
    // Вызов VK API.
    if (isset($chats[$chat])) file_get_contents("https://api.vk.com/method/execute?v=5.100&access_token={$settings['access_token']}&code=".urlencode("return API.messages.delete({delete_for_all: 1, message_ids: API.messages.getByConversationMessageId({peer_id: ".(2000000000+$chats[$chat]).", conversation_message_ids: \"".implode(',', $ids)."\"}).items@.id});"));
    break;
  case 'message_pin':
    // ID сообщения которое нужно закрепить
    $msg = $json['data']['conversation_message_id'];
    // UID чата
    $chat = $json['data']['chat'];
    // Вызов VK API.
    if (isset($chats[$chat])) file_get_contents("https://api.vk.com/method/execute?v=5.100&access_token={$settings['access_token']}&code=".urlencode("return API.messages.pin({peer_id: ".(2000000000+$chats[$chat]).", message_id: API.messages.getByConversationMessageId({peer_id: ".(2000000000+$chats[$chat]).", conversation_message_ids: \"".implode(',', $ids)."\"}).items@.id[0]});"));
    break;
  case 'photo_update':
    // ID сообщения которое нужно закрепить
    $photo = $json['data']['photo'];
    // UID чата
    $chat = $json['data']['chat'];
    // Вызов VK API.
    if (isset($chats[$chat])){
        $server = json_decode(file_get_contents("https://api.vk.com/method/photos.getChatUploadServer?v=5.100&access_token={$settings['access_token']}&chat_id={$chats[$chat]}"))->response->upload_url;
        file_put_contents('temp.jpeg', base64_decode($photo));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $server);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array('file' => new CURLFile('temp.jpeg')));
        $vkphoto = json_decode(curl_exec($ch))->response;
        file_get_contents("https://api.vk.com/method/messages.setChatPhoto?v=5.100&access_token={$settings['access_token']}&file=$vkphoto");
    }
    break;
  default:
    break;
}
?>
