<?php


namespace App\Tools;

use GuzzleHttp\Client;

class DocumentorClient
{
    /**
     * Making http/https requests with post methods with basic authentication
     * @param array $config
     */
    public static function postBasic(array $conf, $data=null)
    {
        $basic = base64_encode($conf['username'] . ':' . $conf['password']);
        $headers = ['Authorization' => 'Basic ' . $basic];
        $httpClient = new Client([
           'headers' => $headers
        ]);
        $res = $httpClient->request('post',$conf['url'], ['json'=> $data]);
        if ($res->getStatusCode() != 200) {
            return ['error'=> 'Bad response from consumer'];
        }
        $result = json_decode($res->getBody()->getContents());
        return ['error'=> null,'data' => $result];
    }

    /**
     * Making http/https requests with post methods with bearer authentication
     * @param array $conf
     * @param array $data
     * @param string $token
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function postBearer(array $conf, array $data, string $token, $content_type='multipart/form-data',$request_param = 'json')
    {
        $headers = [
            'Authorization' => 'Bearer '. $token,
            'Content-Type'  => $content_type
        ];

        $httpClient = new Client([
            'headers' => $headers,
        ]);

        $res = $httpClient->request('post',$conf['url'],[$request_param => $data]);
        $code =$res->getStatusCode();
        if ($code == 200 || $code == 201) {
            $result = json_decode($res->getBody()->getContents());
            return ['error'=> null,'data' => $result];
        }

        return ['error'=> 'Bad response from consumer'];
    }

    /**
     * Get requests
     * @param string $token
     * @param string $url
     * @param array $urlParams
     * @param bool $debug
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function get(string $token, string $url, array $urlParams = [], $debug = false)
    {
        try {
            $headers = [
                'Authorization' => 'Bearer '. $token
            ];
            $params = [
                'connect_timeout' => 20,
                'timeout' => 20,
                'debug' => $debug,
            ];
            // build query string
            $url2 = null;
            foreach ($urlParams as $key => $value) {
                if (!empty($url2)) {
                    $url2 .= '&';
                }
                $url2 .= $key . '=' . $value;
            }
            if (!empty($url2)) {
                $url = $url . '?' . $url2;
            }
            $HttpClient = new Client([
                'headers' => $headers
            ]);
            $res = $HttpClient->request('get',$url, $params);

            if ($res->getStatusCode() != 200) {
                throw new \Exception();
            }
            return json_decode($res->getBody()->getContents());
        } catch (GuzzleException | \Throwable | \Error | \Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    //**Curl-variant**//


    /**
     * Method which uses curl labrary
     * @param string $token
     * @param array $data
     * @param string $url
     * @return array
     */
    public static function postCurl(string $token, array $data, string $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'content-type: multipart/form-data'
            ]
        );
        $result = curl_exec($ch);
        $returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if(!$result && $returnCode != 200){
            return ['error'=>'Get codes not 200 from curl', 'status' => 'error'];
        }
        return ['error' => null, 'status' => 'ok'];
    }



}
