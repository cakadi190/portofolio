<?php

it('does not force the offcanvas to display:none, which would suppress its slide-in transition', function (): void {
    $html = $this->get('/')->getContent();

    expect($html)
        ->not->toContain('.offcanvas:not(.show)')
        ->toContain('.modal:not(.show)');
});
