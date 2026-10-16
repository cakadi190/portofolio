{!! __('mail.hello_text', ['name' => $contactMessage->name]) !!}

{!! __('mail.confirmation.body_text', ['site' => config('app.name'), 'reason' => $contactMessage->reason->label()]) !!}

{!! __('mail.confirmation.copy') !!}
{!! \App\Services\PlainTextConverter::fromHtml($contactMessage->message) !!}

--
{!! config('app.name') !!} — {!! config('app.url') !!}
