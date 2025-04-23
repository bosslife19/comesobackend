<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestAccepted extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    
    

    public function __construct($name)
    {
        $this->name = $name;
        
    }

    public function build()
    {
        return $this->view('emails.request-accepted')
                    ->subject("Your payment request has been accepted!")
                    ->with([
                        'name' => $this->name,
                        

                    ]);
    }
}
