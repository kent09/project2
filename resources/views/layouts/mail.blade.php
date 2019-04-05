<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="public">
    <title>Mail</title>

</head>
<link href="https://fonts.googleapis.com/css?family=Roboto:400,400i,700,700i" rel="stylesheet">
<body class="container">
<style type="text/css">
body {
    padding-top: 50px;
}
.panel-warning {
	border-color: #D4AF37 !important;
}
.panel-warning > .panel-heading {
	color: #fff !important;
	background-color: #D4AF37 !important;
	border-color: #D4AF37 !important;
}
.docker {
	border: 1px solid #ccc;
	padding: 15px;
	box-shadow: 5px 4px 4px #eee;
}
td {
    padding: 5px !important;
    border: 1px solid #eee;
}
.container {
	text-align: center;
	font-size: 16px;
	color: #767676;
	font-family: 'Roboto', sans-serif;
}
.header {
	background-color: #f1f3f8; 
	border-bottom: 6px solid #4268b3; 
	padding-bottom: 20px;
	padding-top: 40px; 
}
.name {
	text-transform: capitalize;
}
.btn {
	text-decoration: none;
	padding: 15px 40px;
	color: #ffffff;
	background-color: #4268b3;
	border-radius: 4px;
	display: inline-block;
}
b, strong {
	color: #1d2d35;
}
.title {
	font-size: 48px;
	font-weight: bold;
	color: #1d2d35;
	margin: 30px 0 25px;
}
.hr {
	border-top: 1px solid #dfdfdf;
	margin: 40px 0;
}
.link {
	margin-bottom: 25px;
}
.get-touch {
	font-size: 18px;
}
.footer {
	background-color: #f1f3f8;
	border-top: 1px solid #dfdfdf;
	padding-bottom: 50px;   
}
ul {
	padding: 0;
	margin-bottom: 50px;
}
ul li {
	list-style: none;
	display: inline-block;
	margin-right: 20px;
}
.note {
	color: #c4c4c4;
}
i {
	color: #747577;
}
.mb-0 {
	margin-bottom: 0;
}
.mb-20 {
	margin-bottom: 20px;
}
.pb-20 {
	padding-bottom: 20px;
}
.mt-0 {
	margin-top: 0;
}
.team {
	margin-bottom: -2px;
}
</style>
<!-- ====================================================== -->
	<div class="container">
		<div class="header">
			<a href="{{ config('app.frontend_url_email') }}">
				<img src="{{ url('/') }}/images/k.svg" width="auto" height="90" alt="{{ config('app.name') }}">
			</a>
		</div>
		<div class="body">

			@yield('content')

			<div class="hr"></div>
			<div class="get-touch"><b>Get in touch with us.</b></div>
			<ul>
				<li>
					<a href="https://www.facebook.com/Kryptoniaio/">
						<img src="{{ url('/') }}/images/fb.png" width="35" height="35" alt="facebook">
					</a>
				</li>
				<li>
					<a href="https://www.instagram.com/kryptonia.io/">
						<img src="{{ url('/') }}/images/instagram.png" alt="instagram" width="35" height="35">
					</a>
				</li>
				<li>
					<a href="https://twitter.com/KRYPT0N1A">
						<img src="{{ url('/') }}/images/twitter.png" alt="twitter" width="35" height="35">
					</a>
				</li>
				<li>
					<a href="https://discord.gg/GTQNzZa">
						<img src="{{ url('/') }}/images/discord.svg" alt="slack" width="35" height="35">
					</a>
				</li>
				<li>
					<a href="https://t.me/superiorcoin">
						<img src="{{ url('/') }}/images/telegram.png" alt="telegram" width="35" height="35">
					</a>
				</li>
				<li>
					<a href="https://steemit.com/@kryptonia">
						<img src="{{ url('/') }}/images/steemit.png" alt="steemit" width="35" height="35">
					</a>
				</li>
			</ul>
		</div>
		<div class="footer">
			<p class="note">This is a system-generated email. Please do not reply.</p>
			<p><strong><i>Copyright &copy; 2016-2018 Kryptonia.io. All rights reserved.</i></strong></p>
			<p><strong><i>Enjoy the rest of your day!</i></strong></p>
			<a href="{{ config('app.frontend_url_email') }}"><img src="{{ url('/') }}/images/k-footer.png" width="auto" height="35" alt="{{ config('app.name') }}"></a>
		</div>
	</div>

<!-- ====================================================== -->
</body>
</html>