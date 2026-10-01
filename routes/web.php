<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::view('/admin', 'welcome');

Route::post('/contact', function (Request $request): JsonResponse {
    $validated = $request->validate(
        [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'message' => ['required', 'string', 'max:2000'],
        ],
        [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Gunakan format email yang valid.',
            'message.required' => 'Pesan wajib diisi.',
        ],
    );

    return response()->json([
        'message' => 'Terima kasih, pesanmu sudah diterima di demo ini.',
        'name' => $validated['name'],
    ]);
});
