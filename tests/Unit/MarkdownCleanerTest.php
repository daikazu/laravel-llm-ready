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

it('fixes inline headings with short eyebrow text', function () {
    $cleaner = new MarkdownCleaner;

    $input = 'Pricing ### Flexible pricing plan';

    $result = $cleaner->clean($input);

    expect($result)->toContain('**Pricing**');
    expect($result)->toContain('Flexible pricing plan');
});

it('fixes inline headings with uppercase eyebrow', function () {
    $cleaner = new MarkdownCleaner;

    $input = 'FEATURES ## Our Features';

    $result = $cleaner->clean($input);

    expect($result)->toContain('*FEATURES*');
    expect($result)->toContain('Our Features');
});

it('fixes inline headings with long prefix text', function () {
    $cleaner = new MarkdownCleaner;

    $input = 'This is a long sentence that describes something. ## Heading';

    $result = $cleaner->clean($input);

    expect($result)->toContain('This is a long sentence that describes something.');
    expect($result)->toContain('Heading');
});

it('fixes link formatting with extra spaces', function () {
    $cleaner = new MarkdownCleaner;

    $input = '[ Link Text ](#url)';

    $result = $cleaner->clean($input);

    expect($result)->toContain('[Link Text](#url)');
});

it('fixes space between link brackets and parentheses', function () {
    $cleaner = new MarkdownCleaner;

    $input = '[Link] (#url)';

    $result = $cleaner->clean($input);

    expect($result)->toContain('[Link](#url)');
});

it('fixes spaces inside url parentheses', function () {
    $cleaner = new MarkdownCleaner;

    $input = '[Link]( #url )';

    $result = $cleaner->clean($input);

    expect($result)->toContain('[Link](#url)');
});

it('fixes excessive emphasis on short text', function () {
    $cleaner = new MarkdownCleaner;

    $input = '***Short Label***';

    $result = $cleaner->clean($input);

    expect($result)->toContain('*Short Label*');
    expect($result)->not->toContain('***');
});

it('preserves excessive emphasis on long text', function () {
    $cleaner = new MarkdownCleaner;

    $input = '***This is a much longer text that exceeds forty characters and should be kept***';

    $result = $cleaner->clean($input);

    expect($result)->toContain('***');
});

it('simplifies bold uppercase labels to italic', function () {
    $cleaner = new MarkdownCleaner;

    $input = "**FEATURED**\n\n# Title";

    $result = $cleaner->clean($input);

    expect($result)->toContain('*FEATURED*');
});

it('fixes price formatting with spaces', function () {
    $cleaner = new MarkdownCleaner;

    $input = 'Only $ 10 /mo for the basic plan';

    $result = $cleaner->clean($input);

    expect($result)->toContain('$10');
    expect($result)->toContain('/mo');
    expect($result)->not->toContain('$ 10');
    expect($result)->not->toContain(' /mo');
});

it('fixes double dollar signs in prices', function () {
    $cleaner = new MarkdownCleaner;

    $input = '$$10.99';

    $result = $cleaner->clean($input);

    expect($result)->toContain('$10.99');
    expect($result)->not->toContain('$$');
});

it('preserves code block content', function () {
    $cleaner = new MarkdownCleaner;

    $input = "# Title\n\n```\n  indented code\n  more code\n```\n\nParagraph";

    $result = $cleaner->clean($input);

    expect($result)->toContain('  indented code');
    expect($result)->toContain('  more code');
});

it('preserves list items', function () {
    $cleaner = new MarkdownCleaner;

    $input = "- Item 1\n- Item 2\n- Item 3";

    $result = $cleaner->clean($input);

    expect($result)->toContain('- Item 1');
    expect($result)->toContain('- Item 2');
    expect($result)->toContain('- Item 3');
});

it('preserves numbered list items', function () {
    $cleaner = new MarkdownCleaner;

    $input = "1. First\n2. Second\n3. Third";

    $result = $cleaner->clean($input);

    expect($result)->toContain('1. First');
    expect($result)->toContain('2. Second');
    expect($result)->toContain('3. Third');
});

it('normalizes heading spacing', function () {
    $cleaner = new MarkdownCleaner;

    // Use text on its own line (not inline with heading) to test spacing normalization
    $input = "Some text here.\n# Heading\nAnother paragraph";

    $result = $cleaner->clean($input);

    // Should have blank line before and after heading
    expect($result)->toContain("Some text here.\n\n# Heading\n\nAnother paragraph");
});

it('collapses multiple spaces into single space', function () {
    $cleaner = new MarkdownCleaner;

    $input = 'Word    with    many    spaces';

    $result = $cleaner->clean($input);

    expect($result)->toBe("Word with many spaces\n");
});
