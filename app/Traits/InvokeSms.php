<?php


namespace App\Traits;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class InvokeSms
{
    public static function initSms($phone,$message){
        info('init sms');
        $client = new Client();
        $headers = [
            'Accept' => 'application/json',
            'app-key' => config('aft.appKey'),
            'app-secret' => config('aft.appSecret'),
        ];
        $body =["profile_id"=>config('aft.profileId'), "phone_number"=>$phone,"message"=> $message."\n\n Regards,\n JBID"];
       try {
            $response = $client->post(config('aft.AppLink'),  ['json'=>$body,'headers'=>$headers]);
            return $response->getBody()->getContents();

        }catch (ClientException $e) {
            return $e->getResponse()->getBody()->getContents();
        }catch (GuzzleException $e) {
            Log::error("GuzzleException: ". $e->getMessage());
        }catch (Exception $e){
            Log::error("Exception: ". $e->getMessage());
        }
    }
}
