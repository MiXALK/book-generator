<?php

namespace App\Services;

readonly class StoryPaginator
{
    private const int MAX_PAGE_TEXT_LENGTH = 80;

    /**
     * @return list<string>
     */
    public function paginate(string $storyText): array
    {
        $normalized = $this->normalizeText($storyText);

        if ($normalized === '') {
            return [];
        }

        $sentences = $this->splitIntoSentences($normalized);
        $pages = $this->packSentencesIntoPages($sentences);

        return array_map(
            fn (string $page) => $this->limitSymbols($page),
            $pages,
        );
    }

    private function normalizeText(string $text): string
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return '';
        }

        return preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed;
    }

    /**
     * @return list<string>
     */
    private function splitIntoSentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?…])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if ($parts === false || $parts === []) {
            return [$text];
        }

        $sentences = [];

        foreach ($parts as $part) {
            $sentence = trim($part);

            if ($sentence !== '') {
                $sentences[] = $sentence;
            }
        }

        return $sentences === [] ? [$text] : $sentences;
    }

    /**
     * @param  list<string>  $sentences
     * @return list<string>
     */
    private function packSentencesIntoPages(array $sentences): array
    {
        $pages = [];
        $currentPage = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($sentence) > self::MAX_PAGE_TEXT_LENGTH) {
                if ($currentPage !== '') {
                    $pages[] = $currentPage;
                    $currentPage = '';
                }

                $pages = array_merge($pages, $this->splitOversizedSentence($sentence));

                continue;
            }

            $candidate = $currentPage === ''
                ? $sentence
                : $currentPage.' '.$sentence;

            if (mb_strlen($candidate) <= self::MAX_PAGE_TEXT_LENGTH) {
                $currentPage = $candidate;

                continue;
            }

            $pages[] = $currentPage;
            $currentPage = $sentence;
        }

        if ($currentPage !== '') {
            $pages[] = $currentPage;
        }

        return $pages;
    }

    /**
     * @return list<string>
     */
    private function splitOversizedSentence(string $sentence): array
    {
        $chunks = [];
        $remaining = $sentence;

        while (mb_strlen($remaining) > self::MAX_PAGE_TEXT_LENGTH) {
            $chunk = $this->limitSymbols($remaining);
            $chunks[] = $chunk;
            $remaining = ltrim(mb_substr($remaining, mb_strlen($chunk)));
        }

        if ($remaining !== '') {
            $chunks[] = $remaining;
        }

        return $chunks;
    }

    private function limitSymbols(string $value): string
    {
        $max = self::MAX_PAGE_TEXT_LENGTH;

        if (mb_strlen($value) <= $max) {
            return $value;
        }

        $chunk = mb_substr($value, 0, $max);
        $sentenceEnd = $this->findLastSentenceEndPosition($chunk);

        if ($sentenceEnd !== null) {
            return rtrim(mb_substr($value, 0, $sentenceEnd + 1));
        }

        $lastSpace = mb_strrpos($chunk, ' ');
        if ($lastSpace !== false && $lastSpace > 0) {
            return rtrim(mb_substr($value, 0, $lastSpace));
        }

        return rtrim($chunk);
    }

    private function findLastSentenceEndPosition(string $text): ?int
    {
        $lastPosition = null;
        $length = mb_strlen($text);

        for ($index = $length - 1; $index >= 0; $index--) {
            $character = mb_substr($text, $index, 1);

            if (in_array($character, ['.', '!', '?', '…'], true)) {
                $lastPosition = $index;
                break;
            }
        }

        return $lastPosition;
    }
}
