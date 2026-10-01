<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Portfolio Fadhil, pelajar Rekayasa Perangkat Lunak dari Surabaya.">
        <title>Fadhil | Portfolio</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="m-0 font-sans antialiased">
        <div id="app"></div>
    </body>
</html>
