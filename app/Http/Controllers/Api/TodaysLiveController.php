<?php

namespace App\Http\Controllers\Api;

use App\Enums\CourseContentType;
use App\Http\Controllers\Controller;
use App\Http\Resources\TodaysLiveResource;
use App\Models\CourseContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class TodaysLiveController extends Controller
{
    /**
     * Today's live schedule for the public website: exam and live-class content
     * items (from published courses) whose start date is today.
     */
    public function index(): JsonResponse
    {
        $contents = CourseContent::query()
            ->whereIn('type', [CourseContentType::Exam->value, CourseContentType::Live->value])
            ->whereHas('course', fn ($query) => $query->where('is_published', true))
            ->with('course:id,title')
            ->get()
            ->filter(fn (CourseContent $content) => $this->startsToday($content));

        return response()->json([
            'exams' => TodaysLiveResource::collection(
                $contents->where('type', CourseContentType::Exam)->sortBy(fn ($c) => $c->payload['start_time'] ?? '')->values()
            ),
            'classes' => TodaysLiveResource::collection(
                $contents->where('type', CourseContentType::Live)->sortBy(fn ($c) => $c->payload['scheduled_at'] ?? '')->values()
            ),
        ]);
    }

    /**
     * Whether the content item's start date (exam start_time / live scheduled_at) is today.
     */
    private function startsToday(CourseContent $content): bool
    {
        $key = $content->type === CourseContentType::Exam ? 'start_time' : 'scheduled_at';
        $value = $content->payload[$key] ?? null;

        return $value !== null && Carbon::parse($value)->isToday();
    }
}
