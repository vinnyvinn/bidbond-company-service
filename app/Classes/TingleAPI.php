<?php

namespace App\Classes;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

class TingleAPI
{
    private $instance;
    private $shared_key;
    private $endpoint;
    private $version;

    public function __construct()
    {
        $this->instance = config('services.tingle.instance');
        $this->shared_key = config('services.tingle.shared_key');
        $this->version = config('services.tingle.version');
    }

    public function getIdentity($type, $value)
    {
        $this->endpoint = 'iprs';
        $datetime = str_replace('+0000', 'GMT', Carbon::now()->format('D, d M Y H:i:s O'));
        $string_to_hash = "GET\n" . "0" . "\n\n" . "x-ms-date:" . $datetime . "\n/api/" . $this->version . "/" . $this->endpoint;
        $hashed_string = $this->getSignature($string_to_hash);
        $query  = [
            $type => $value
        ];
        return $this->makeRequest($hashed_string, $datetime, $query);
    }

    public function getByID($idnumber)
    {
        return $this->getIdentity('idNumber', $idnumber);
    }

    public function getByPhone($phone)
    {
        return $this->getIdentity('phoneNumber', $phone);
    }

    private function getSignature($string_to_hash)
    {
        $utf8_string = utf8_encode($string_to_hash);
        $sig = hash_hmac('sha256', $utf8_string, base64_decode($this->shared_key), true);
        return 'SharedKey ' . base64_encode($sig);
    }

    private function makeRequest($signature, $datetime, $query = [])
    {
        $url = "https://" . $this->instance . ".people.tinglesoftware.com/api/" . $this->version . "/" . $this->endpoint;


        try {
            $data = [
                "headers" => [
                    "x-ms-date" => $datetime,
                    "Authorization" => $signature,
                    "Accept" => "application/json",
                ]
            ];
            if ($query) {
                $data["query"] = $query;
            }
            $client = new Client();
            $response = $client->request('GET', $url, $data);

            $response = $response->getBody()->getContents();

            return $response;
        } catch (RequestException $e) {
            Log::error(print_r($e->getResponse()->getBody()->getContents()));
        } catch (GuzzleException $e) {
            Log::error(print_r($e->getMessage()));
        } catch (Exception $e) {
            Log::error(print_r($e->getMessage()));
        }
        return false;
    }
}

