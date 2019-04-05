@component('mail::message')

@extends('layouts.mail')

@section('content')

	<div class="title">Kryptonia Membership Earning Withdrawal</div>
    <p class="mb-20">Hello, <b class="name">{{ $withdrawal->user_info['name'] }}</b>! <br>
    You receive this email because you requested to withdraw your coins. Click the button below to complete the process.</p>

	<a href="{{ url('/') }}/confirm-earnings-withdrawal/{{ $withdrawal->email_token }}" class="btn">Confirm Withdrawal</a>
    <div class="hr"></div>
    
    <p>Balance: <b>{{ $withdrawal->amount }} {{ $withdrawal->type === 'paypal' ? 'USD' : 'BTC' }}</b></p>
    @if ($withdrawal->type === 'paypal')
	    <p class="mb-20">Receiver Paypal Email: <b>{{ $withdrawal->paypal_email }}</b></p>
    @else
	    <p class="mb-20">Receiver BTC Address: <b>{{ $withdrawal->btc_address }}</b></p>
    @endif

	<p>if you are unable to access the button above, you may click the link below or copy and paste it in the address bar of your browser</p>
	<a href="{{ url('/') }}/confirm-earnings-withdrawal/{{ $withdrawal->email_token }}" class="mb-20">{{ url('/') }}/confirm-earnings-withdrawal/{{ $withdrawal->email_token }}</a> 
	<p class="mb-0">Thank you,<p>
	<p class="pb-20">The <a href="{{ config('app.frontend_url_email') }}"><img class="team" src="{{ url('/') }}/images/kryptonia-word-logo.png" width="auto" height="16" alt="{{ config('app.name') }}"></a> Team</p>

@endsection

@endcomponent
