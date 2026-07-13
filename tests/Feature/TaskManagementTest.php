<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the index page returns successfully.
     */
    public function test_can_view_tasks_dashboard(): void
    {
        $response = $this->get(route('tasks.index'));
        $response->assertStatus(200);
    }

    /**
     * Test creating a project.
     */
    public function test_can_create_project(): void
    {
        $response = $this->post(route('projects.store'), [
            'name' => 'Project Alpha'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Project Alpha'
        ]);
    }

    /**
     * Test creating a task with a status.
     */
    public function test_can_create_task(): void
    {
        $project = Project::create(['name' => 'Web App Development']);

        $response = $this->from(route('tasks.index', ['project_id' => $project->id]))
            ->post(route('tasks.store'), [
                'name' => 'Install Laravel Framework',
                'description' => 'Install framework via composer',
                'project_id' => $project->id,
                'status' => 'to_do',
                'priority' => 2 // medium
            ]);

        $response->assertRedirect(route('tasks.index', ['project_id' => $project->id]));
        $this->assertDatabaseHas('tasks', [
            'name' => 'Install Laravel Framework',
            'description' => 'Install framework via composer',
            'project_id' => $project->id,
            'status' => 'to_do',
            'priority' => 2
        ]);
    }

    /**
     * Test updating a task.
     */
    public function test_can_update_task(): void
    {
        $task = Task::create([
            'name' => 'Old Task Name', 
            'description' => 'Old Task Description',
            'priority' => 2,
            'status' => 'to_do'
        ]);

        $response = $this->from(route('tasks.index', ['project_id' => null]))
            ->put(route('tasks.update', $task), [
                'name' => 'Updated Task Name',
                'description' => 'Updated Task Description',
                'project_id' => null,
                'status' => 'in_progress',
                'priority' => 3 // high
            ]);

        $response->assertRedirect(route('tasks.index', ['project_id' => null]));
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Updated Task Name',
            'description' => 'Updated Task Description',
            'status' => 'in_progress',
            'priority' => 3
        ]);
    }

    /**
     * Test deleting a task.
     */
    public function test_can_delete_task(): void
    {
        $task = Task::create(['name' => 'Task to delete', 'priority' => 2, 'status' => 'to_do']);

        $response = $this->from(route('tasks.index', ['project_id' => null]))
            ->delete(route('tasks.destroy', $task));

        $response->assertRedirect(route('tasks.index', ['project_id' => null]));
        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id
        ]);
    }

    /**
     * Test reordering tasks changes database priorities and statuses.
     */
    public function test_can_reorder_tasks_priority_and_status(): void
    {
        $taskA = Task::create(['name' => 'Task A', 'priority' => 2, 'status' => 'to_do']);
        $taskB = Task::create(['name' => 'Task B', 'priority' => 3, 'status' => 'to_do']);

        // Drag Task B to In Progress column as sequence #1
        $response = $this->post(route('tasks.reorder'), [
            'order' => [$taskB->id],
            'status' => 'in_progress'
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);

        $this->assertEquals('in_progress', $taskB->fresh()->status);
        $this->assertEquals(1, $taskB->fresh()->sequence);
    }

    /**
     * Test GET request to reorder route redirects to index.
     */
    public function test_get_reorder_route_redirects_to_index(): void
    {
        $response = $this->get('/tasks/reorder');
        $response->assertRedirect(route('tasks.index'));
    }

    /**
     * Test reordering with empty order array (e.g. when a column is emptied).
     */
    public function test_can_reorder_empty_column_order_list(): void
    {
        $response = $this->post(route('tasks.reorder'), [
            'order' => [],
            'status' => 'to_do'
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'success']);
    }
}
