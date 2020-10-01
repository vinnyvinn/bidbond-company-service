<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Knox\AFT\AFT;
use Knox\AFT\Exceptions\ATFException;
use App\Traits\InvokeSms;

class SendSMS implements ShouldQueue
{
    use  InteractsWithQueue, Queueable, SerializesModels;

    protected $phone_number;
    protected $message;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($phone_number, $message)
    {
        $this->phone_number = $phone_number;
        $this->message = $message;
    }


    public function handle():void
    {
         // if (config('aft.enable_sms')) {
           // AFT::sendMessage($this->phone_number, $this->message);
            InvokeSms::initSms($this->phone_number,$this->message);
      //  }
        Log::info($this->phone_number . " : " . $this->message);

    }
}
