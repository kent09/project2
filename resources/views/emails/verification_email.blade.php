@extends('layouts.mail')

@section('content')

		<div class="title">Verify your account</div>
		<p>Welcome to Kryptonia, <b class="name">{{ $user->name }}</b>! You receive this email because your account needs to verify your email address.</p>
		<a href="{{ env('APP_URL') }}/auth/verify-email/{{$user->email_token}}" class="btn">Verify Email</a>
		<div class="hr"></div>
		<p class="mb-0">Or paste this link into your browser:</p>
		<a href="{{ env('APP_URL') }}/auth/verify-email/{{$user->email_token}}" class="link">{{ env('APP_URL')}}/auth/verify-email/{{$user->email_token}}</a>
		<p class="mb-0">Thank you,</p>
		<p class="mt-0">The <a href="{{ env('APP_URL') }}"><img class="team" src="{{ url('/') }}/images/kryptonia-word-logo.png" width="auto" height="16" alt="{{ config('app.name') }}"></a> Team</p>
		
@endsection