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

        return $pages;
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

                $pages[] = $sentence;

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
}
