@extends('mail.layout')

@section('title', __('mail.reset.subject'))
@section('preheader', __('mail.reset.preheader'))
@section('heading', __('mail.reset.subject'))

@section('content')
    <p>{!! __('mail.hello', ['name' => e($name)]) !!}</p>
    <p>{{ __('mail.reset.intro') }}</p>
    @include('mail.partials.button', ['url' => $url, 'label' => __('mail.reset.button')])
    <p>{!! __('mail.reset.expiry', ['minutes' => e($expires)]) !!}</p>
    @include('mail.partials.fallback-link', ['url' => $url])
@endsection
