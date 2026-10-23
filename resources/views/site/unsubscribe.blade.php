@extends('site.layout')
@section('content')<div class="panel"><h2>Make yourself comfortable.</h2><p>Confirm below to stop marketing emails from {{ $business->name }}.</p><form method="post">@csrf<button class="btn">Unsubscribe from marketing</button></form></div>@endsection
