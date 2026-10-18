{!! __('mail.hello_text', ['name' => $name]) !!}

{!! __('mail.reset.intro_text') !!}
{!! $url !!}

{!! __('mail.reset.expiry_text', ['minutes' => $expires]) !!}

@include('mail.text.partials.confidential')
