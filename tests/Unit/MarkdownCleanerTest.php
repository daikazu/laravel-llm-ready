<?php

declare(strict_types=1);

use Daikazu\LaravelLlmReady\Support\MarkdownCleaner;

it('removes excessive blank lines', function () {
    $cleaner = new MarkdownCleaner;

    $input = "# Title\n\n\n\n\nParagraph";

    $result = $cleaner->clean($input);

    expect($result)->toBe("# Title\n\nParagraph\n");
});

it('removes trailing whitespace from lines', function () {
    $cleaner = new MarkdownCleaner;

    $input = "# Title   \n\nParagraph  ";

    $result = $cleaner->clean($input);

    expect($result)->toBe("# Title\n\nParagraph\n");
});

it('normalizes line endings', function () {
    $cleaner = new MarkdownCleaner;

    $input = "Line 1\r\nLine 2\rLine 3";

    $result = $cleaner->clean($input);

    expect($result)->toBe("Line 1\nLine 2\nLine 3\n");
});

it('ensures single trailing newline', function () {
    $cleaner = new MarkdownCleaner;

    $input = 'Content';

    $result = $cleaner->clean($input);

    expect($result)->toBe("Content\n");
});

it('removes control characters', function () {
    $cleaner = new MarkdownCleaner;

    $input = "Content\x00with\x08control\x1Fchars";

    $result = $cleaner->clean($input);

    expect($result)->toBe("Contentwithcontrolchars\n");
});
