<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Http\Resources\QuestionResource;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    /**
     * List questions in the store, filterable by subcategory, category or text search.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $questions = Question::query()
            ->with(['options', 'subcategory.category'])
            ->when($request->integer('subcategory_id'), fn ($query, $id) => $query->where('subcategory_id', $id))
            ->when($request->integer('category_id'), fn ($query, $id) => $query->whereHas('subcategory', fn ($q) => $q->where('category_id', $id)))
            ->when($request->string('search')->trim()->value(), fn ($query, $search) => $query->where('body', 'like', "%{$search}%"))
            ->latest()
            ->get();

        return QuestionResource::collection($questions);
    }

    /**
     * Create a question with its options (exactly one marked correct).
     */
    public function store(StoreQuestionRequest $request): JsonResponse
    {
        $question = DB::transaction(function () use ($request) {
            $question = Question::create([
                'subcategory_id' => $request->validated('subcategory_id'),
                'body' => $request->validated('body'),
                'marks' => $request->validated('marks') ?? 1,
            ]);

            $this->syncOptions($question, $request->validated('options'));

            return $question;
        });

        return (new QuestionResource($question->load(['options', 'subcategory.category'])))
            ->response()->setStatusCode(201);
    }

    /**
     * Show a single question with its options.
     */
    public function show(Question $question): QuestionResource
    {
        return new QuestionResource($question->load(['options', 'subcategory.category']));
    }

    /**
     * Update a question and, when supplied, replace its full set of options.
     */
    public function update(UpdateQuestionRequest $request, Question $question): QuestionResource
    {
        DB::transaction(function () use ($request, $question) {
            $question->update($request->safe()->only(['subcategory_id', 'body', 'marks']));

            if ($request->has('options')) {
                $question->options()->delete();
                $this->syncOptions($question, $request->validated('options'));
            }
        });

        return new QuestionResource($question->load(['options', 'subcategory.category']));
    }

    /**
     * Delete a question (also detaches it from any exams via cascade).
     */
    public function destroy(Question $question): JsonResponse
    {
        $question->delete();

        return response()->json(['message' => 'Question deleted.']);
    }

    /**
     * Persist the option rows for a question, preserving their order.
     *
     * @param  array<int, array{body: string, is_correct: bool}>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        foreach (array_values($options) as $position => $option) {
            $question->options()->create([
                'body' => $option['body'],
                'is_correct' => filter_var($option['is_correct'], FILTER_VALIDATE_BOOLEAN),
                'position' => $position,
            ]);
        }
    }
}
