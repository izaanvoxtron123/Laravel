<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Mails extends Mailable
{
    use Queueable, SerializesModels;
    public $detail,$file;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($detail,$file)
    {
        $this->detail = $detail;
        $this->file = $file;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.'.$this->file);
    }
}