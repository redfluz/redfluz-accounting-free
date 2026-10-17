@extends('layouts.app')

@section('content')
    @include('finance.discounts._form', [
        'formAction' => route('finance.discounts.store'),
        'formMethod' => 'POST',
        'formTitle' => __('discounts.form.create_title'),
    ])
@endsection
