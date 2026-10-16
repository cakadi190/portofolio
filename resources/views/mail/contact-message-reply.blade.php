@extends('mail.layout')

@section('title', $reply->subject)
@section('preheader', \Illuminate\Support\Str::limit(\App\Services\PlainTextConverter::fromHtml($reply->body), 120))
@section('heading', $reply->subject)

@section('content')
    <p>{!! __('mail.hello', ['name' => e($reply->contactMessage->name)]) !!}</p>
    <div style="margin:0 0 16px;">{!! \App\Services\EmailHtml::format($reply->body) !!}</div>
    @if ($attachmentNames !== [])
        <p style="margin:0 0 8px;">{{ __('mail.reply.attachments') }}</p>
        <ul style="margin:0 0 20px;padding-left:20px;font-size:13px;color:#5f6f86;">
            @foreach ($attachmentNames as $attachmentName)
                <li>{{ $attachmentName }}</li>
            @endforeach
        </ul>
    @endif
    <hr style="border:0;border-top:1px solid #e3e8ef;margin:24px 0 16px;">
    <p style="margin-bottom:8px;color:#5f6f86;font-size:13px;">{{ __('mail.reply.previous', ['reason' => $reply->contactMessage->reason->label()]) }}</p>
    <div class="quote" style="background:#f3f5f8;border-radius:12px;padding:14px 18px;margin:0;color:#5f6f86;">
        {!! \App\Services\EmailHtml::format($reply->contactMessage->message) !!}
    </div>
@endsection

@section('footnote', __('mail.reply.footnote'))
