<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CategoryTask extends Model
{
    //
    protected $primaryKey = 'task_id';
    protected $table = 'category_task';

    public $timestamps = false;

    public function saveCategoryTaskData(array $data) {
        $task_id = $data['task_id'];
        $task = Task::find($task_id);
        $category = TaskCategory::select(['id', 'category'])->where('category', $data['category'])->first();
        if($category) {
            $old_data = static::where('task_id', $data['task_id'])->first(['task_id']);

            if(!$old_data) {
                $task_category = new static;
                $task_category->task_category_id = $category['id'];
                $task_category->task_id = $task_id;
                if($task_category->save()) {
                    #update task category not efficient way
                    if($task) {
                        $task->category = $category['category'];
                        $task->save();
                    }
                    return $task_category;
                }
            } else {

                $old_data->task_category_id = $category['id'];
                $old_data->task_id = $task_id;
                if($old_data->save()) {
                    #update task category not efficient way
                    if($task) {
                        $task->category = $category['category'];
                        $task->save();
                    }
                    return $old_data;
                }
            }
        }
        return false;
    }

    #relation
    public function category() {
        return $this->hasOne(TaskCategory::class, 'id', 'task_category_id');
    }
}
