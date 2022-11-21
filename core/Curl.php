<?php

namespace Core;

class Curl
{
    /**
     * Выполнение GET запроса по указаному адресу.
     * @param string $url Адрес
     * @param array $params Параметры
     * @return mixed
     */
    public static function get(string $url, array $params = [])
    {
        if (!empty($params)) $url .= '?' . http_build_query($params);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);
        curl_close($ch);
         
        return $result;
    }

    /**
     * Выполнение post запроса по указаному адресу.
     * @param string $url Адрес
     * @param array $params Параметры
     * @return mixed
     */
    public static function post(string $url, array $params = [])
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $result = curl_exec($ch);
        curl_close($ch);	
         
        return $result;
    }
}