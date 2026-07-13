@extends('layouts.acorn')
@section('title', 'Tasks')

@push('css')
    <style>
        /* Kanban Board CSS Design from Ticket Raising Module */
        .kanban-board {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            padding: 0.5rem 0;
            align-items: flex-start;
        }

        .kanban-column {
            flex: 1;
            min-width: 280px;
            background: var(--background);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            max-height: 80vh;
        }

        html[data-theme="dark"] .kanban-column {
            background: var(--background-light);
            border-color: rgba(255, 255, 255, 0.05);
        }

        .kanban-column-header {
            padding: 1.25rem 1rem 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid transparent;
        }

        .kanban-column-title {
            font-size: 0.85rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kanban-column-count {
            background: rgba(0, 0, 0, 0.05);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        html[data-theme="dark"] .kanban-column-count {
            background: rgba(255, 255, 255, 0.1);
        }

        .kanban-column-body {
            padding: 1rem;
            overflow-y: auto;
            flex-grow: 1;
            min-height: 450px;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .kanban-card {
            background: var(--foreground);
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.05);
            cursor: grab;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }

        html[data-theme="dark"] .kanban-card {
            border-color: rgba(255, 255, 255, 0.05);
        }

        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06);
        }

        .kanban-card:active {
            cursor: grabbing;
        }

        .sortable-ghost {
            opacity: 0.4;
            border: 2px dashed var(--primary) !important;
            background: rgba(var(--primary-rgb), 0.05) !important;
        }

        .sortable-drag {
            opacity: 0.9;
        }

        .priority-low {
            font-weight: 700;
            font-size: 0.7rem;
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            padding: 3px 8px;
            border-radius: 20px;
            display: inline-block;
        }

        .priority-medium {
            font-weight: 700;
            font-size: 0.7rem;
            background: rgba(246, 195, 67, 0.15);
            color: #d39e00;
            padding: 3px 8px;
            border-radius: 20px;
            display: inline-block;
        }

        .priority-high {
            font-weight: 700;
            font-size: 0.7rem;
            background: rgba(230, 81, 0, 0.15);
            color: #e65100;
            padding: 3px 8px;
            border-radius: 20px;
            display: inline-block;
        }

        .priority-critical {
            font-weight: 700;
            font-size: 0.7rem;
            background: #c62828;
            color: #ffffff;
            padding: 3px 8px;
            border-radius: 20px;
            display: inline-block;
        }

        .kanban-empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: var(--muted);
            font-size: 0.8rem;
            border: 2px dashed rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.01);
            user-select: none;
        }
    </style>
    <script>
        if (localStorage.getItem('task_manager_view') === 'board') {
            document.write(
                '<style>#list-view-container { display: none; } #board-view-container { display: block; }</style>');
        }
    </script>
@endpush

