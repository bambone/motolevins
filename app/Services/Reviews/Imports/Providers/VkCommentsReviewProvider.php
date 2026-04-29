<?php

declare(strict_types=1);

namespace App\Services\Reviews\Imports\Providers;

use App\Models\ReviewImportSource;
use App\Services\Reviews\Imports\Contracts\ExternalReviewProvider;
use App\Services\Reviews\Imports\Dto\ExternalReviewItemDto;
use App\Services\Reviews\Imports\Dto\ReviewFetchOptions;
use App\Services\Reviews\Imports\Dto\ReviewFetchResult;
use App\Services\Reviews\Imports\Dto\ReviewProviderCapabilities;
use App\Services\Reviews\Imports\ExternalReviewSourceRef;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class VkCommentsReviewProvider implements ExternalReviewProvider
{
    public function providerKey(): string
    {
        return 'vk';
    }

    public function detect(string $url): bool
    {
        $u = mb_strtolower($url);

        return str_contains($u, 'vk.com') && (str_contains($u, 'topic-') || str_contains($u, 'wall'));
    }

    public function parseSourceUrl(string $url): ExternalReviewSourceRef
    {
        $norm = trim($url);
        if (preg_match('#topic-(\d+)_(\d+)#', $norm, $m)) {
            return new ExternalReviewSourceRef(
                normalizedUrl: $norm,
                externalOwnerId: $m[1],
                externalTopicId: $m[2],
            );
        }
        if (preg_match('#wall(-?\d+)_(\d+)#', $norm, $m)) {
            return new ExternalReviewSourceRef(
                normalizedUrl: $norm,
                externalOwnerId: $m[1],
                externalTopicId: $m[2],
            );
        }

        return new ExternalReviewSourceRef(normalizedUrl: $norm);
    }

    public function capabilities(): ReviewProviderCapabilities
    {
        return new ReviewProviderCapabilities(
            canFetchText: true,
            needsAuth: true,
            canFetchAvatar: true,
            canFetchRating: false,
            canFetchDate: true,
        );
    }

    public function fetchPreview(ReviewImportSource $source, ReviewFetchOptions $options): ReviewFetchResult
    {
        $token = (string) config('services.vk.access_token', '');
        if ($token === '') {
            return ReviewFetchResult::unavailable('needs_token', 'VK access token is not configured (services.vk.access_token).');
        }

        $ref = $this->parseSourceUrl($source->source_url);
        try {
            if ($source->provider === 'vk_topic' && $ref->externalOwnerId && $ref->externalTopicId) {
                return $this->fetchBoardComments($ref, $source, $options, $token);
            }
            if ($source->provider === 'vk_wall' && $ref->externalOwnerId && $ref->externalTopicId) {
                return $this->fetchWallComments($ref, $source, $options, $token);
            }
        } catch (Throwable $e) {
            report($e);

            return ReviewFetchResult::unavailable('fetch_failed', 'VK request failed.');
        }

        return ReviewFetchResult::unavailable('bad_url', 'Could not parse VK topic or wall URL.');
    }

    private function fetchBoardComments(ExternalReviewSourceRef $ref, ReviewImportSource $source, ReviewFetchOptions $options, string $token): ReviewFetchResult
    {
        $items = [];
        $fetched = 0;
        $offset = 0;
        $pages = 0;
        $groupId = (int) $ref->externalOwnerId;
        $topicId = (int) $ref->externalTopicId;

        while ($pages < $options->maxPages && count($items) < $options->maxPerRun) {
            $pages++;
            $resp = Http::timeout(20)->get('https://api.vk.com/method/board.getComments', [
                'access_token' => $token,
                'v' => '5.199',
                'group_id' => $groupId,
                'topic_id' => $topicId,
                'count' => min(100, $options->pageSize),
                'offset' => $offset,
                'extended' => 1,
                'need_likes' => 0,
                'sort' => 'desc',
            ]);
            if (! $resp->ok()) {
                return ReviewFetchResult::unavailable('http', 'VK HTTP error.');
            }
            $json = $resp->json();
            if (isset($json['error'])) {
                return $this->mapVkError($json['error']);
            }
            $chunk = $json['response']['items'] ?? [];
            $profiles = [];
            foreach ($json['response']['profiles'] ?? [] as $p) {
                $profiles[(int) ($p['id'] ?? 0)] = $p;
            }
            if ($chunk === []) {
                break;
            }
            foreach ($chunk as $row) {
                $fetched++;
                $dto = $this->mapComment($row, $profiles, $source, 'board');
                if ($dto !== null && mb_strlen(trim($dto->body)) >= $options->minTextLength) {
                    $items[] = $dto;
                }
            }
            $offset += count($chunk);
            if (count($chunk) < $options->pageSize) {
                break;
            }
        }

        return ReviewFetchResult::success($items, $fetched);
    }

    private function fetchWallComments(ExternalReviewSourceRef $ref, ReviewImportSource $source, ReviewFetchOptions $options, string $token): ReviewFetchResult
    {
        $items = [];
        $fetched = 0;
        $offset = 0;
        $pages = 0;
        $ownerId = (int) $ref->externalOwnerId;
        $postId = (int) $ref->externalTopicId;

        while ($pages < $options->maxPages && count($items) < $options->maxPerRun) {
            $pages++;
            $resp = Http::timeout(20)->get('https://api.vk.com/method/wall.getComments', [
                'access_token' => $token,
                'v' => '5.199',
                'owner_id' => $ownerId,
                'post_id' => $postId,
                'count' => min(100, $options->pageSize),
                'offset' => $offset,
                'extended' => 1,
                'need_likes' => 0,
                'sort' => 'desc',
            ]);
            if (! $resp->ok()) {
                return ReviewFetchResult::unavailable('http', 'VK HTTP error.');
            }
            $json = $resp->json();
            if (isset($json['error'])) {
                return $this->mapVkError($json['error']);
            }
            $chunk = $json['response']['items'] ?? [];
            $profiles = [];
            foreach ($json['response']['profiles'] ?? [] as $p) {
                $profiles[(int) ($p['id'] ?? 0)] = $p;
            }
            if ($chunk === []) {
                break;
            }
            foreach ($chunk as $row) {
                $fetched++;
                $dto = $this->mapComment($row, $profiles, $source, 'wall');
                if ($dto !== null && mb_strlen(trim($dto->body)) >= $options->minTextLength) {
                    $items[] = $dto;
                }
            }
            $offset += count($chunk);
            if (count($chunk) < $options->pageSize) {
                break;
            }
        }

        return ReviewFetchResult::success($items, $fetched);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, array<string, mixed>>  $profiles
     */
    private function mapComment(array $row, array $profiles, ReviewImportSource $source, string $kind): ?ExternalReviewItemDto
    {
        if (! empty($row['deleted'])) {
            return null;
        }
        $text = trim((string) ($row['text'] ?? ''));
        if ($text === '') {
            return null;
        }
        $fromId = (int) ($row['from_id'] ?? 0);
        $prof = $profiles[$fromId] ?? [];
        $name = trim(($prof['first_name'] ?? '').' '.($prof['last_name'] ?? ''));
        if ($name === '') {
            $name = 'VK user '.$fromId;
        }
        $photo = isset($prof['photo_100']) ? (string) $prof['photo_100'] : null;
        $cid = (string) ($row['id'] ?? '');
        $date = isset($row['date']) ? Carbon::createFromTimestamp((int) $row['date']) : null;
        $permalink = $kind === 'board'
            ? $source->source_url.'?comment='.$cid
            : $source->source_url.'?reply='.$cid;

        return new ExternalReviewItemDto(
            externalId: $cid !== '' ? $cid : null,
            authorName: $name !== '' ? $name : null,
            authorAvatarUrl: $photo,
            rating: null,
            reviewedAt: $date,
            body: Str::of($text)->replace(['<br>', '<br/>', '<br />'], "\n")->toString(),
            sourceUrl: $permalink,
            rawPayload: $row,
        );
    }

    /**
     * @param  array<string, mixed>  $err
     */
    private function mapVkError(array $err): ReviewFetchResult
    {
        $code = (string) ($err['error_code'] ?? 'vk_error');
        $msg = (string) ($err['error_msg'] ?? 'VK API error');

        return ReviewFetchResult::unavailable($code, $msg);
    }
}
