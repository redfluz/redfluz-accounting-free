@extends('layouts.app')

@section('content')
    @include('finance.discounts._form', [
        'formAction' => route('finance.discounts.update', $discount),
        'formMethod' => 'PUT',
        'formTitle' => __('discounts.form.edit_title'),
    ])
@endsection
