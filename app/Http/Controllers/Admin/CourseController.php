<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Course\StoreCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Admin\Course;
use App\Services\Course\CourseService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    protected CourseService $service;
    public function __construct(CourseService $service)
    {
        $this->service = $service;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseRequest $request)
    {
        $validated = $request->validated();

        $course = $this->service->createCourse($validated, $request->user());

        return response()->json([
            'message' => 'دوره با موفقیت ایجاد شد.',
            'data' => new CourseResource($course->load('category', 'instructor')),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Course $course)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        //
    }
}
