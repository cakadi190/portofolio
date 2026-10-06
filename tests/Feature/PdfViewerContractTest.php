<?php

/**
 * No JS test runner is installed, so these guard the viewer's structural contracts at source level.
 */
function pdfViewerSource(): string
{
    return file_get_contents(resource_path('js/components/ui/pdf-canvas-viewer.svelte'));
}

it('observes thumbnails in an effect separate from the document loader so toggling the sidebar does not reload the PDF', function () {
    $source = pdfViewerSource();

    $loader = substr($source, strpos($source, 'const source = url;'), strpos($source, 'Separate from the document loader') - strpos($source, 'const source = url;'));

    expect($loader)
        ->toContain('getDocument')
        ->not->toContain('thumbList')
        ->not->toContain('thumbObserver');
    expect($source)->toContain('const rail = thumbList;');
});

it('collapses toolbar actions into one animated dropdown on small screens', function () {
    $source = pdfViewerSource();

    expect($source)
        ->toContain("menu === 'mobile'")
        ->toContain('transition:pop')
        ->toContain('.pdf-desktop')
        ->toContain('max-width: 639.98px');
});

it('supports two-finger pinch zoom on touch screens', function () {
    $source = pdfViewerSource();

    expect($source)
        ->toContain("event.pointerType === 'touch'")
        ->toContain('applyPinch')
        ->toContain('touch-action: pan-x pan-y');
});

it('uses bootstrap form controls and searches annotations in the search sidebar', function () {
    $source = pdfViewerSource();

    expect($source)
        ->toContain('class="form-check-input" type="checkbox"')
        ->toContain('class="form-control"')
        ->toContain('data-bs-theme="dark"')
        ->toContain('getAnnotations()')
        ->toContain("source: 'annotation'");
});
