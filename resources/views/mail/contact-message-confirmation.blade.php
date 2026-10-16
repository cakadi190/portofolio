@extends('mail.layout')

@section('title', __('mail.confirmation.subject'))
@section('preheader', __('mail.confirmation.preheader'))
@section('heading', __('mail.confirmation.subject'))

@section('content')
    <p>{!! __('mail.hello', ['name' => e($contactMessage->name)]) !!}</p>
    <p>{!! __('mail.confirmation.body', ['site' => e(config('app.name')), 'reason' => e($contactMessage->reason->label())]) !!}</p>
    <p style="margin-bottom:8px;color:#5f6f86;font-size:13px;">{{ __('mail.confirmation.copy') }}</p>
    <div class="quote" style="background:#f3f5f8;border-radius:12px;padding:14px 18px;margin:0 0 8px;">
        {!! \App\Services\EmailHtml::format($contactMessage->message) !!}
    </div>
@endsection

@section('footnote', __('mail.confirmation.footnote'))
