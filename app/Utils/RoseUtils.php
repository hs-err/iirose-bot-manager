<?php


namespace App\Utils;

use GuzzleHttp\Client;

class RoseUtils
{
    public static function sendPersonChat(
        $user_id,
        $message,
        $color = '6ebadb'
    ) {
        $client = new Client();
        $client->get(
            'http://localhost:9502/sendPersonChat', [
            'query' => [
                'user_id' => $user_id,
                'message' => $message,
                'color' => $color,
            ],
            ]
        );
    }

    public static function getInfo(
        $username
    ) {
        $client = new Client();
        $response = $client->get(
            'http://localhost:9502/getInfo', [
            'query' => [
                'username' => $username
            ],
            ]
        );
        $result = $response->getBody()->getContents();
        return $result == 'null' ? null : json_decode($result);
    }

    public static function at($username)
    {
        return ' [*' . $username . '*] ';
    }

    public static function ro($room_id)
    {
        return ' [_' . $room_id . '_] ';
    }
}
