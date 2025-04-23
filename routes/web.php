<?php

use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/send-email', function () {
    $name = "John Doe"; // Replace with dynamic data if needed
    Mail::to('wokodavid001@gmail.com')->send(new WelcomeEmail($name));
    return "Email sent successfully!";
});