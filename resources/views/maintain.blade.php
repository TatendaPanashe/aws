@extends('layouts.main')

@section('title')
Welcome
@endsection

@section('content')
@include('includes.header')

<body style="font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f4f4f4;">

    <div style="text-align: center; padding: 20px; border: 1px solid #555; border-radius: 8px; background-color: #333; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);">
        <h1 style="color: #eee; margin-bottom: 20px;">Site Under Construction</h1>
        <p style="color: #ccc; margin-bottom: 30px;">Something awesome is brewing! We're working hard behind the scenes to deliver an experience you'll love. In the meantime, your daily collections and face value records are vital, so keep them flowing. Plus, why not take the system for a spin and see what's cooking? We'll be back online with the main course shortly!</p>
        <br>
        <p style="color: #ccc;">We expect to be back online soon.</p>
        <p style="color: #ccc;">Thank you for your patience!</p>
        <p style="color: #ccc;">&copy; 2025 Kodomo Technologies</p>
    </div>

</body>
@endsection
