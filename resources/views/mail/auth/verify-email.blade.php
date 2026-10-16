@extends('mail.layout')

@section('title', __('mail.verify.subject'))
@section('preheader', __('mail.verify.preheader'))
@section('heading', __('mail.verify.subject'))

@section('content')
    <p>{!! __('mail.hello', ['name' => e($name)]) !!}</p>
    <p>{{ __('mail.verify.intro') }}</p>
    @include('mail.partials.button', ['url' => $url, 'label' => __('mail.verify.button')])
    <p>{!! __('mail.verify.expiry', ['minutes' => e($expires)]) !!}</p>
    @include('mail.partials.fallback-link', ['url' => $url])
@endsection
