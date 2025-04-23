<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SentVoucher extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $receiver;
    public $amount;
    public $date;
    public $transId;

    public function __construct($name, $receiver, $amount, $date, $transId)
    {
        $this->name = $name;
        $this->amount = $amount;
        $this->date = $date;
        $this->transId = $transId;
        $this->receiver = $receiver;
    }
    

    public function build()
    {
        return $this->view('emails.sent-voucher')
                    ->subject('✅ Your Voucher Transfer Was Successful!')
                    ->with([
                        'name' => $this->name,
                        'amount'=>$this->amount,
                        'date'=>$this->date,
                        'transId'=>$this->transId,
                        'receiver'=>$this->receiver,
                    ]);
    }
}
