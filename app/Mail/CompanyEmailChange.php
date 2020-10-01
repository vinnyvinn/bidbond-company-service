<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompanyEmailChange extends Mailable
{
    use Queueable, SerializesModels;

    public $company;
    public $old_email;
    public function __construct($company,$old_email)
    {
        $this->company = $company;
        $this->old_email = $old_email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.company_email_change');
    }
}

