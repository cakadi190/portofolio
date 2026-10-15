<?php

namespace App\Services;

use App\Models\Note;
use DOMDocument;
use DOMElement;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\Yaml\Yaml;

/**
 * Turns a published note into Markdown with a YAML front-matter header, meant to be fed to AI tools.
 */
class NoteMarkdownExporter
{
    public function export(Note $note): string
    {
        $note->loadMissing('tags:id,name', 'author:id,name');

        $header = array_filter([
            'title' => $note->title,
            'url' => route('notes.show', $note),
            'author' => $note->author?->name,
            'published_at' => $note->published_at?->toAtomString(),
            'updated_at' => $note->updated_at?->toAtomString(),
            'tags' => $note->tags->pluck('name')->all(),
            'excerpt' => $note->excerpt,
        ], fn (mixed $value): bool => $value !== null && $value !== []);

        $yaml = Yaml::dump($header, 2, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        return "---\n{$yaml}---\n\n".$this->toMarkdown($note->content);
    }

    /**
     * Convert editor HTML to Markdown. The HTML is sanitized first, figures become
     * an image followed by an italic caption, and relative image URLs become absolute.
     */
    public function toMarkdown(?string $html): string
    {
        $clean = HtmlSanitizer::clean($html);

        if ($clean === '') {
            return '';
        }

        $converter = new HtmlConverter([
            'strip_tags' => true,
            'hard_break' => true,
            'header_style' => 'atx',
        ]);
        $converter->getEnvironment()->addConverter(new TableConverter);

        return trim($converter->convert($this->simplifyBlocks($clean)))."\n";
    }

    private function simplifyBlocks(string $html): string
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="utf-8" ?><body>'.$html.'</body>', LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach (iterator_to_array($document->getElementsByTagName('img')) as $image) {
            $source = $image->getAttribute('src');

            if (str_starts_with($source, '/') && ! str_starts_with($source, '//')) {
                $image->setAttribute('src', url($source));
            }
        }

        foreach (iterator_to_array($document->getElementsByTagName('figcaption')) as $caption) {
            $text = trim($caption->textContent);
            $credit = trim($caption->getAttribute('data-credit'));
            $line = trim($text.($credit !== '' ? " ({$credit})" : ''));

            $paragraph = $document->createElement('p');

            if ($line !== '') {
                $emphasis = $document->createElement('em');
                $emphasis->appendChild($document->createTextNode($line));
                $paragraph->appendChild($emphasis);
            }

            $caption->parentNode?->replaceChild($paragraph, $caption);
        }

        foreach (iterator_to_array($document->getElementsByTagName('figure')) as $figure) {
            foreach (iterator_to_array($figure->getElementsByTagName('img')) as $image) {
                $wrapper = $document->createElement('p');
                $figure->replaceChild($wrapper, $image);
                $wrapper->appendChild($image);
            }

            $this->unwrap($figure);
        }

        $body = $document->getElementsByTagName('body')->item(0);
        $output = '';

        foreach ($body?->childNodes ?? [] as $child) {
            $output .= $document->saveHTML($child)."\n";
        }

        return $output;
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        while ($element->firstChild !== null) {
            $parent?->insertBefore($element->firstChild, $element);
        }

        $parent?->removeChild($element);
    }
}
