@extends('layouts.mail')

@section('content')

	
	<div class="title">Referral Signed up!</div>
	<p class="mb-20">Hello, <b class="name">{{ $referrer->name }}</b>! You received this email because your referral code was successfully used by {{ $user->name }} for signup.</p>
	<p class="mb-0">Thank you,<p>
	<p class="pb-20">The <a href="{{ config('app.frontend_url_email') }}"><img class="team" src="{{ url('/') }}/images/kryptonia-word-logo.png" width="auto" height="16" alt="{{ config('app.name') }}"></a> Team</p>


@endsection