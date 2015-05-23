<?php
class Vk {

    public $users  = [];
    public $errors = [];
    private $storage = [];
    private $current_user = 0;

    private $client_id = 3697615;
    private $client_secret = 'AlVXZFMUqyrnABp8ncuU'; //Используются данные офф windows приложения
                                                     //для аутентификации по логину/паролю

    public function __construct($users) {
        foreach ($users as $key => $user) {
            $this->users[] = [
                'login'    => $user['login'],
                'password' => $user['password'],
                'token'    => false,
            ];

            $token = $this->authorization($this->users[$key]);
            if ($token) {
                $this->users[$key]['token'] = $token;
            }
        }

    }

    public function send_task($task) {
        $task = preg_replace('/[^0-9_]/', '', $task);
        $task = explode('_', $task);
        $this->storage[] = ['owner' => $task[0], 'item' => $task[1]];
    }

    public function get_tasks() {
        return $this->storage;
    }

    public function start() {
        foreach ($this->storage as $item) {
            $response = $this->execute('likes.add',
                ['type'=>'photo', 'owner_id'=>$item['owner'], 'item_id'=>$item['item']],
                $this->users[$this->current_user]['token']);

            if (isset($response->error)) {
                if ($response->error->error_code == 14) {
                    $this->errors[] = 'Для пользователя ' . $this->users[$this->current_user] . 'Требуется капча';
                    $this->current_user = (count($this->users) >= $this->current_user)?$this->current_user++ : $this->current_user = 0;
                } elseif ($response->error->error_code != 6) {
                    array_shift($this->storage);
                    $this->errors[] = 'Не удалось лайкнуть photo'.$item['owner'].'_'.$item['item'] .
                    ' | ' . $response->error->error_code;
                }
            } else {
                array_shift($this->storage);
            }
            usleep(130000); // Как показали экперименты оптимальное время
        }
    }

    private function authorization($user) {
        $auth_request_string = 'https://oauth.vk.com/token?grant_type=password&client_id='.$this->client_id.'&client_secret='.$this->client_secret.'&username='.$user['login'].'&password='.$user['password'];
        $response = $this->execute(null, null, null, $auth_request_string);

        if (isset($response->error)) {
            $this->errors[] = $response->error . ' - ' . $response->error_description;
            return false;
        } else {
            return $response->access_token;
        }
    }

    public function execute($method, $params, $token, $string = null) {
        if (!$string) {
            $par = '';
            foreach ($params as $key=>$val) {
                $par .= $key.'='.$val.'&';
            }
            $string = 'https://api.vk.com/method/'.$method.'?'.$par.'access_token='.$token;
        }

        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $string);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        $query = curl_exec($curl_handle);
        curl_close($curl_handle);
        return json_decode($query);
    }

    public function get_errors() {
        return $this->errors;
    }
}