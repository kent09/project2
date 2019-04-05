@extends('layouts.mail')

@section('content')

	<div class="title">Kryptonia-withdrawal</div>
	<p class="mb-20">Hello, <b class="name">{{ $user->name }}</b>! You receive this email because you requested to withdraw your coins. Click the button below to complete the process.</p>
	<a href="{{ url('/') }}/confirm-withdrawal/{{$email_withdrawal->key}}" class="btn">Verify Withdrawal</a>
	<div class="hr"></div>
	<p>Balance: <b>{{ $withdrawal->balance }} SUP</b></p>
	<p class="mb-20">Receiver Address: <b>{{ $withdrawal->recaddress }}</b></p>
	<p>if you are unable to access the button above, you may click the link below or copy and paste it in the address bar of your browser</p>
	<a href="{{ url('/') }}/confirm-withdrawal/{{$email_withdrawal->key}}" class="mb-20">{{ url('/') }}/confirm-withdrawal/{{$email_withdrawal->key}}</a> 
	<p class="mb-0">Thank you,<p>
	<p class="pb-20">The <a href="{{ config('app.frontend_url_email') }}"><img class="team" src="{{ url('/') }}/images/kryptonia-word-logo.png" width="auto" height="16" alt="{{ config('app.name') }}"></a> Team</p>

@endsection


