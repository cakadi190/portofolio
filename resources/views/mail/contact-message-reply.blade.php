@extends('mail.layout')

@section('title', $reply->subject)
@section('preheader', \Illuminate\Support\Str::limit(\App\Services\PlainTextConverter::fromHtml($reply->body), 120))
@section('heading', $reply->subject)

@section('content')
    <p>Halo, <strong>{{ $reply->contactMessage->name }}</strong>.</p>
    {!! \App\Services\HtmlSanitizer::clean($reply->body) !!}
    @if ($attachmentNames !== [])
        <p style="margin:0 0 6px;color:#5f6f86;font-size:13px;">Lampiran:</p>
        <ul style="margin:0 0 20px;padding-left:20px;font-size:13px;">
            @foreach ($attachmentNames as $attachmentName)
                <li>{{ $attachmentName }}</li>
            @endforeach
        </ul>
    @endif
    <hr style="border:0;border-top:1px solid #e3e8ef;margin:24px 0 16px;">
    <p style="margin-bottom:8px;color:#5f6f86;font-size:13px;">Pesan Anda sebelumnya ({{ $reply->contactMessage->reason->label() }}):</p>
    <div class="quote" style="padding:12px 16px;margin:0;color:#5f6f86;">
        {!! \App\Services\HtmlSanitizer::clean($reply->contactMessage->message) !!}
    </div>
@endsection

@section('footnote', 'Anda menerima email ini sebagai balasan atas pesan yang Anda kirim lewat formulir kontak. Anda dapat membalas email ini langsung.')
