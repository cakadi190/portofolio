{!! __('mail.hello_text', ['name' => $name]) !!}

{!! __('mail.verify.intro_text') !!}
{!! $url !!}

{!! __('mail.verify.expiry_text', ['minutes' => $expires]) !!}

@include('mail.text.partials.confidential')
