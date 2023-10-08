<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class HomePageController extends Controller
{
    public function getHomePage()
    {
        return view('layouts.home');
    }

    public function getContactPage()
    {
        return view('layouts.contact');
    }

    public function createContact(Request $request)
    {
        if (RateLimiter::tooManyAttempts($request->ip(), 5)) {
            return back()->with('status-error', 'Your contact has not been sent!');
        }

        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'email' => 'required|email',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return back()->with('status-error', 'Your contact has not been sent!');
        }

        Contact::create([
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'email' => $request->email,
            'feedback' => $request->description,
        ]);

        RateLimiter::hit($request->ip(), 60*60*24);

        return back()->with('status-success', 'Your contact has been sent!');
    }
}
