<!doctype html>
<html lang="id">
<body style="font-family: Arial, sans-serif; color: #212529; line-height: 1.6;">
    <h2>Pesan baru dari formulir kontak</h2>
    <p>
        <strong>Nama:</strong> {{ $contactMessage->name }}<br>
        <strong>Email:</strong> {{ $contactMessage->email }}<br>
        <strong>Keperluan:</strong> {{ $contactMessage->reason->label() }}
    </p>
    <hr>
    {!! $contactMessage->message !!}
</body>
</html>
