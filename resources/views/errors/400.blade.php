@extends('layouts.error')

@section('content')

<div class="row justify-content-center align-items-center h-100">
    <div class="col col-md-4">
        <div class="card bg-warning text-white">
            <div class="card-header">
                <b>Error: 400</b>
            </div>
            <div class="card-body">
                <div class="row justify-content-center">
                    <center>
                        <i class="fa fa-warning fa-5x" aria-hidden="true"></i> <hr>
                        <b>{{ $message }}</b>
                    </center>
                </div>
            </div>
            <div class="card-footer">
                <small class="float-right">
                    <a href="{{ config('app.frontend_url_email') }}/{{ $redirect_uri }}" class="btn btn-link btn-sm text-white">Continue to {{ config('app.name') }} <i class="fa fa-caret-right"></i></a>
                </small>
            </div>
        </div>
    </div>
</div>

@endsection