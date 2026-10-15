@extends('mail.layout')

@section('title', 'Reset Password')
@section('preheader', 'Permintaan reset password untuk akun Anda.')
@section('heading', 'Reset Password')

@section('content')
    <p>Halo, <strong>{{ $name }}</strong>.</p>
    <p>Kami menerima permintaan untuk mereset password akun Anda. Klik tombol di bawah ini untuk membuat password baru:</p>
    @include('mail.partials.button', ['url' => $url, 'label' => 'Reset Password'])
    <p>Tautan ini berlaku selama <strong>{{ $expires }} menit</strong>. Jika Anda tidak meminta reset password, abaikan email ini dan password Anda tidak akan berubah.</p>
    @include('mail.partials.fallback-link', ['url' => $url])
@endsection
