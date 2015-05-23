<?php
$gearman_servers = [
    '127.0.0.1:4730',
];

$users = [
    [
        'login'=>'87775556666',
        'password' => '123456',
    ]
];

require_once 'vk_client.php';


$vk_client = new Vk($users);

$worker = new GearmanWorker();
$worker->addServers(implode(',', $gearman_servers));
$worker->setTimeout(29000);
$worker->addFunction('photo_liker', 'do_tasks');

while($worker->work()){};

function do_tasks($job) {
    global $vk_client;

    $photos = json_decode($job->workload());
    /*
     * Предположим, что данные приходят в виде ["photo454545_454545","photo565656_565656"]
     */

    foreach ($photos as $photo) {
        $vk_client->send_task($photo);
    }

    $vk_client->start();

    if ($vk_client->get_errors()) {
        foreach ($vk_client->get_errors() as $error) {
            file_put_contents('errors.txt', '['.date("d.m.Y H:i:s").'] '.$error."\r\n", FILE_APPEND);
        }
    }
}

















