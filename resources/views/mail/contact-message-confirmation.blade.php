@extends('mail.layout')

@section('title', 'Pesan Anda sudah kami terima')
@section('preheader', 'Terima kasih sudah menghubungi kami, pesan Anda akan segera diproses.')
@section('heading', 'Pesan Anda sudah kami terima')

@section('content')
    <p>Halo, <strong>{{ $contactMessage->name }}</strong>.</p>
    <p>Terima kasih sudah menghubungi {{ config('app.name') }}. Pesan Anda dengan keperluan <strong>{{ $contactMessage->reason->label() }}</strong> sudah kami terima dan akan segera diproses. Balasan akan dikirim ke alamat email ini.</p>
    <p style="margin-bottom:8px;color:#5f6f86;font-size:13px;">Salinan pesan Anda:</p>
    <div class="quote" style="padding:12px 16px;margin:0 0 8px;">
        {!! \App\Services\HtmlSanitizer::clean($contactMessage->message) !!}
    </div>
@endsection

@section('footnote', 'Anda menerima email ini karena mengirim pesan lewat formulir kontak. Jika bukan Anda, abaikan saja email ini.')