@section('content')
    <!-- Header matching ticket raises view controls -->
    <div class="row mb-4 align-items-center">
        <div class="col-12 col-md-5">
            <h1 class="mb-0 pb-0 display-4">Tasks</h1>
        </div>
        <div class="col-12 col-md-7 d-flex align-items-center justify-content-md-end flex-wrap gap-2 mt-3 mt-md-0">
            <!-- View Toggle Group -->
            <div class="btn-group me-2 shadow-sm" role="group" id="viewToggleGroup">
                <button type="button" class="btn btn-outline-primary active" id="btn_list_view">
                    <i class="fa-solid fa-list me-1"></i> List
                </button>
                <button type="button" class="btn btn-outline-primary" id="btn_board_view">
                    <i class="fa-solid fa-table-columns me-1"></i> Board
                </button>
            </div>

            <!-- Create Project Trigger -->
            <button class="btn btn-outline-primary btn-icon shadow-sm" data-bs-toggle="modal"
                data-bs-target="#createProjectModal">
                <i class="fa-solid fa-folder-plus me-1"></i>
                <span>New Project</span>
            </button>

            <!-- Create Task Trigger -->
            <button class="btn btn-primary btn-icon shadow-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                <i class="fa-solid fa-plus me-1"></i>
                <span>Add Task</span>
            </button>
        </div>
    </div>

    <!-- Filters Bar with Select2 -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form action="{{ route('tasks.index') }}" method="GET" id="filterForm" class="row align-items-center g-3">
                <div class="col-md-3 col-sm-5">
                    <div class="w-100">
                        <select name="project_id" id="projectFilter" class="form-select select2"
                            onchange="this.form.submit()">
                            <option value="">All Projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}"
                                    {{ $selectedProjectId == $project->id ? 'selected' : '' }}>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 col-sm-5">
                    <div class="w-100">
                        <select name="priority" id="priorityFilter" class="form-select select2"
                            onchange="this.form.submit()">
                            <option value="">All Priorities</option>
                            <option value="1" {{ $selectedPriority == 1 ? 'selected' : '' }}>Low</option>
                            <option value="2" {{ $selectedPriority == 2 ? 'selected' : '' }}>Medium</option>
                            <option value="3" {{ $selectedPriority == 3 ? 'selected' : '' }}>High</option>
                            <option value="4" {{ $selectedPriority == 4 ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>
                </div>
                @if ($selectedProjectId || $selectedPriority)
                    <div class="col-auto">
                        <a href="{{ route('tasks.index') }}"
                            class="btn btn-outline-danger btn-sm shadow-sm py-1 px-3 d-inline-flex align-items-center justify-content-center">
                            <i class="fa-solid fa-filter-circle-xmark me-1"></i> Clear Filters
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- DUAL CONTAINERS -->

    <!-- 1. List View Container -->
    <div id="list-view-container">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Task Name</th>
                                <th>Description</th>
                                <th style="width: 80px;">Priority</th>
                                <th>Project</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tasks as $task)
                                <tr>
                                    <td class="fw-bold">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="fw-bold">{{ $task->name }}</span>
                                    </td>
                                    <td>
                                        @if ($task->description)
                                            <div class="text-muted small"
                                                style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                                title="{{ $task->description }}">
                                                {{ $task->description }}
                                            </div>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($task->priority == 1)
                                            <span class="priority-low">Low</span>
                                        @elseif($task->priority == 2)
                                            <span class="priority-medium">Medium</span>
                                        @elseif($task->priority == 3)
                                            <span class="priority-high">High</span>
                                        @elseif($task->priority == 4)
                                            <span class="priority-critical">Critical</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($task->project)
                                            <span class="badge bg-secondary">{{ $task->project->name }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($task->status == 'to_do')
                                            <span class="badge bg-outline-primary">To Do</span>
                                        @elseif($task->status == 'in_progress')
                                            <span class="badge bg-outline-warning">In Progress</span>
                                        @elseif($task->status == 'testing')
                                            <span class="badge bg-outline-info">Testing</span>
                                        @elseif($task->status == 'completed')
                                            <span class="badge bg-outline-success">Completed</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button class="btn btn-sm btn-outline-warning edit-task-btn"
                                                data-id="{{ $task->id }}" data-name="{{ $task->name }}"
                                                data-description="{{ $task->description }}"
                                                data-project-id="{{ $task->project_id }}"
                                                data-status="{{ $task->status }}" data-priority="{{ $task->priority }}"
                                                data-bs-toggle="modal" data-bs-target="#editTaskModal">
                                                Edit
                                            </button>
                                            <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this task?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        No tasks found. Click "Add Task" to get started!
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Board View Container (Kanban layout from ticket index raises) -->
    <div id="board-view-container" style="display: none;">
        <div class="kanban-board">
            <!-- To Do Column -->
            <div class="kanban-column">
                <div class="kanban-column-header">
                    <span class="kanban-column-title">
                        <i class="fa-solid fa-list-ul text-primary"></i> To Do
                    </span>
                    <span class="kanban-column-count" id="count_status_to_do">0</span>
                </div>
                <div class="kanban-column-body" id="col_status_to_do" data-status="to_do">
                    @foreach ($boardTasks['to_do'] as $task)
                        <div class="kanban-card" data-id="{{ $task->id }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div id="badge-{{ $task->id }}">
                                    @if ($task->priority == 1)
                                        <span class="priority-low">Low</span>
                                    @elseif($task->priority == 2)
                                        <span class="priority-medium">Medium</span>
                                    @elseif($task->priority == 3)
                                        <span class="priority-high">High</span>
                                    @elseif($task->priority == 4)
                                        <span class="priority-critical">Critical</span>
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-xs btn-outline-warning p-1 edit-task-btn"
                                        data-id="{{ $task->id }}" data-name="{{ $task->name }}"
                                        data-description="{{ $task->description }}"
                                        data-project-id="{{ $task->project_id }}" data-status="{{ $task->status }}"
                                        data-priority="{{ $task->priority }}" data-bs-toggle="modal"
                                        data-bs-target="#editTaskModal" style="font-size: 0.65rem; line-height: 1;">
                                        Edit
                                    </button>
                                    <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                        onsubmit="return confirm('Are you sure?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger p-1"
                                            style="font-size: 0.65rem; line-height: 1;">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-1">{{ $task->name }}</h6>
                            @if ($task->description)
                                <p class="text-muted small mb-2"
                                    style="font-size: 0.75rem; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;"
                                    title="{{ $task->description }}">
                                    {{ $task->description }}</p>
                            @endif
                            @if ($task->project)
                                <span class="badge bg-secondary"
                                    style="font-size: 0.65rem;">{{ $task->project->name }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- In Progress Column -->
            <div class="kanban-column">
                <div class="kanban-column-header">
                    <span class="kanban-column-title">
                        <i class="fa-solid fa-spinner text-warning"></i> In Progress
                    </span>
                    <span class="kanban-column-count" id="count_status_in_progress">0</span>
                </div>
                <div class="kanban-column-body" id="col_status_in_progress" data-status="in_progress">
                    @foreach ($boardTasks['in_progress'] as $task)
                        <div class="kanban-card" data-id="{{ $task->id }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div id="badge-{{ $task->id }}">
                                    @if ($task->priority == 1)
                                        <span class="priority-low">Low</span>
                                    @elseif($task->priority == 2)
                                        <span class="priority-medium">Medium</span>
                                    @elseif($task->priority == 3)
                                        <span class="priority-high">High</span>
                                    @elseif($task->priority == 4)
                                        <span class="priority-critical">Critical</span>
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-xs btn-outline-warning p-1 edit-task-btn"
                                        data-id="{{ $task->id }}" data-name="{{ $task->name }}"
                                        data-description="{{ $task->description }}"
                                        data-project-id="{{ $task->project_id }}" data-status="{{ $task->status }}"
                                        data-priority="{{ $task->priority }}" data-bs-toggle="modal"
                                        data-bs-target="#editTaskModal" style="font-size: 0.65rem; line-height: 1;">
                                        Edit
                                    </button>
                                    <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                        onsubmit="return confirm('Are you sure?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger p-1"
                                            style="font-size: 0.65rem; line-height: 1;">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-1">{{ $task->name }}</h6>
                            @if ($task->description)
                                <p class="text-muted small mb-2"
                                    style="font-size: 0.75rem; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;"
                                    title="{{ $task->description }}">
                                    {{ $task->description }}</p>
                            @endif
                            @if ($task->project)
                                <span class="badge bg-secondary"
                                    style="font-size: 0.65rem;">{{ $task->project->name }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Testing Column -->
            <div class="kanban-column">
                <div class="kanban-column-header">
                    <span class="kanban-column-title">
                        <i class="fa-solid fa-vial text-info"></i> Testing
                    </span>
                    <span class="kanban-column-count" id="count_status_testing">0</span>
                </div>
                <div class="kanban-column-body" id="col_status_testing" data-status="testing">
                    @foreach ($boardTasks['testing'] as $task)
                        <div class="kanban-card" data-id="{{ $task->id }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div id="badge-{{ $task->id }}">
                                    @if ($task->priority == 1)
                                        <span class="priority-low">Low</span>
                                    @elseif($task->priority == 2)
                                        <span class="priority-medium">Medium</span>
                                    @elseif($task->priority == 3)
                                        <span class="priority-high">High</span>
                                    @elseif($task->priority == 4)
                                        <span class="priority-critical">Critical</span>
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-xs btn-outline-warning p-1 edit-task-btn"
                                        data-id="{{ $task->id }}" data-name="{{ $task->name }}"
                                        data-description="{{ $task->description }}"
                                        data-project-id="{{ $task->project_id }}" data-status="{{ $task->status }}"
                                        data-priority="{{ $task->priority }}" data-bs-toggle="modal"
                                        data-bs-target="#editTaskModal" style="font-size: 0.65rem; line-height: 1;">
                                        Edit
                                    </button>
                                    <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                        onsubmit="return confirm('Are you sure?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger p-1"
                                            style="font-size: 0.65rem; line-height: 1;">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-1">{{ $task->name }}</h6>
                            @if ($task->description)
                                <p class="text-muted small mb-2"
                                    style="font-size: 0.75rem; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;"
                                    title="{{ $task->description }}">
                                    {{ $task->description }}</p>
                            @endif
                            @if ($task->project)
                                <span class="badge bg-secondary"
                                    style="font-size: 0.65rem;">{{ $task->project->name }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Completed Column -->
            <div class="kanban-column">
                <div class="kanban-column-header">
                    <span class="kanban-column-title">
                        <i class="fa-solid fa-check-double text-success"></i> Completed
                    </span>
                    <span class="kanban-column-count" id="count_status_completed">0</span>
                </div>
                <div class="kanban-column-body" id="col_status_completed" data-status="completed">
                    @foreach ($boardTasks['completed'] as $task)
                        <div class="kanban-card" data-id="{{ $task->id }}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div id="badge-{{ $task->id }}">
                                    @if ($task->priority == 1)
                                        <span class="priority-low">Low</span>
                                    @elseif($task->priority == 2)
                                        <span class="priority-medium">Medium</span>
                                    @elseif($task->priority == 3)
                                        <span class="priority-high">High</span>
                                    @elseif($task->priority == 4)
                                        <span class="priority-critical">Critical</span>
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-xs btn-outline-warning p-1 edit-task-btn"
                                        data-id="{{ $task->id }}" data-name="{{ $task->name }}"
                                        data-description="{{ $task->description }}"
                                        data-project-id="{{ $task->project_id }}" data-status="{{ $task->status }}"
                                        data-priority="{{ $task->priority }}" data-bs-toggle="modal"
                                        data-bs-target="#editTaskModal" style="font-size: 0.65rem; line-height: 1;">
                                        Edit
                                    </button>
                                    <form action="{{ route('tasks.destroy', $task) }}" method="POST"
                                        onsubmit="return confirm('Are you sure?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger p-1"
                                            style="font-size: 0.65rem; line-height: 1;">Delete</button>
                                    </form>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-1">{{ $task->name }}</h6>
                            @if ($task->description)
                                <p class="text-muted small mb-2"
                                    style="font-size: 0.75rem; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;"
                                    title="{{ $task->description }}">
                                    {{ $task->description }}</p>
                            @endif
                            @if ($task->project)
                                <span class="badge bg-secondary"
                                    style="font-size: 0.65rem;">{{ $task->project->name }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- MODALS -->

    <!-- Create Task Modal -->
    <div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('tasks.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Create New Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="taskName" class="form-label fw-bold">Task Name</label>
                            <input type="text" name="name" class="form-control" id="taskName"
                                placeholder="Enter task name" required>
                        </div>
                        <div class="mb-3">
                            <label for="taskDescription" class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" id="taskDescription" rows="3"
                                placeholder="Enter task description"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="w-100">
                                <label for="taskProject" class="form-label fw-bold">Project</label>
                                <select name="project_id" class="form-select select2-modal" id="taskProject"
                                    style="width: 100%;">
                                    <option value="">No Project</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}"
                                            {{ $selectedProjectId == $project->id ? 'selected' : '' }}>
                                            {{ $project->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="w-100">
                                <label for="taskStatus" class="form-label fw-bold">Status</label>
                                <select name="status" class="form-select select2-modal" id="taskStatus"
                                    style="width: 100%;">
                                    <option value="to_do">To Do</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="testing">Testing</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="w-100">
                                <label for="taskPriority" class="form-label fw-bold">Priority</label>
                                <select name="priority" class="form-select select2-modal" id="taskPriority"
                                    style="width: 100%;">
                                    <option value="1">Low</option>
                                    <option value="2" selected>Medium</option>
                                    <option value="3">High</option>
                                    <option value="4">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Task Modal -->
    <div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editTaskForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editTaskName" class="form-label fw-bold">Task Name</label>
                            <input type="text" name="name" id="editTaskName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="editTaskDescription" class="form-label fw-bold">Description</label>
                            <textarea name="description" id="editTaskDescription" class="form-control" rows="3"
                                placeholder="Enter task description"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editTaskProject" class="form-label fw-bold">Project</label>
                            <select name="project_id" id="editTaskProject" class="form-select select2-modal"
                                style="width: 100%;">
                                <option value="">No Project</option>
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editTaskStatus" class="form-label fw-bold">Status</label>
                            <select name="status" id="editTaskStatus" class="form-select select2-modal"
                                style="width: 100%;">
                                <option value="to_do">To Do</option>
                                <option value="in_progress">In Progress</option>
                                <option value="testing">Testing</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editTaskPriority" class="form-label fw-bold">Priority</label>
                            <select name="priority" id="editTaskPriority" class="form-select select2-modal"
                                style="width: 100%;">
                                <option value="1">Low</option>
                                <option value="2">Medium</option>
                                <option value="3">High</option>
                                <option value="4">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Project Modal -->
    <div class="modal fade" id="createProjectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('projects.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Add New Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="projectName" class="form-label fw-bold">Project Name</label>
                            <input type="text" name="name" id="projectName" class="form-control"
                                placeholder="Enter project name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Project</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 on the filter dropdowns
            $('#projectFilter').select2({
                theme: 'bootstrap4',
                placeholder: 'Select a project'
            });
            $('#priorityFilter').select2({
                theme: 'bootstrap4',
                placeholder: 'Select a priority'
            });

            // Initialize Select2 inside modal boxes when shown
            $('.modal').on('shown.bs.modal', function() {
                $(this).find('.select2-modal').select2({
                    theme: 'bootstrap4',
                    dropdownParent: $(this)
                });
            });

            // Track if tasks have been reordered
            let tasksChanged = false;

            function setView(viewType) {
                localStorage.setItem('task_manager_view', viewType);
                if (viewType === 'board') {
                    $('#btn_board_view').addClass('active');
                    $('#btn_list_view').removeClass('active');
                    $('#board-view-container').show();
                    $('#list-view-container').hide();
                    // Re-calc column counts & check empty status when board view loads
                    updateColumnCounts();
                } else {
                    $('#btn_list_view').addClass('active');
                    $('#btn_board_view').removeClass('active');
                    $('#list-view-container').show();
                    $('#board-view-container').hide();
                }
            }

            // Initialize active view from localStorage
            const initialView = localStorage.getItem('task_manager_view') || 'list';
            setView(initialView);

            $('#btn_list_view').on('click', function() {
                if (localStorage.getItem('task_manager_view') === 'list') return;
                if (tasksChanged) {
                    localStorage.setItem('task_manager_view', 'list');
                    window.location.reload();
                } else {
                    setView('list');
                }
            });

            $('#btn_board_view').on('click', function() {
                if (localStorage.getItem('task_manager_view') === 'board') return;
                if (tasksChanged) {
                    localStorage.setItem('task_manager_view', 'board');
                    window.location.reload();
                } else {
                    setView('board');
                }
            });

            // Edit Task Modal data binding
            $(document).on('click', '.edit-task-btn', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const description = $(this).data('description');
                const projectId = $(this).data('project-id');
                const status = $(this).data('status');
                const priority = $(this).data('priority');

                $('#editTaskName').val(name);
                $('#editTaskDescription').val(description);
                $('#editTaskProject').val(projectId).trigger('change');
                $('#editTaskStatus').val(status).trigger('change');
                $('#editTaskPriority').val(priority).trigger('change');
                $('#editTaskForm').attr('action', `/tasks/${id}`);
            });

            // Initialize SortableJS on all columns
            document.querySelectorAll('.kanban-column-body').forEach(el => {
                new Sortable(el, {
                    group: 'kanban-board',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    dragClass: 'sortable-drag',
                    filter: '.kanban-empty-state',
                    onEnd: function(evt) {
                        let taskId = evt.item.dataset.id;
                        if (!taskId) return;

                        let newStatus = evt.to.dataset.status;
                        let oldStatus = evt.from.dataset.status;

                        // Reorder target column list items
                        saveNewOrder(evt.to, newStatus);

                        // If dragged to another column, also reorder the source column items
                        if (newStatus !== oldStatus) {
                            saveNewOrder(evt.from, oldStatus);
                        }
                    }
                });
            });

            function saveNewOrder(columnElement, status) {
                let order = [];
                $(columnElement).find('.kanban-card').each(function() {
                    order.push($(this).data('id'));
                });

                // Update column badges & empty state locally
                updateColumnCounts();

                $.ajax({
                    url: "{{ route('tasks.reorder') }}",
                    type: "POST",
                    data: {
                        order: order,
                        status: status
                    },
                    success: function(response) {
                        tasksChanged = true;
                    },
                    error: function() {
                        alert('Failed to update task sequence/status.');
                    }
                });
            }

            function checkEmptyState(colElement) {
                let container = $(colElement);
                if (container.children().length === 0 || (container.children().length === 1 && container.children()
                        .hasClass('kanban-empty-state'))) {
                    container.html(`
                    <div class="kanban-empty-state">
                        No tasks in this column
                    </div>
                `);
                } else {
                    container.find('.kanban-empty-state').remove();
                }
            }

            function updateColumnCounts() {
                document.querySelectorAll('.kanban-column-body').forEach(el => {
                    const status = el.dataset.status;
                    const count = $(el).find('.kanban-card').length;
                    $('#count_status_' + status).text(count);
                    checkEmptyState(el);
                });
            }

            // Initial setup for board counters
            updateColumnCounts();
        });
    </script>
@endpush
