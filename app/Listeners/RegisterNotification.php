<?php

namespace App\Listeners;

use App\Mail\RegisterMail;
use App\Events\RegisterEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisterNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(RegisterEvent $event): void
    {
        $user = $event->user;
        $code = generateRandomNumber(8);
        DB::table('register_verification_codes')->insert([
            'email' => $user->email,
            'code'  => $code,
            'created_at' => now(),
        ]);
        try{
            $mail = new RegisterMail($user, $code);
            Mail::to($user->email)->later(10, $mail);
        }catch(\Exception $e){
            return;
        }
    }
}
