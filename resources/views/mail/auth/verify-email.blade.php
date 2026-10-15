@extends('mail.layout')

@section('title', 'Verifikasi Alamat Email')
@section('preheader', 'Konfirmasi alamat email akun Anda.')
@section('heading', 'Verifikasi Alamat Email')

@section('content')
    <p>Halo, <strong>{{ $name }}</strong>.</p>
    <p>Terima kasih sudah mendaftar. Klik tombol di bawah ini untuk memverifikasi alamat email Anda:</p>
    @include('mail.partials.button', ['url' => $url, 'label' => 'Verifikasi Email'])
    <p>Tautan ini berlaku selama <strong>{{ $expires }} menit</strong>. Jika Anda tidak membuat akun, abaikan email ini.</p>
    @include('mail.partials.fallback-link', ['url' => $url])
@endsection
