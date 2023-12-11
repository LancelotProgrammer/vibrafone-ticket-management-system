<?php

use App\Http\Controllers\HomePageController;
use App\Http\Controllers\PDFController;
use App\Mail\TicketWorkOrder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [HomePageController::class, 'getHomePage']);
Route::get('/contact', [HomePageController::class, 'getContactPage']);
Route::post('/contact', [HomePageController::class, 'createContact']);
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');
Route::get('tickets/{id}/pdf', PDFController::class)->name('ticket.pdf');

if (App::environment('local')) {
    Route::get('/mailable', function () {
        return new TicketWorkOrder([
            'email_title' => 'test',
            'email_body' => 'hello and welcome to our channel hello and welcome to our channel hello and welcome to our channel hello and welcome to our channel hello and welcome to our channel ',
            'from' => 'test',
            'cc' => 'test',
            'to' => 'test',
        ]);
    });
}
