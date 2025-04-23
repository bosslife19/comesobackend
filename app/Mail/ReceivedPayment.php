<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReceivedPayment extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $sender;
    public $amount;
    public $date;
    public $transId;
    

    public function __construct($name, $sender, $amount, $date, $transId)
    {
        $this->name = $name;
        $this->sender = $sender;
        $this->amount = $amount;
        $this->date = $date;
        $this->transId = $transId;
    }

    public function build()
    {
        return $this->view('emails.received-payment')
                    ->subject("You've Received Vouchers!")
                    ->with([
                        'name' => $this->name,
                        'sender'=>$this->sender,
                        'amount'=>$this->amount,
                        'date'=>$this->date,
                        'transId'=>$this->transId,

                    ]);
    }
}
