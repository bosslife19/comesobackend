<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Complain extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $complain;

    public function __construct($name, $email, $complain)
    {
        $this->name = $name;
        $this->email = $email;
        $this->complain = $complain;
    }

    public function build()
    {
        return $this->view('emails.complain')
                    ->subject("New Complaint from $this->name")
                    ->with([
                        'name' => $this->name,
                        'email'=>$this->email,
                        'complain'=>$this->complain
                    ]);
    }
}
