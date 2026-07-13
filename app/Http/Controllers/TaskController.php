<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Display a listing of the tasks and projects.
     */
    public function index(Request $request)
    {
        $projects = Project::all();
        $selectedProjectId = $request->input('project_id');
        $selectedPriority = $request->input('priority');

        $tasks = Task::query()
            ->when($selectedProjectId, function ($query, $projectId) {
                return $query->where('project_id', $projectId);
            })
            ->when($selectedPriority, function ($query, $priority) {
                return $query->where('priority', $priority);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('sequence', 'asc')
            ->get();

        // Group tasks by status for the Kanban Board
        $boardTasks = [
            'to_do' => $tasks->where('status', 'to_do'),
            'in_progress' => $tasks->where('status', 'in_progress'),
            'testing' => $tasks->where('status', 'testing'),
            'completed' => $tasks->where('status', 'completed'),
        ];

        return view('tasks.index', compact('tasks', 'boardTasks', 'projects', 'selectedProjectId', 'selectedPriority'));
    }

    /**
     * Store a newly created task in database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'status' => 'nullable|string|in:to_do,in_progress,testing,completed',
            'priority' => 'required|integer|in:1,2,3,4',
        ]);

        $status = $request->input('status', 'to_do');
        $maxSequence = Task::where('status', $status)->max('sequence') ?? 0;

        Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'project_id' => $request->project_id,
            'status' => $status,
            'priority' => $request->priority,
            'sequence' => $maxSequence + 1,
        ]);

        return redirect()->back()->with('success', 'Task created successfully.');
    }

    /**
     * Update the specified task in database.
     */
    public function update(Request $request, Task $task)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'status' => 'required|string|in:to_do,in_progress,testing,completed',
            'priority' => 'required|integer|in:1,2,3,4',
        ]);

        $oldStatus = $task->status;
        $newStatus = $request->status;

        $task->update([
            'name' => $request->name,
            'description' => $request->description,
            'project_id' => $request->project_id,
            'status' => $newStatus,
            'priority' => $request->priority,
        ]);

        // If status changed, reset sequence of the task to the bottom of the new status list
        if ($oldStatus != $newStatus) {
            $maxSequence = Task::where('status', $newStatus)->max('sequence') ?? 0;
            $task->update(['sequence' => $maxSequence + 1]);
        }

        return redirect()->back()->with('success', 'Task updated successfully.');
    }

    /**
     * Remove the specified task from database.
     */
    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->back()->with('success', 'Task deleted successfully.');
    }

    /**
     * Reorder tasks and update their status & sequence numbers dynamically.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'sometimes|array',
            'order.*' => 'integer|exists:tasks,id',
            'status' => 'required|string|in:to_do,in_progress,testing,completed',
        ]);

        if (empty($request->order)) {
            return response()->json(['status' => 'success', 'message' => 'No tasks to reorder.']);
        }

        DB::transaction(function () use ($request) {
            $status = $request->status;
            foreach ($request->order as $index => $id) {
                Task::where('id', $id)->update([
                    'status' => $status,
                    'sequence' => $index + 1
                ]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Tasks reordered successfully.']);
    }
}
