Pesan baru dari formulir kontak

Nama      : {!! $contactMessage->name !!}
Email     : {!! $contactMessage->email !!}
Keperluan : {!! $contactMessage->reason->label() !!}

{!! \App\Services\PlainTextConverter::fromHtml($contactMessage->message) !!}

Buka kotak masuk: {!! route('admin.contact-messages.index') !!}
