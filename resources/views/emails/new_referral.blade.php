@extends('layouts.mail')

@section('content')

<div class="container">
	<div class="panel panel-warning">
		<div class="panel-heading">
			<h3 class="panel-title">New referral sign up  - {{ config('app.name') }}</h3>
		</div>
		<div class="panel-body">
			<div class="docker">
				<h4>Hello <b>{{ $referrer->name }}</b>,</h4>

				<h2 class="text-center">
					<span class="text-success">Referral signed up!</span> <br>
					<small>You received this email because your referral code was successfully used by {{ $user->name }} for signup.</small>
				</h2>

				<br>
				<p><i>Thank you</i></p>
				<p><i>This is a system-generated e-mail. Please do not reply </i></p>
				<p><a href="{{ url('/') }}">Team {{ config('app.name') }}</a></p>
				<div class="clearfix"></div>
			</div>
		</div>
	</div>
</div>

@endsection