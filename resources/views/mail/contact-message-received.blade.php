@extends('mail.layout')

@section('title', 'Pesan baru dari formulir kontak')
@section('preheader', "{$contactMessage->name} mengirim pesan: {$contactMessage->reason->label()}")
@section('heading', 'Pesan baru dari formulir kontak')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 18px;">
        <tr><td class="row-label">Nama</td><td class="row-value">{{ $contactMessage->name }}</td></tr>
        <tr><td class="row-label">Email</td><td class="row-value"><a href="mailto:{{ $contactMessage->email }}" style="color:#15803d;">{{ $contactMessage->email }}</a></td></tr>
        <tr><td class="row-label">Keperluan</td><td class="row-value">{{ $contactMessage->reason->label() }}</td></tr>
        <tr><td class="row-label">Dikirim</td><td class="row-value">{{ $contactMessage->created_at?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('d F Y, H:i') }} WIB</td></tr>
    </table>
    <p style="margin-bottom:8px;color:#5f6f86;font-size:13px;">Isi pesan:</p>
    <div class="quote" style="padding:12px 16px;margin:0 0 22px;">
        {!! \App\Services\HtmlSanitizer::clean($contactMessage->message) !!}
    </div>
    @include('mail.partials.button', ['url' => route('admin.contact-messages.index'), 'label' => 'Buka Kotak Masuk'])
@endsection

@section('footnote', 'Anda menerima email ini sebagai penerima notifikasi formulir kontak. Membalas email ini akan langsung menuju pengirim.')
