@extends('layouts.mail')

@section('content')

	<div class="title">Reset Password</div>
	<p><b>Hello!</b>, You are receiving this email because we received a password reset request for your account.</p>
	<a href="{{env('RESET_LINK')}}{{$user->password_reset_token}}" class="btn">Reset Password</a>
	<p class="mb-20">If you did not request a password request, no further action is required.</p>
	<p class="mb-0">Thank you,</p>
	<p class="mt-0">The <a href="{{ config('app.frontend_url_email') }}"><img class="team" src="{{ url('/') }}/images/kryptonia-word-logo.png" width="auto" height="16" alt="{{ config('app.name') }}"></a> Team</p>
	<div class="hr"></div>
	<p class="mb-0">If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:</p>
	<a href="{{env('RESET_LINK')}}{{$user->password_reset_token}}">{{env('RESET_LINK')}}{{$user->password_reset_token}}</a>
@endsection
