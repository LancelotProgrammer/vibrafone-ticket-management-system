<?php

use App\Http\Controllers\HomePageController;
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
Route::get('/contact', function () {
    return redirect('/admin/login');
})->name('login');

Route::get('/mailable', function () {
    return new App\Mail\TicketWorkOrder([
        'email_title' => 'test',
        'email_body' => 'hello and welcome to our channel hello and welcome to our channel hello and welcome to our channel hello and welcome to our channel hello and welcome to our channel ',
        'from' => 'test',
        'cc' => 'test',
        'to' => 'test',
    ]);
});
