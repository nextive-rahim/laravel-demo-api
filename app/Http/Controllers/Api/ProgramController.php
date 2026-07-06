<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProgramCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProgramResource;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rules\Enum;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProgramController extends Controller
{
    /**
     * List published programs, optionally filtered by `?category=academic|skills|admission|job`.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'category' => ['nullable', new Enum(ProgramCategory::class)],
        ]);

        $programs = Program::query()
            ->where('is_published', true)
            ->when($validated['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->orderBy('position')
            ->latest()
            ->get();

        return ProgramResource::collection($programs);
    }

    public function show(Program $program): ProgramResource
    {
        if (! $program->is_published) {
            throw new NotFoundHttpException;
        }

        return new ProgramResource($program);
    }
}
