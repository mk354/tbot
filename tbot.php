<?php
/*
 * Класс для отправки уведомлений в чат телеграмма.
 * Использование:
 * $bpot= new tbot(bot_id, token), где bot_id- идентификатор бота, token- токен бота.
 * Отправить сообщение: $bot->sendMessageToChat($chatid, $message), chatid- id чата со знаком '-', message- произвольный текст
 * Если в процессе отправки сообщения произойдет ошибка (пустое сообщение, несуществующий чат, неправильный токен, id бота и т.д.)
 * будет отпрвлено сообщение об ошибке в группу is74bot_error (id="-244471930").
 *
 * Перед отправкой сообщений бота необходимо добавить в группы, в которые необходимо рассылать уведомления.
 *
 * Класс  предполагается использовать для рассылки уведомлений об ошибках при обработке заявок с сайта и лендиногов. В случае,
 * если поступила заявка снекорректным номером, бот отправит сообщение в группу "Аварийные заявки"(id="-173789536")
 */
class tbot
{
    const ERROR_BOT_AUTH='bot280522693:AAEXrwF_5X4h5Hdj-RG-rUUPM-8cjtF_kDs'; //параметры автризации бота для отправки ошибок
    const ERROR_CHAT='-244471930'; //Чат для сообщений об ошибках при отправке сообщений

    private $bot_id;
    private $token;
    private $messagesList=array();//Список сообщений для рассылки
    /*
     * $bot_id - id бота
     * $token - токен
     * @param string bot_id
     * @param string token
     */
    public function __construct($bot_id, $token)
    {
        $this->bot_id=$bot_id;
        $this->token=$token;
    }

    /*
     * Отправляет одно сообщение
     * @param string chatid id чата/группы телеграмм
     * @param string message текст сообщения
     */
    public function sendMessageToChat($chatid=false, $message=false)
    {
        $text=iconv('windows-1251', 'UTF-8', $message);
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, 'https://api.telegram.org/'.$this->bot_id.':'.$this->token.'/sendMessage?chat_id='.$chatid.'&text='.urlencode($text));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $out = json_decode(curl_exec($curl), true);
            curl_close($curl);
            if (!$out['ok']){
                $data=array(
                    'type'=>'tochat',
                    'botid'=>$this->bot_id,
                    'chatid'=>$chatid,
                    'message'=>$text,
                    'error_code'=>$out['error_code'],
                    'error_description'=>$out['description'],
                );
                $this->sendErrorMessage($data);
            }
            else {
                return $out;
            }
        }
        return false;
    }




    /*
     * Добавляет сообщение в список сообщений
     * @param string $message  сообщение для добавления в очередь
     */
    public function addMessageToList($chat_id=false, $message=false)
    {
        if ($message && $chat_id){
            array_push($this->messagesList, array(
                'chat_id'=>$chat_id,
                'message'=>$message,
                ));
            return true;
        }
        return false;
    }

    /*
     * осуществляет рассылку ранее добавленых сообщений в очередь messagesList
     */
    public function sendMessagesFromList()
    {
        if (!empty($this->messagesList)){
            foreach($this->messagesList as $message){
                $this->sendMessageToChat($message['chat_id'], $message['message']);
            }
        }
    }


    /*
     * Отправляет сообщение в группу, если при выполеннии запроса произошла ошибка
     */
    private function sendErrorMessage($data)
    {
        $text=json_encode($data);
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, 'https://api.telegram.org/'.self::ERROR_BOT_AUTH.'/sendMessage?chat_id='.self::ERROR_CHAT.'&text='.urldecode($text));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $out = curl_exec($curl);
            curl_close($curl);
            print_r($out);
            return $out;

        }
    }
}
